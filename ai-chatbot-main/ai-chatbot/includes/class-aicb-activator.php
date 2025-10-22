<?php
/**
 * AICB_Activator Class.
 *
 * This class is responsible for actions that need to be performed when the plugin is activated.
 * This includes creating necessary database tables.
 *
 * @package    AI_Chatbot
 * @subpackage AI_Chatbot/includes
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 */
class AICB_Activator {

	/**
	 * Main activation hook. Creates database tables required by the plugin.
	 *
	 * @since 1.0.0
	 */
	public static function activate() {
		global $wpdb;

		// Leads table.
		$table_name_leads = $wpdb->prefix . 'aicb_leads';
		$charset_collate  = $wpdb->get_charset_collate();

		$sql_leads = "CREATE TABLE {$table_name_leads} (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			name varchar(100) NOT NULL,
			email varchar(100) NOT NULL,
			phone varchar(50) DEFAULT NULL,
			company varchar(100) DEFAULT NULL,
			message text DEFAULT NULL,
			page_url text DEFAULT NULL,
			ip_address varchar(100) DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			privacy_consent varchar(10) DEFAULT 'not_set' NOT NULL,
			consent_timestamp datetime DEFAULT NULL,
			marketing_consent varchar(15) DEFAULT 'not_applicable' NOT NULL,
			thread_id VARCHAR(255) DEFAULT NULL,
			user_identifier VARCHAR(255) DEFAULT NULL,
			PRIMARY KEY  (id)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql_leads );

		// Chat history table.
		$table_name_history = $wpdb->prefix . 'aicb_chat_history';
		$sql_history        = "CREATE TABLE {$table_name_history} (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			thread_id varchar(255) NOT NULL,
			message_sender varchar(20) NOT NULL,
			message_content text NOT NULL,
			timestamp datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			page_context_url varchar(2083) DEFAULT NULL,
			user_identifier varchar(255) DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY thread_id (thread_id),
			KEY timestamp (timestamp),
			KEY user_identifier (user_identifier)
		) {$charset_collate};";

		dbDelta( $sql_history );

		// Verify table creation as dbDelta can be unreliable for reporting.
		// If dbDelta failed, try a direct CREATE TABLE IF NOT EXISTS query for chat history.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name_history ) );

		if ( $table_name_history !== $table_exists ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- $sql_history is DDL and constructed from safe sources, used only if dbDelta fails.
			$wpdb->query( $sql_history ); // Attempt direct creation if dbDelta might have missed it.
		}

		require_once AICB_PLUGIN_DIR . 'includes/class-aicb-embedding-manager.php';
		AICB_Embedding_Manager::create_table();
	}
} // End class AICB_Activator.
