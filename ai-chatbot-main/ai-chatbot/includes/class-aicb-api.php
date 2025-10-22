<?php
/**
 * OpenAI API helper for AI Chatbot.
 *
 * @package AI_Chatbot
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'AICB_Embedding_Manager' ) ) {
	require_once AICB_PLUGIN_DIR . 'includes/class-aicb-embedding-manager.php';
}

/**
 * Helper responsible for communicating with OpenAI and exposing REST utilities.
 */
class AICB_API {

	/**
	 * Base OpenAI API URL.
	 *
	 * @var string
	 */
	private $api_url = 'https://api.openai.com/v1';

	/**
	 * Cached plugin settings.
	 *
	 * @var array
	 */
	private $options = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->options = get_option( 'aicb_settings', array() );
	}

	/**
	 * Register REST routes handled directly by this helper.
	 *
	 * @return void
	 */
	public function register_rest_routes() {
		register_rest_route(
			'ai-chatbot/v1',
			'/lead',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle_lead_submission' ),
				'permission_callback' => array( $this, 'verify_rest_permission' ),
			)
		);

		register_rest_route(
			'ai-chatbot/v1',
			'/consent',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle_consent_update' ),
				'permission_callback' => array( $this, 'verify_rest_permission' ),
			)
		);
	}

	/**
	 * Verify the REST request nonce.
	 *
	 * @param WP_REST_Request $request Request instance.
	 * @return true|WP_Error
	 */
	public function verify_rest_permission( WP_REST_Request $request ) {
		$nonce = $request->get_header( 'X-WP-Nonce' );

		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new WP_Error(
				'rest_forbidden',
				esc_html__( 'Invalid security token supplied.', 'ai-chatbot' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * Handle lead submissions coming from the REST endpoint.
	 *
	 * @param WP_REST_Request $request Request instance.
	 * @return WP_REST_Response|WP_Error
	 */
	public function handle_lead_submission( WP_REST_Request $request ) {
		$settings    = get_option( 'aicb_settings', array() );
		$form_fields = isset( $settings['lead_form_fields'] ) ? $settings['lead_form_fields'] : array();

		$params = $request->get_json_params();
		if ( empty( $params ) ) {
			$params = $request->get_body_params();
		}

		if ( empty( $params ) || ! is_array( $params ) ) {
			return new WP_Error( 'invalid_payload', esc_html__( 'Invalid request payload.', 'ai-chatbot' ), array( 'status' => 400 ) );
		}

		// Build list of required fields based on configuration.
		$required_fields = array();
		foreach ( $form_fields as $field => $config ) {
			if ( ! empty( $config['enabled'] ) && ! empty( $config['required'] ) ) {
				$required_fields[] = $field;
			}
		}

		$errors = array();
		foreach ( $required_fields as $field ) {
			$value = isset( $params[ $field ] ) ? trim( (string) $params[ $field ] ) : '';
			if ( '' === $value ) {
				$errors[] = sprintf(
					/* translators: %s field name */
					esc_html__( '%s is required.', 'ai-chatbot' ),
					esc_html( ucfirst( $field ) )
				);
			}
		}

		if ( isset( $form_fields['email']['enabled'] ) && $form_fields['email']['enabled'] && ! empty( $params['email'] ) && ! is_email( $params['email'] ) ) {
			$errors[] = esc_html__( 'Please enter a valid email address.', 'ai-chatbot' );
		}

		if ( ! empty( $errors ) ) {
			return new WP_Error( 'lead_validation_failed', implode( ' ', $errors ), array( 'status' => 422 ) );
		}

		$privacy_consent   = isset( $params['privacy'] ) && ( true === $params['privacy'] || 'true' === $params['privacy'] ) ? 'given' : 'not_given';
		$marketing_enabled = ! empty( $settings['lead_enable_marketing_opt_in'] );
		$marketing_consent = 'not_applicable';
		if ( $marketing_enabled ) {
			$marketing_consent = ( isset( $params['marketing'] ) && ( true === $params['marketing'] || 'true' === $params['marketing'] ) )
				? 'given'
				: 'not_given';
		}

		$lead_data = array(
			'name'             => isset( $params['name'] ) ? sanitize_text_field( wp_unslash( $params['name'] ) ) : '',
			'email'            => isset( $params['email'] ) ? sanitize_email( wp_unslash( $params['email'] ) ) : '',
			'phone'            => isset( $params['phone'] ) ? sanitize_text_field( wp_unslash( $params['phone'] ) ) : '',
			'company'          => isset( $params['company'] ) ? sanitize_text_field( wp_unslash( $params['company'] ) ) : '',
			'message'          => isset( $params['message'] ) ? sanitize_textarea_field( wp_unslash( $params['message'] ) ) : '',
			'page_url'         => isset( $params['page_url'] ) ? esc_url_raw( wp_unslash( $params['page_url'] ) ) : '',
			'created_at'       => current_time( 'mysql' ),
			'ip_address'       => $this->get_client_ip(),
			'privacy_consent'  => $privacy_consent,
			'consent_timestamp'=> 'given' === $privacy_consent ? current_time( 'mysql' ) : null,
			'marketing_consent'=> $marketing_consent,
			'thread_id'        => isset( $params['thread_id'] ) ? sanitize_text_field( wp_unslash( $params['thread_id'] ) ) : null,
			'user_identifier'  => isset( $params['user_identifier'] ) ? sanitize_text_field( wp_unslash( $params['user_identifier'] ) ) : null,
		);

		global $wpdb;
		$table_name = $wpdb->prefix . 'aicb_leads';

		$result = $wpdb->insert(
			$table_name,
			$lead_data,
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( false === $result ) {
			return new WP_Error( 'database_error', esc_html__( 'Failed to save lead information.', 'ai-chatbot' ), array( 'status' => 500 ) );
		}

		$this->send_lead_notification( $lead_data );

		return rest_ensure_response(
			array(
				'success' => true,
				'message' => esc_html__( 'Thank you! Your information has been submitted.', 'ai-chatbot' ),
			)
		);
	}

	/**
	 * Notify site admin about a new lead.
	 *
	 * @param array $lead_data Sanitized lead data.
	 * @return void
	 */
	private function send_lead_notification( array $lead_data ) {
		$settings = get_option( 'aicb_settings', array() );
		$to       = isset( $settings['lead_notification_email'] ) && is_email( $settings['lead_notification_email'] )
			? $settings['lead_notification_email']
			: get_option( 'admin_email' );

		$subject = sprintf(
			/* translators: %s site title */
			esc_html__( 'New Lead from %s', 'ai-chatbot' ),
			wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES )
		);

		$message = sprintf(
			"%s\n\n%s %s\n%s %s\n%s %s\n%s %s\n%s %s\n%s %s\n%s %s\n%s %s\n%s %s\n%s %s\n%s %s\n%s %s",
			esc_html__( 'New lead captured from the chatbot:', 'ai-chatbot' ),
			esc_html__( 'Name:', 'ai-chatbot' ),
			$lead_data['name'],
			esc_html__( 'Email:', 'ai-chatbot' ),
			$lead_data['email'],
			esc_html__( 'Phone:', 'ai-chatbot' ),
			$lead_data['phone'],
			esc_html__( 'Company:', 'ai-chatbot' ),
			$lead_data['company'],
			esc_html__( 'Message:', 'ai-chatbot' ),
			$lead_data['message'],
			esc_html__( 'Page URL:', 'ai-chatbot' ),
			$lead_data['page_url'],
			esc_html__( 'Date:', 'ai-chatbot' ),
			$lead_data['created_at'],
			esc_html__( 'IP Address:', 'ai-chatbot' ),
			$lead_data['ip_address'],
			esc_html__( 'Privacy Consent:', 'ai-chatbot' ),
			$lead_data['privacy_consent'],
			esc_html__( 'Marketing Consent:', 'ai-chatbot' ),
			$lead_data['marketing_consent'],
			esc_html__( 'Consent Timestamp:', 'ai-chatbot' ),
			$lead_data['consent_timestamp'],
			esc_html__( 'Thread ID:', 'ai-chatbot' ),
			isset( $lead_data['thread_id'] ) ? $lead_data['thread_id'] : '',
			esc_html__( 'User Identifier:', 'ai-chatbot' ),
			isset( $lead_data['user_identifier'] ) ? $lead_data['user_identifier'] : ''
		);

		wp_mail(
			$to,
			$subject,
			$message,
			array( 'Content-Type: text/plain; charset=UTF-8' )
		);
	}

	/**
	 * Handle consent updates (currently no persistence, hook for extensibility).
	 *
	 * @param WP_REST_Request $request Request instance.
	 * @return WP_REST_Response
	 */
	public function handle_consent_update( WP_REST_Request $request ) {
		$data = array(
			'save_history' => (bool) $request->get_param( 'save_history' ),
			'thread_id'    => sanitize_text_field( (string) $request->get_param( 'conversation_id' ) ),
		);

		/**
		 * Fires when a visitor updates consent preferences.
		 *
		 * @param array           $data    Consent data.
		 * @param WP_REST_Request $request Request instance.
		 */
		do_action( 'aicb_consent_update', $data, $request );

		return rest_ensure_response(
			array(
				'success' => true,
			)
		);
	}

	/**
	 * Public wrapper to send a message using the improved assistant flow.
	 *
	 * @param string      $message       User prompt.
	 * @param string      $thread_id     Thread identifier.
	 * @param string|null $page_context  Context payload.
	 * @param string      $language_code Target language.
	 * @return array
	 */
	public function send_message_v2( $message, $thread_id = '', $page_context = null, $language_code = 'en' ) {
		$api_key = $this->get_api_key();

		if ( empty( $api_key ) ) {
			return array(
				'error' => esc_html__( 'API Key is missing.', 'ai-chatbot' ),
			);
		}

		try {
			return $this->send_message_using_responses_api( $message, $thread_id, $page_context, $language_code );
		} catch ( Exception $exception ) {
			return array(
				'error' => $exception->getMessage(),
			);
		}
	}

	/**
	 * Run OpenAI moderation against supplied text.
	 *
	 * @param string $text_to_moderate Text to moderate.
	 * @return array
	 */
	public function moderate_content( $text_to_moderate ) {
		$api_key = $this->get_api_key();

		if ( empty( $api_key ) ) {
			return array(
				'flagged' => false,
				'error'   => esc_html__( 'API Key is missing.', 'ai-chatbot' ),
			);
		}

		if ( empty( $text_to_moderate ) ) {
			return array(
				'flagged' => false,
				'reason'  => esc_html__( 'Empty input.', 'ai-chatbot' ),
			);
		}

		$response = wp_remote_post(
			$this->api_url . '/moderations',
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'input' => $text_to_moderate,
					)
				),
				'timeout' => 20,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'flagged' => false,
				'error'   => $response->get_error_message(),
			);
		}

		$status = wp_remote_retrieve_response_code( $response );
		$body   = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $status ) {
			$error = isset( $body['error']['message'] ) ? $body['error']['message'] : esc_html__( 'Unknown moderation error.', 'ai-chatbot' );

			return array(
				'flagged' => false,
				'error'   => $error,
			);
		}

		if ( isset( $body['results'][0] ) ) {
			$result = $body['results'][0];

			return array(
				'flagged'         => (bool) $result['flagged'],
				'categories'      => (array) $result['categories'],
				'category_scores' => (array) $result['category_scores'],
			);
		}

		return array(
			'flagged' => false,
			'error'   => esc_html__( 'Invalid moderation response.', 'ai-chatbot' ),
		);
	}

	/**
	 * Execute a lightweight connectivity test against the configured model.
	 *
	 * @return array
	 */
	public function test_connection() {
		$api_key = $this->get_api_key();

		if ( empty( $api_key ) ) {
			return array(
				'success' => false,
				'message' => esc_html__( 'OpenAI API Key is missing.', 'ai-chatbot' ),
			);
		}

		$model = isset( $this->options['model'] ) ? $this->options['model'] : 'gpt-4o-mini';

		$response = wp_remote_get(
			$this->api_url . '/models/' . rawurlencode( $model ),
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
				),
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'message' => $response->get_error_message(),
			);
		}

		$status = wp_remote_retrieve_response_code( $response );

		if ( 200 !== $status ) {
			$body    = json_decode( wp_remote_retrieve_body( $response ), true );
			$message = isset( $body['error']['message'] ) ? $body['error']['message'] : esc_html__( 'Unexpected API response.', 'ai-chatbot' );

			return array(
				'success' => false,
				'message' => $message,
			);
		}

		return array(
			'success' => true,
			'message' => esc_html__( 'Connection successful!', 'ai-chatbot' ),
		);
	}

	/**
	 * Retrieve the configured API key.
	 *
	 * @return string
	 */
	private function get_api_key() {
		return isset( $this->options['openai_api_key'] ) ? trim( $this->options['openai_api_key'] ) : '';
	}

	/**
	 * Retrieve the system prompt currently configured.
	 *
	 * @return string
	 */
	public function get_system_prompt() {
		if ( isset( $this->options['system_prompt'] ) && '' !== trim( $this->options['system_prompt'] ) ) {
			return (string) $this->options['system_prompt'];
		}

		return __( 'You are a helpful assistant.', 'ai-chatbot' );
	}

	/**
	 * Send a message using the Assistants Responses API.
	 *
	 * @param string      $message       User message.
	 * @param string      $thread_id     Response thread identifier.
	 * @param string|null $page_context  JSON payload for contextual data.
	 * @param string      $language_code Target language.
	 * @return array
	 * @throws Exception When the API request fails.
	 */
	private function send_message_using_responses_api( $message, $thread_id, $page_context, $language_code ) {
		$api_key = $this->get_api_key();

		$model         = isset( $this->options['model'] ) ? $this->options['model'] : 'gpt-4o-mini';
		$system_prompt = $this->get_system_prompt();

		$input = array(
			array(
				'role'    => 'system',
				'content' => $system_prompt,
			),
		);

		if ( ! empty( $language_code ) && 'en' !== strtolower( $language_code ) ) {
			$input[] = array(
				'role'    => 'system',
				'content' => sprintf(
					/* translators: %s language code. */
					__( 'Please respond in %s.', 'ai-chatbot' ),
					sanitize_text_field( $language_code )
				),
			);
		}

		if ( ! empty( $page_context ) ) {
			$context = $this->build_context_from_payload( $page_context );
			if ( ! empty( $context ) ) {
				$input[] = array(
					'role'    => 'system',
					'content' => __( 'Use the following page context when it is relevant:', 'ai-chatbot' ) . "\n" . $context,
				);
			}
		}

		$knowledge_context = $this->get_knowledge_base_context( $message, $thread_id );
		$similarity_threshold = $this->get_similarity_threshold();

		if ( ! empty( $knowledge_context['content'] ) ) {
			$input[] = array(
				'role'    => 'system',
				'content' => __( 'Use the following knowledge base information when relevant:', 'ai-chatbot' ) . "\n" . $knowledge_context['content'],
			);
			if ( ! empty( $knowledge_context['low_confidence'] ) || ( isset( $knowledge_context['top_score'] ) && (float) $knowledge_context['top_score'] < $similarity_threshold ) ) {
				$input[] = array(
					'role'    => 'system',
					'content' => __( 'If the knowledge matches seem uncertain, ask the user for clarification before proceeding.', 'ai-chatbot' ),
				);
			}
		} else {
			$input[] = array(
				'role'    => 'system',
				'content' => __( 'If the request is ambiguous or the knowledge base does not provide matches, ask the user for clarification before answering.', 'ai-chatbot' ),
			);
		}

		$input[] = array(
			'role'    => 'user',
			'content' => $message,
		);

		$request_body = array(
			'model' => $model,
			'input' => $input,
			'store' => true,
		);

		if ( ! empty( $thread_id ) ) {
			$request_body['previous_response_id'] = $thread_id;
		}

		$response = wp_remote_post(
			$this->api_url . '/responses',
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode( $request_body ),
				'timeout' => 60,
			)
		);

        if ( is_wp_error( $response ) ) {
            throw new Exception( esc_html( $response->get_error_message() ) );
		}

		$status = wp_remote_retrieve_response_code( $response );
		$body   = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( 200 !== $status ) {
            $error = isset( $body['error']['message'] ) ? $body['error']['message'] : esc_html__( 'Unknown API error.', 'ai-chatbot' );
            throw new Exception( esc_html( $error ) );
		}

		$message_text = '';

		if ( ! empty( $body['output_text'] ) ) {
			$message_text = trim( $body['output_text'] );
		} elseif ( ! empty( $body['output'] ) && is_array( $body['output'] ) ) {
			$parts = array();
			foreach ( $body['output'] as $chunk ) {
				if ( isset( $chunk['content'][0]['text'] ) ) {
					$parts[] = $chunk['content'][0]['text'];
				}
			}
			$message_text = trim( implode( "\n", $parts ) );
		}

		if ( '' === $message_text ) {
			throw new Exception( esc_html__( 'Unexpected API response structure.', 'ai-chatbot' ) );
		}

		$response_id = isset( $body['id'] ) ? $body['id'] : $thread_id;

		return array(
			'message'   => $message_text,
			'thread_id' => $response_id,
		);
	}


	/**
	 * Build a concise textual context from the JSON payload supplied by the frontend.
	 *
	 * @param string $payload JSON payload.
	 * @return string
	 */
	private function build_context_from_payload( $payload ) {
		$context = json_decode( $payload, true );

		if ( empty( $context ) || ! is_array( $context ) ) {
			return '';
		}

		$segments = array();

		$field_limits = array(
			'title'       => 160,
			'description' => 400,
			'content'     => 600,
		);

		foreach ( $field_limits as $key => $limit ) {
			if ( empty( $context[ $key ] ) ) {
				continue;
			}

			$value = $this->truncate_prompt_text( $context[ $key ], $limit );
			if ( '' !== $value ) {
				$segments[] = ucfirst( $key ) . ': ' . $value;
			}
		}

		if ( ! empty( $context['headings'] ) ) {
			$segments[] = 'Headings: ' . $this->truncate_prompt_text( $context['headings'], 300 );
		}

		if ( ! empty( $context['links'] ) ) {
			$segments[] = 'Links: ' . $this->truncate_prompt_text( $context['links'], 300 );
		}

		if ( ! empty( $context['images'] ) ) {
			$segments[] = 'Images: ' . $this->truncate_prompt_text( $context['images'], 200 );
		}

		return implode( "\n", array_filter( $segments ) );
	}


	/**
	 * Create an embedding vector for the supplied text.
	 *
	 * @param string $text  Text to embed.
	 * @param string $model Optional. Embedding model.
	 * @return array|WP_Error
	 */
	public function create_embedding( $text, $model = 'text-embedding-3-small' ) {
		$text = trim( (string) $text );

		if ( '' === $text ) {
			return new WP_Error( 'aicb_embedding_empty', esc_html__( 'Cannot create embedding for empty text.', 'ai-chatbot' ) );
		}

		$api_key = $this->get_api_key();

		if ( empty( $api_key ) ) {
			return new WP_Error( 'aicb_embedding_no_key', esc_html__( 'API Key is missing.', 'ai-chatbot' ) );
		}

		$response = wp_remote_post(
			$this->api_url . '/embeddings',
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => wp_json_encode(
					array(
						'model' => $model,
						'input' => $text,
					)
				),
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $code || empty( $body['data'][0]['embedding'] ) ) {
			$error_message = isset( $body['error']['message'] ) ? $body['error']['message'] : esc_html__( 'Failed to create embedding.', 'ai-chatbot' );
			return new WP_Error( 'aicb_embedding_failed', $error_message );
		}

		return array_map( 'floatval', (array) $body['data'][0]['embedding'] );
	}

	private function truncate_prompt_text( $value, $limit = 400 ) {
		$clean = trim( wp_strip_all_tags( wp_unslash( $value ) ) );

		if ( '' === $clean ) {
			return '';
		}

		$clean = preg_replace( '/\s+/', ' ', $clean );

		if ( mb_strlen( $clean ) > $limit ) {
			$clean = trim( mb_substr( $clean, 0, $limit ) ) . '…';
		}

		return sanitize_text_field( $clean );
	}

	/**
	 * Truncate knowledge snippets while keeping them lightweight.
	 *
	 * @param string $value Raw snippet.
	 * @param int    $limit Character limit.
	 * @return string
	 */
	private function truncate_context_snippet( $value, $limit = 650 ) {
		$clean = trim( wp_strip_all_tags( wp_unslash( $value ) ) );

		if ( '' === $clean ) {
			return '';
		}

		$clean = preg_replace( '/\s+/u', ' ', $clean );

		if ( mb_strlen( $clean ) > $limit ) {
			$clean = trim( mb_substr( $clean, 0, $limit ) ) . '…';
		}

		return $clean;
	}

	/**
	 * Retrieve contextual snippets from the local knowledge base.
	 *
	 * @param string $message   User message.
	 * @param string $thread_id Current thread.
	 * @return array {
	 *     @type string $content   Concatenated knowledge chunks.
	 *     @type float  $top_score Highest similarity score.
	 *     @type array  $matches   Raw matches.
	 * }
	 */
	private function get_knowledge_base_context( $message, $thread_id ) {
		$result = array(
			'content'   => '',
			'top_score' => 0,
			'matches'   => array(),
			'low_confidence' => false,
		);

		if ( empty( $message ) ) {
			return $result;
		}

		if ( empty( $this->options['enable_knowledge_base'] ) || '1' !== $this->options['enable_knowledge_base'] ) {
			return $result;
		}

		$embedding_model = apply_filters( 'aicb_embedding_model', 'text-embedding-3-small' );
		$query_embedding = $this->create_embedding( $message, $embedding_model );

		if ( is_wp_error( $query_embedding ) ) {
			return $result;
		}

		$manager = AICB_Embedding_Manager::instance();
		$query_embedding = $manager->prepare_query_vector( array_map( 'floatval', (array) $query_embedding ) );
		$matches = $manager->find_matching_chunks( $query_embedding, 8 );

		if ( empty( $matches ) ) {
			return $result;
		}

		$similarity_threshold = $this->get_similarity_threshold();
		$max_context_chars    = 1800;
		$current_chars        = 0;
		$blocks = array();
		$primary_blocks = array();
		$backup_blocks = array();
		foreach ( $matches as $match ) {
			if ( empty( $match['chunk'] ) ) {
				continue;
			}

			$score = isset( $match['score'] ) ? (float) $match['score'] : 0.0;

			$snippet = $this->truncate_context_snippet( $match['chunk'], 650 );

			$source = '';
			if ( ! empty( $match['source_title'] ) ) {
				$source = sprintf(
					/* translators: %s: Knowledge base source title. */
					esc_html__( 'Source: %s', 'ai-chatbot' ),
					sanitize_text_field( $match['source_title'] )
				);
			}

			if ( ! empty( $match['source_category'] ) ) {
				$category_label = sprintf(
					/* translators: %s knowledge base category */
					esc_html__( 'Category: %s', 'ai-chatbot' ),
					sanitize_text_field( $match['source_category'] )
				);

				$source = '' !== $source ? $source . ' | ' . $category_label : $category_label;
			}

			if ( ! empty( $match['source_url'] ) ) {
				$url    = esc_url_raw( $match['source_url'] );
				$source = trim( $source . ' ' . '(' . $url . ')' );
			}

			if ( '' !== $source ) {
				$snippet .= "\n" . $source;
			}

			if ( $score >= $similarity_threshold ) {
				if ( $current_chars + mb_strlen( $snippet ) <= $max_context_chars ) {
					$primary_blocks[] = $snippet;
					$current_chars    += mb_strlen( $snippet );
				}
			} else {
				$backup_blocks[] = $snippet;
			}
		}

		if ( ! empty( $matches ) ) {
			$result['top_score'] = isset( $matches[0]['score'] ) ? (float) $matches[0]['score'] : 0;
			$result['matches']   = $matches;
		}

		if ( empty( $primary_blocks ) && ! empty( $backup_blocks ) ) {
			$blocks = array_slice( $backup_blocks, 0, 3 );
			$result['low_confidence'] = true;
		} else {
			$blocks = $primary_blocks;
			if ( empty( $blocks ) && empty( $backup_blocks ) ) {
				return $result;
			}
		}

		if ( ! empty( $blocks ) ) {
			$trimmed_blocks = array();
			$char_budget    = $max_context_chars;
			foreach ( $blocks as $block ) {
				$length = mb_strlen( $block );
				if ( $char_budget <= 0 ) {
					break;
				}
				$trimmed_blocks[] = $block;
				$char_budget     -= $length;
			}
			$blocks = $trimmed_blocks;
		}

		if ( empty( $blocks ) ) {
			return $result;
		}

		$result['content']   = implode( "\n\n", array_slice( $blocks, 0, 5 ) );

		return $result;
	}

	/**
	 * Retrieve configured similarity threshold.
	 *
	 * @return float
	 */
	private function get_similarity_threshold() {
		$default = 0.6;

		if ( empty( $this->options['rag_similarity_threshold'] ) ) {
			return $default;
		}

		$threshold = (float) $this->options['rag_similarity_threshold'];

		if ( $threshold <= 0 || $threshold >= 1 ) {
			return $default;
		}

		return $threshold;
	}

	/**
	 * Retrieve the best-effort client IP address.
	 *
	 * @return string
	 */
	private function get_client_ip() {
		$keys = array(
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_X_CLUSTER_CLIENT_IP',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR',
		);

		foreach ( $keys as $key ) {
			if ( empty( $_SERVER[ $key ] ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
				continue;
			}

			$ips = explode( ',', wp_unslash( $_SERVER[ $key ] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated

			foreach ( $ips as $ip ) {
				$ip = trim( $ip );

				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return sanitize_text_field( $ip );
				}
			}
		}

		return '';
	}
}

/**
 * Register AI Chatbot REST routes.
 *
 * Called from core bootstrap during rest_api_init.
 *
 * @return void
 */
function aicb_register_rest_routes() {
	$api = new AICB_API();
	$api->register_rest_routes();
}
