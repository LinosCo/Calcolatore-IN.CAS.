<?php
/**
 * Handles storage and retrieval of embeddings for RAG support.
 *
 * @package AI_Chatbot
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AICB_Embedding_Manager {

	const TABLE_NAME                 = 'aicb_embeddings';
	const SNAPSHOT_OPTION_KEY        = 'aicb_current_snapshot_id';
	const SNAPSHOT_META_OPTION_KEY   = 'aicb_embedding_snapshot_meta';
	const SCHEMA_VERSION             = 2;
	const SCHEMA_VERSION_OPTION_KEY  = 'aicb_embeddings_schema_version';

	private static $instance = null;

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->maybe_upgrade_schema();
		add_action( 'update_option_aicb_settings', array( $this, 'maybe_rebuild_after_settings_change' ), 10, 3 );
		add_action( 'aicb_run_embedding_rebuild', array( $this, 'handle_rebuild_event' ) );
	}

	/**
	 * Singleton accessor.
	 *
	 * @return AICB_Embedding_Manager
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Handle scheduled rebuild.
	 *
	 * @return void
	 */
	public function handle_rebuild_event() {
		delete_option( 'aicb_embedding_rebuild_scheduled' );
		$this->rebuild_embeddings();
	}

	/**
	 * Trigger a rebuild via cron to avoid blocking the request.
	 *
	 * @return void
	 */
	public function schedule_rebuild() {
		if ( get_option( 'aicb_embedding_rebuild_scheduled' ) ) {
			return;
		}

		update_option( 'aicb_embedding_rebuild_scheduled', time(), false );

		wp_schedule_single_event( time() + 10, 'aicb_run_embedding_rebuild' );
	}

	/**
	 * Evaluate settings update and schedule rebuild if needed.
	 *
	 * @param array $old_value Previous settings.
	 * @param array $value     New settings.
	 * @param string $option   Option name.
	 * @return void
	 */
	public function maybe_rebuild_after_settings_change( $old_value, $value, $option ) {
		if ( 'aicb_settings' !== $option ) {
			return;
		}

		$old_value = is_array( $old_value ) ? $old_value : array();
		$value     = is_array( $value ) ? $value : array();

		$system_changed = isset( $value['system_prompt'] ) && ( ! isset( $old_value['system_prompt'] ) || $old_value['system_prompt'] !== $value['system_prompt'] );
		$knowledge_changed = isset( $value['enable_knowledge_base'] ) && ( ! isset( $old_value['enable_knowledge_base'] ) || $old_value['enable_knowledge_base'] !== $value['enable_knowledge_base'] );

		if ( $system_changed || $knowledge_changed ) {
			$this->schedule_rebuild();
		}
	}

	/**
	 * Create embeddings table.
	 *
	 * @return void
	 */
	public static function create_table() {
		global $wpdb;

		$table_name      = $wpdb->prefix . self::TABLE_NAME;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id BIGINT(20) NOT NULL AUTO_INCREMENT,
			snapshot_id VARCHAR(64) NOT NULL,
			source_type VARCHAR(50) DEFAULT NULL,
			source_id BIGINT(20) DEFAULT NULL,
			source_title VARCHAR(255) DEFAULT NULL,
			source_url VARCHAR(500) DEFAULT NULL,
			source_category VARCHAR(150) DEFAULT NULL,
			chunk_index INT(11) DEFAULT 0,
			chunk LONGTEXT NOT NULL,
			checksum VARCHAR(64) NOT NULL,
			embedding LONGTEXT NOT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY snapshot_id (snapshot_id),
			KEY source_lookup (source_type, source_id),
			KEY checksum (checksum),
			FULLTEXT KEY chunk_search (chunk)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( self::SCHEMA_VERSION_OPTION_KEY, self::SCHEMA_VERSION, false );
	}

	/**
	 * Rebuild embeddings from the knowledge base.
	 *
	 * @return true|WP_Error
	 */
	public function rebuild_embeddings() {
		if ( ! class_exists( 'AICB_Knowledge_Base' ) ) {
			require_once AICB_PLUGIN_DIR . 'includes/class-aicb-knowledge-base.php';
		}

		$api = new AICB_API();

		$snapshot_id   = $this->generate_snapshot_id();
		$system_prompt = $api->get_system_prompt();
		$embedding_model = apply_filters( 'aicb_embedding_model', 'text-embedding-3-small' );

		global $wpdb;

		$knowledge_table = $wpdb->prefix . 'aicb_knowledge_base';
		$embeddings_table = $wpdb->prefix . self::TABLE_NAME;

		$entries = $wpdb->get_results( "SELECT id, title, content, source_type, source_url, category, content_hash FROM {$knowledge_table} WHERE status = 'active'", ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$entry_count = is_array( $entries ) ? count( $entries ) : 0;

		if ( empty( $entries ) ) {
			update_option( self::SNAPSHOT_OPTION_KEY, $snapshot_id );
			$this->persist_snapshot_metadata( $snapshot_id, $system_prompt, 0, $entry_count, $embedding_model );
			$wpdb->query( "TRUNCATE TABLE {$embeddings_table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			return true;
		}

		// Start fresh.
		$wpdb->query( "TRUNCATE TABLE {$embeddings_table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		$inserted = 0;

		foreach ( $entries as $entry ) {
			$chunks = $this->chunk_content( $entry['title'], $entry['content'] );

			foreach ( $chunks as $index => $chunk ) {
				$embedding = $api->create_embedding( $chunk, $embedding_model );

				if ( is_wp_error( $embedding ) ) {
					return $embedding;
				}

				$normalized = $this->normalize_vector( (array) $embedding );

				$insert = $wpdb->insert(
					$embeddings_table,
					array(
						'snapshot_id'  => $snapshot_id,
						'source_type'  => $entry['source_type'],
						'source_id'    => $entry['id'],
						'source_title' => $entry['title'],
						'source_url'   => $entry['source_url'],
						'source_category' => isset( $entry['category'] ) ? $entry['category'] : null,
						'chunk_index'  => $index,
						'chunk'        => $chunk,
						'checksum'     => $this->build_chunk_checksum( $entry, $index, $chunk ),
						'embedding'    => wp_json_encode( $normalized ),
					),
					array( '%s', '%s', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s' )
				);

				if ( false === $insert ) {
					return new WP_Error( 'aicb_embedding_insert_failed', esc_html__( 'Failed to store knowledge embeddings.', 'ai-chatbot' ) );
				}

				++$inserted;
			}
		}

		update_option( self::SNAPSHOT_OPTION_KEY, $snapshot_id );
		$this->persist_snapshot_metadata( $snapshot_id, $system_prompt, $inserted, $entry_count, $embedding_model );

		return true;
	}

	/**
	 * Generate a new snapshot id.
	 *
	 * @return string
	 */
	public function generate_snapshot_id() {
		return 'snap_' . wp_generate_uuid4();
	}

	/**
	 * Retrieve best matching chunks for a message.
	 *
	 * @param array $query_embedding Embedding vector of the query.
	 * @param int   $limit           Number of matches.
	 * @return array
	 */
	public function find_matching_chunks( $query_embedding, $limit = 5 ) {
		global $wpdb;

		$snapshot_id = get_option( self::SNAPSHOT_OPTION_KEY );

		if ( empty( $snapshot_id ) || empty( $query_embedding ) ) {
			return array();
		}

		$table  = $wpdb->prefix . self::TABLE_NAME;
		$rows   = $wpdb->get_results( $wpdb->prepare( "SELECT chunk, embedding, source_title, source_url, source_type, source_category, checksum FROM {$table} WHERE snapshot_id = %s", $snapshot_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared

		if ( empty( $rows ) ) {
			return array();
		}

		$scores = array();

		foreach ( $rows as $row ) {
			$embedding = json_decode( $row['embedding'], true );

			if ( ! is_array( $embedding ) ) {
				continue;
			}

			$embedding   = array_map( 'floatval', $embedding );
			$similarity = $this->cosine_similarity( $query_embedding, $embedding );

			if ( null === $similarity ) {
				continue;
			}

			$scores[] = array(
				'score'       => $similarity,
				'chunk'       => $row['chunk'],
				'source_title'=> $row['source_title'],
				'source_url'  => $row['source_url'],
				'source_type' => isset( $row['source_type'] ) ? $row['source_type'] : '',
				'source_category' => isset( $row['source_category'] ) ? $row['source_category'] : '',
				'checksum'    => isset( $row['checksum'] ) ? $row['checksum'] : '',
			);
		}

		if ( empty( $scores ) ) {
			return array();
		}

		usort(
			$scores,
			function ( $a, $b ) {
				if ( $a['score'] === $b['score'] ) {
					return 0;
				}
				return ( $a['score'] > $b['score'] ) ? -1 : 1;
			}
		);

		return array_slice( $scores, 0, $limit );
	}

	/**
	 * Break content into manageable chunks.
	 *
	 * @param string $title   Entry title.
	 * @param string $content Entry content.
	 * @return array
	 */
	private function chunk_content( $title, $content ) {
		$clean = wp_strip_all_tags( $content );
		$clean = preg_replace( '/\s+/u', ' ', $clean );
		$clean = trim( (string) $clean );

		if ( '' === $clean ) {
			return array();
		}

		$max_chars   = 780;
		$min_chars   = 420;
		$total_len   = mb_strlen( $clean );
		$offset      = 0;
		$chunks      = array();
		$title_clean = trim( (string) $title );

		while ( $offset < $total_len ) {
			$remaining = mb_substr( $clean, $offset, $max_chars );

			if ( false === $remaining ) {
				break;
			}

			$segment = $remaining;

			if ( mb_strlen( $segment ) === $max_chars && ( $offset + $max_chars ) < $total_len ) {
				$breakpoint = $this->find_sentence_breakpoint( $segment, $min_chars );
				if ( false !== $breakpoint ) {
					$segment = mb_substr( $segment, 0, $breakpoint );
				}
			}

			$segment = trim( (string) $segment );

			if ( '' === $segment ) {
				$offset += $max_chars;
				continue;
			}

			if ( '' !== $title_clean ) {
				$chunks[] = $title_clean . "\n\n" . $segment;
			} else {
				$chunks[] = $segment;
			}

			$offset += mb_strlen( $segment );
		}

		return $chunks;
	}

	/**
	 * Cosine similarity helper.
	 *
	 * @param array $a Vector one.
	 * @param array $b Vector two.
	 * @return float|null
	 */
	private function cosine_similarity( $a, $b ) {
		$len_a = count( $a );
		$len_b = count( $b );

		if ( $len_a !== $len_b || 0 === $len_a ) {
			return null;
		}

		$dot = 0.0;
		$norm_a = 0.0;
		$norm_b = 0.0;

		for ( $i = 0; $i < $len_a; $i++ ) {
			$dot    += $a[ $i ] * $b[ $i ];
			$norm_a += $a[ $i ] ** 2;
			$norm_b += $b[ $i ] ** 2;
		}

		if ( 0.0 === $norm_a || 0.0 === $norm_b ) {
			return null;
		}

		return $dot / ( sqrt( $norm_a ) * sqrt( $norm_b ) );
	}

	/**
	 * Ensure the embeddings table is up to date.
	 *
	 * @return void
	 */
	private function maybe_upgrade_schema() {
		$current = (int) get_option( self::SCHEMA_VERSION_OPTION_KEY, 0 );

		if ( $current >= self::SCHEMA_VERSION ) {
			return;
		}

		self::create_table();
	}

	/**
	 * Normalize a vector to unit length for cosine comparison.
	 *
	 * @param array $vector Raw embedding vector.
	 * @return array
	 */
	private function normalize_vector( array $vector ) {
		$norm        = 0.0;
		$normalized  = array();

		foreach ( $vector as $value ) {
			$float        = (float) $value;
			$normalized[] = $float;
			$norm        += $float ** 2;
		}

		if ( $norm <= 0 ) {
			return $normalized;
		}

		$norm = sqrt( $norm );

		foreach ( $normalized as $index => $value ) {
			$normalized[ $index ] = $value / $norm;
		}

		return $normalized;
	}

	/**
	 * Build a deterministic checksum for a chunk.
	 *
	 * @param array  $entry Knowledge base entry data.
	 * @param int    $index Chunk sequence index.
	 * @param string $chunk Chunk text.
	 * @return string
	 */
	private function build_chunk_checksum( array $entry, $index, $chunk ) {
		$base = '';

		if ( ! empty( $entry['content_hash'] ) ) {
			$base = (string) $entry['content_hash'];
		} else {
			$base = md5( (string) $entry['content'] );
		}

		return hash( 'sha256', $base . '::' . (int) $index . '::' . substr( $chunk, 0, 32 ) );
	}

	/**
	 * Determine a reasonable breakpoint for a chunk, preferring sentence boundaries.
	 *
	 * @param string $segment Chunk segment.
	 * @param int    $min_chars Minimum characters to keep before breaking.
	 * @return int|false
	 */
	private function find_sentence_breakpoint( $segment, $min_chars ) {
		$length = mb_strlen( $segment );

		if ( $length <= $min_chars ) {
			return false;
		}

		$search_window = mb_substr( $segment, $min_chars );

		$break_chars = array( '. ', '? ', '! ', "\n" );
		$best_pos    = false;

		foreach ( $break_chars as $delimiter ) {
			$pos = mb_strrpos( $search_window, $delimiter );
			if ( false !== $pos ) {
				$candidate = $min_chars + $pos + mb_strlen( $delimiter );
				if ( $candidate > $min_chars && $candidate < $length ) {
					$best_pos = $candidate;
				}
			}
		}

		if ( false !== $best_pos ) {
			return $best_pos;
		}

		$space_pos = mb_strrpos( $segment, ' ' );

		if ( false !== $space_pos && $space_pos > $min_chars ) {
			return $space_pos;
		}

		return false;
	}

	/**
	 * Normalize and return a query vector ready for similarity checks.
	 *
	 * @param array $vector Raw query vector.
	 * @return array
	 */
	public function prepare_query_vector( array $vector ) {
		return $this->normalize_vector( $vector );
	}

	/**
	 * Persist snapshot metadata alongside the identifier.
	 *
	 * @param string $snapshot_id     Generated snapshot id.
	 * @param string $system_prompt   System prompt used during indexing.
	 * @param int    $chunk_count     Total stored chunks.
	 * @param int    $entry_count     Knowledge base entry count.
	 * @param string $embedding_model Embedding model identifier.
	 * @return void
	 */
	private function persist_snapshot_metadata( $snapshot_id, $system_prompt, $chunk_count, $entry_count, $embedding_model ) {
		$metadata = array(
			'snapshot_id'     => $snapshot_id,
			'system_prompt'   => (string) $system_prompt,
			'generated_at'    => current_time( 'mysql' ),
			'chunk_count'     => (int) $chunk_count,
			'entry_count'     => (int) $entry_count,
			'embedding_model' => (string) $embedding_model,
		);

		update_option( self::SNAPSHOT_META_OPTION_KEY, $metadata, false );
	}
}
