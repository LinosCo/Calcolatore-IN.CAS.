<?php
/**
 * Plugin Name: AI Chatbot
 * Plugin URI: https://voler.ai/ai-per-knowledge-sharing/
 * Description: An AI-powered chatbot using OpenAI's API
 * Version: 1.0.0
 * Author: voler.ai
 * Author URI: https://voler.ai
 * License: GPLv2 or later
 * Text Domain: ai-chatbot
 * Domain Path: /languages
 */

/*
 * Copyright (c) 2024 voler.ai
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 */

if (!defined('ABSPATH')) {
    exit;
}

// Check for plugin conflict with the free version
add_action('admin_init', function() {
    include_once(ABSPATH . 'wp-admin/includes/plugin.php');
    if (is_plugin_active('voler-ai-chatbot/voler-ai-chatbot.php')) {
        add_action('admin_notices', function() {
            ?>
            <div class="error">
                <p>
                    <strong><?php esc_html_e('AI Chatbot Conflict', 'ai-chatbot'); ?></strong><br>
                    <?php esc_html_e('The AI Chatbot Pro plugin cannot be used at the same time as the free version (Voler AI Chatbot). Please deactivate the free version to use the Pro features.', 'ai-chatbot'); ?>
                </p>
            </div>
            <?php
        });
        deactivate_plugins(plugin_basename(__FILE__));
        if (isset($_GET['activate'])) {
            unset($_GET['activate']);
        }
    }
});

// Define plugin constants
define('AICB_VERSION', '1.0.0');
define('AICB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AICB_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load dependencies needed for Core initialization
require_once AICB_PLUGIN_DIR . 'includes/class-aicb-activator.php';
require_once AICB_PLUGIN_DIR . 'includes/class-aicb-core.php';

/**
 * The main function responsible for initializing the plugin.
 */
function aicb_run_plugin() {
    try {
        // Instantiate the Core class
        new AICB_Core(); // AICB_Core constructor handles its own hooks. save_lead hooks removed.
    } catch (Exception $e) {
        // Add admin notice about the error
        add_action('admin_notices', function() use ($e) {
            ?>
            <div class="notice notice-error is-dismissible">
                <p><?php esc_html_e('AI Chatbot plugin failed to initialize:', 'ai-chatbot'); ?> <?php echo esc_html($e->getMessage()); ?></p>
            </div>
            <?php
        });
    }
}

// Activation hook
register_activation_hook(__FILE__, array('AICB_Activator', 'activate'));

// Start the plugin initialization process
aicb_run_plugin();

/**
 * One-time routine to clean up corrupted theme data.
 *
 * This function runs once on init to delete a potentially corrupted
 * user meta key that causes a fatal error.
 *
 * @since 1.0.2
 */
function aicb_cleanup_corrupted_theme_data() {
    // Use a new flag to ensure this robust version runs.
    if (get_option('aicb_theme_data_cleaned_1_0_2')) {
        return;
    }

    $user_id = get_current_user_id();
    if ($user_id > 0) {
        // Overwrite with a valid empty JSON object instead of deleting.
        update_user_meta($user_id, 'wp_theme_json_data_user', '{}');
    }

    update_option('aicb_theme_data_cleaned_1_0_2', true);
}
add_action('init', 'aicb_cleanup_corrupted_theme_data');


// --- Removed AICB_Plugin class and related aicb_init function ---
// The AICB_Core class constructor now handles hooking its own init method.
// The AICB_Admin and AICB_Frontend classes handle their own action hooks
// within their respective constructors.
