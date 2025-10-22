<?php
/**
 * Chat history list table.
 *
 * @package AI_Chatbot
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Displays stored chat history entries.
 */
class AICB_Chat_History_Table extends WP_List_Table {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => __( 'Chat Message', 'ai-chatbot' ),
				'plural'   => __( 'Chat Messages', 'ai-chatbot' ),
				'ajax'     => false,
			)
		);
	}

	/**
	 * Define columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		return array(
			'thread_id'       => __( 'Thread ID', 'ai-chatbot' ),
			'message_sender'  => __( 'Sender', 'ai-chatbot' ),
			'message_content' => __( 'Message', 'ai-chatbot' ),
			'timestamp'       => __( 'Timestamp', 'ai-chatbot' ),
		);
	}

	/**
	 * Prepare the list of items for displaying.
	 *
	 * @return void
	 */
	public function prepare_items() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'aicb_chat_history';

		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = array(
			'timestamp'      => array( 'timestamp', true ),
			'message_sender' => array( 'message_sender', false ),
		);

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$per_page     = 20;
		$current_page = $this->get_pagenum();
		$offset       = ( $current_page - 1 ) * $per_page;

		$order   = isset( $_GET['order'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_GET['order'] ) ) ) : 'DESC'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$orderby = isset( $_GET['orderby'] ) ? sanitize_sql_orderby( wp_unslash( $_GET['orderby'] ) ) : 'timestamp'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( empty( $orderby ) ) {
			$orderby = 'timestamp';
		}

		if ( 'ASC' !== $order && 'DESC' !== $order ) {
			$order = 'DESC';
		}

		$total_sql   = sprintf( 'SELECT COUNT(id) FROM %s', esc_sql( $table_name ) );
		$total_items = (int) $wpdb->get_var( $total_sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		$allowed_columns = array( 'timestamp', 'message_sender' );
		if ( ! in_array( $orderby, $allowed_columns, true ) ) {
			$orderby = 'timestamp';
		}

		if ( 'ASC' !== $order && 'DESC' !== $order ) {
			$order = 'DESC';
		}

		$query_template = sprintf(
			'SELECT thread_id, message_sender, message_content, timestamp FROM %s ORDER BY %s %s LIMIT %%d OFFSET %%d',
			esc_sql( $table_name ),
			esc_sql( $orderby ),
			esc_sql( $order )
		);

		$query = $wpdb->prepare( $query_template, $per_page, $offset ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$items = $wpdb->get_results( $query, ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared

		$this->items = $items ? $items : array();

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
			)
		);
	}

	/**
	 * Render default column output.
	 *
	 * @param array  $item        Current item.
	 * @param string $column_name Column name.
	 * @return string
	 */
	protected function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'thread_id':
				return esc_html( $item['thread_id'] );
			case 'message_sender':
				return esc_html( ucfirst( $item['message_sender'] ) );
			case 'message_content':
				return wp_kses_post( wp_trim_words( $item['message_content'], 30, '&hellip;' ) );
			case 'timestamp':
				return esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $item['timestamp'] ) ) );
			default:
				return '';
		}
	}
}
