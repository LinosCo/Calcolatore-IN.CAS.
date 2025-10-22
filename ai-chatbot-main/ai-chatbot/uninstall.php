<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Remove options
// delete_option('aicb_openai_api_key'); // Part of aicb_settings
// delete_option('aicb_assistant_id'); // Part of aicb_settings
// delete_option('aicb_primary_color'); // Part of aicb_settings
// delete_option('aicb_secondary_color'); // Part of aicb_settings
// delete_option('aicb_widget_position'); // Part of aicb_settings
// delete_option('aicb_logo'); // Part of aicb_settings
// delete_option('aicb_notification_email'); // Part of aicb_settings
delete_option('aicb_settings'); // Removes the main settings array

// Remove database tables
global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS " . $wpdb->prefix . "aicb_leads" );
$wpdb->query( "DROP TABLE IF EXISTS " . $wpdb->prefix . "aicb_chat_history" );