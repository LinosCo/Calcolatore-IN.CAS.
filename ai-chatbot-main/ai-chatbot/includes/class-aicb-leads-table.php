<?php
/**
 * AICB_Leads_Table Class.
 *
 * This class extends WP_List_Table to display captured leads in the WordPress admin area.
 *
 * @package    AI_Chatbot
 * @subpackage AI_Chatbot/includes
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * AICB_Leads_Table class.
 *
 * Creates a WP_List_Table for displaying leads.
 *
 * @since 1.0.0
 */
class AICB_Leads_Table extends WP_List_Table {

	/**
	 * Constructor.
	 *
	 * Sets up the list table parameters.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => __( 'Lead', 'ai-chatbot' ),
				'plural'   => __( 'Leads', 'ai-chatbot' ),
				'ajax'     => false,
			)
		);
	}

	/**
	 * Get a list of columns.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return array Columns to show in the list table.
	 */
	public function get_columns() {
		$columns = array(
			'name'       => esc_html__( 'Name', 'ai-chatbot' ),
			'email'      => esc_html__( 'Email', 'ai-chatbot' ),
			'phone'      => esc_html__( 'Phone', 'ai-chatbot' ),
			'page_url'   => esc_html__( 'Page URL', 'ai-chatbot' ),
			'marketing_consent' => esc_html__( 'Marketing Consent', 'ai-chatbot' ),
			'created_at' => esc_html__( 'Captured At', 'ai-chatbot' ),
		);
		return $columns;
	}

	/**
	 * Get a list of sortable columns.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @return array Array of sortable columns.
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			'name'       => array( 'name', false ),
			'email'      => array( 'email', false ),
			'created_at' => array( 'created_at', true ),
		);
		return $sortable_columns;
	}

	/**
	 * Prepares the list of items for display.
	 *
	 * @since  1.0.0
	 * @access public
	 */
	public function prepare_items() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'aicb_leads';

		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		// Handle sorting.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading sort order for display.
		$orderby_requested = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'created_at';
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading sort order for display.
		$order_requested = isset( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'DESC';

		$orderby = sanitize_sql_orderby( $orderby_requested );
		$order   = strtoupper( $order_requested ); // sanitize_key applied before, strtoupper is safe.

		if ( ! array_key_exists( $orderby, $sortable ) ) {
			$orderby = 'created_at';
		}
		if ( 'ASC' !== $order && 'DESC' !== $order ) {
			$order = 'DESC';
		}

		$per_page     = 20;
		$current_page = $this->get_pagenum();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table_name is safe as it uses $wpdb->prefix.
		$total_items = $wpdb->get_var( "SELECT COUNT(id) FROM {$table_name}" );

		$this->set_pagination_args(
			array(
				'total_items' => absint( $total_items ),
				'per_page'    => $per_page,
			)
		);

		$offset = ( $current_page - 1 ) * $per_page;
		$query  = $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table_name, $orderby, and $order are sanitized/safe.
			"SELECT * FROM {$table_name} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d",
			$per_page,
			$offset
		);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Query preparation is handled above with sanitization.
		$this->items = $wpdb->get_results( $query, ARRAY_A );
	}

	/**
	 * Default column rendering.
	 *
	 * @since  1.0.0
	 * @access protected
	 *
	 * @param array  $item        The current item's data.
	 * @param string $column_name The name of the current column.
	 * @return string Rendered column content.
	 */
	protected function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'email':
			case 'phone':
				return isset( $item[ $column_name ] ) ? esc_html( $item[ $column_name ] ) : '';
			case 'marketing_consent':
				return isset( $item['marketing_consent'] ) ? esc_html( ucfirst( str_replace( '_', ' ', $item['marketing_consent'] ) ) ) : esc_html__( 'Not set', 'ai-chatbot' );
			case 'created_at':
				return isset( $item[ $column_name ] ) ? esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $item[ $column_name ] ) ) ) : '';
			default:
				return isset( $item[ $column_name ] ) ? esc_html( $item[ $column_name ] ) : '';
		}
	}

	/**
	 * Render the 'Name' column with row actions.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param array $item The current item's data.
	 * @return string Rendered 'Name' column content with actions.
	 */
	public function column_name( $item ) {
		// Build delete action URL.
		$delete_nonce = wp_create_nonce( 'aicb_delete_lead_' . $item['id'] );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading 'page' for URL construction, not action processing.
		$page_slug  = isset( $_REQUEST['page'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ) : '';
		$delete_url = admin_url(
			add_query_arg(
				array(
					'page'     => $page_slug,
					'action'   => 'delete_lead',
					'lead_id'  => absint( $item['id'] ),
					'_wpnonce' => $delete_nonce,
				),
				'admin.php'
			)
		);

		$actions = array(
			'delete' => sprintf(
				'<a href="%s" onclick="return confirm(\'%s\');" style="color:#a00;">%s</a>',
				esc_url( $delete_url ),
				esc_js( __( 'Are you sure you want to delete this lead?', 'ai-chatbot' ) ),
				esc_html__( 'Delete', 'ai-chatbot' )
			),
		);

		$item_name = isset( $item['name'] ) ? esc_html( $item['name'] ) : '';
		return sprintf( '%1$s %2$s', $item_name, $this->row_actions( $actions ) );
	}

	/**
	 * Render the 'Page URL' column as a clickable link.
	 *
	 * @since  1.0.0
	 * @access public
	 *
	 * @param array $item The current item's data.
	 * @return string Rendered 'Page URL' column content.
	 */
	public function column_page_url( $item ) {
		if ( ! empty( $item['page_url'] ) ) {
			$url          = esc_url( $item['page_url'] );
			$display_text = esc_html( wp_basename( $url ) );
			if ( strlen( $display_text ) > 50 ) {
				$display_text = esc_html( substr( $display_text, 0, 47 ) ) . '...';
			}
			return sprintf( '<a href="%s" target="_blank" title="%s">%s</a>', $url, esc_attr( $url ), $display_text );
		} else {
			return esc_html__( 'N/A', 'ai-chatbot' );
		}
	}
} // End class AICB_Leads_Table.
