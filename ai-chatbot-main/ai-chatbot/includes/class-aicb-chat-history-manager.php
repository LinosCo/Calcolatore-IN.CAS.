<?php
/**
 * AICB_Chat_History_Manager Class.
 *
 * This class is responsible for managing chat history, primarily saving messages to the database.
 *
 * @package    AI_Chatbot
 * @subpackage AI_Chatbot/includes
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Manages chat history operations.
 *
 * @since 1.0.0
 */
class AICB_Chat_History_Manager {

	/**
	 * Saves a chat message to the history table.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 *
	 * @param string      $thread_id        OpenAI thread ID.
	 * @param string      $sender           'user' or 'assistant'.
	 * @param string      $content          The message content.
	 * @param string|null $page_context_url Optional. URL of the page where the chat occurred. Default null.
	 * @param string|null $user_identifier  Optional. Anonymous user identifier. Default null.
	 * @return bool|int False on failure, number of rows inserted on success.
	 */
	public static function save_message( $thread_id, $sender, $content, $page_context_url = null, $user_identifier = null ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'aicb_chat_history';

		// Basic validation.
		if ( empty( $thread_id ) || empty( $sender ) || empty( $content ) ) {
			return false;
		}

		$data = array(
			'thread_id'        => sanitize_text_field( $thread_id ),
			'message_sender'   => sanitize_text_field( $sender ),
			'message_content'  => wp_kses_post( $content ), // Using wp_kses_post for content as it might contain some HTML from AI.
			'timestamp'        => current_time( 'mysql' ),
			'page_context_url' => $page_context_url ? esc_url_raw( $page_context_url ) : null,
			'user_identifier'  => $user_identifier ? sanitize_text_field( $user_identifier ) : null,
		);

		$formats = array(
			'%s', // thread_id.
			'%s', // message_sender.
			'%s', // message_content.
			'%s', // timestamp.
			'%s', // page_context_url.
			'%s', // user_identifier.
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$result = $wpdb->insert( $table_name, $data, $formats );

		if ( false === $result ) {
			return false;
		}

		return $result;
	}
} // End class AICB_Chat_History_Manager.
