<?php
/**
 * The frontend functionality of the plugin.
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    AI_Chatbot
 */

if (!defined('ABSPATH')) {
    exit;
}

class AICB_Frontend {
    private $plugin_name;
    private $version;
    private $options;

    /**
     * Initialize the class and set its properties.
     *
     * @param string $version The version of this plugin.
     */
    public function __construct($version) {
        // Ensure AICB_API is loaded
        require_once AICB_PLUGIN_DIR . 'includes/class-aicb-api.php';
        try {
            $this->version = $version;
            $this->plugin_name = 'ai-chatbot';
            $this->options = get_option('aicb_settings', array());

            // Register REST API endpoint for chat messages
            add_action('rest_api_init', function () {
                register_rest_route('ai-chatbot/v1', '/send-message', array(
                    'methods' => 'POST',
                    'callback' => array($this, 'handle_send_message'),
                    'permission_callback' => array($this, 'handle_send_message_permission_check'),
                ));
            });

            // Enqueue scripts and styles
            add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

            // Render chatbot in footer
            add_action('wp_footer', array($this, 'render_chatbot'));

            // Add dynamic color styles to head
            add_action('wp_head', array($this, 'output_dynamic_color_styles_in_head'));

            // Register AJAX handler for lead capture
            if (method_exists($this, 'handle_save_lead')) {
                add_action('wp_ajax_aicb_save_lead', array($this, 'handle_save_lead'));
                add_action('wp_ajax_nopriv_aicb_save_lead', array($this, 'handle_save_lead'));
            }

            // Add shortcode support
            add_shortcode('ai-chatbot', array($this, 'render_shortcode'));

        } catch (Exception $e) {
        }
    }

    public function handle_send_message_permission_check(WP_REST_Request $request) {
        $nonce = $request->get_header('X-WP-Nonce');
        if (!wp_verify_nonce($nonce, 'wp_rest')) {
            return new WP_Error('rest_forbidden', esc_html__('Invalid nonce.', 'ai-chatbot'), array('status' => 403));
        }
        return true;
    }

    /**
     * Register the stylesheets and JavaScript for the frontend.
     */
    public function enqueue_scripts() {
        // Check if the chatbot should be shown
        if (isset($this->options['show_chatbot']) && $this->options['show_chatbot'] === '0') {
            // If 'show_chatbot' is '0', only enqueue scripts if a shortcode is present on the page.
            // The shortcode might be used to display the chatbot even if the global setting is off.
            if (!$this->is_shortcode_present()) {
                return; // Do not enqueue if globally disabled and no shortcode
            }
            // If shortcode is present, proceed to enqueue, but JS will need to respect show_chatbot for global instances.
        }
        // Enqueue styles first
        wp_enqueue_style('dashicons');
        wp_enqueue_style(
            $this->plugin_name . '-frontend',
            AICB_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            $this->version
        );

        wp_enqueue_script(
            $this->plugin_name . '-frontend',
            AICB_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery', 'wp-i18n'), // Added 'wp-i18n'
            $this->version,
            true
        );

        // Get saved settings from WordPress options
        $settings = get_option('aicb_settings', array());

        // Define a default icon URL (adjust path if needed)
        $default_icon_url = AICB_PLUGIN_URL . 'assets/images/default-icon.png';

        $consent_screen_enabled = !isset($settings['enable_consent_screen']) || $settings['enable_consent_screen'] === '1';
        $needs_consent = (
            (isset($settings['privacy_policy_url']) && !empty($settings['privacy_policy_url']))
            || (isset($settings['enable_chat_history']) && $settings['enable_chat_history'] === '1')
        );
        $enable_consent = $consent_screen_enabled ? '1' : ($needs_consent ? '1' : '0');

        // Prepare parameters for JavaScript
        $params = array(
            'rest_url' => rest_url(),
            'rest_nonce' => wp_create_nonce('wp_rest'),
            'show_chatbot' => $this->options['show_chatbot'] ?? '1',
            'chatbot_name' => $settings['chatbot_name'] ?? __('AI Assistant', 'ai-chatbot'),
            'chatbot_icon' => $settings['chatbot_icon'] ?? $default_icon_url,
            'position' => $settings['position'] ?? 'bottom-right',
            'primary_color' => $settings['primary_color'] ?? '#007bff',
            'secondary_color' => $settings['secondary_color'] ?? '#6c757d',
            'enable_lead_capture' => $settings['enable_lead_capture'] ?? '0',
            'lead_intro_text' => $settings['lead_intro_text'] ?? __('Please provide your information to continue.', 'ai-chatbot'),
            'welcome_message' => $settings['welcome_message'] ?? __('Welcome! How can I help you today?', 'ai-chatbot'),
            'suggested_questions' => $settings['suggested_questions'] ?? array(
                __('What services do you offer?', 'ai-chatbot'),
                __('How can I get started?', 'ai-chatbot'),
                __('What are your business hours?', 'ai-chatbot'),
                __('Do you have any special offers?', 'ai-chatbot')
            ),
            'is_shortcode' => $this->is_shortcode_present(),
            'lead_message_threshold' => isset($settings['lead_message_threshold']) ? $settings['lead_message_threshold'] : 3,
            'lead_trigger_threshold' => isset($settings['lead_trigger_threshold']) ? $settings['lead_trigger_threshold'] : (isset($settings['lead_message_threshold']) ? $settings['lead_message_threshold'] : 3),
            'lead_trigger_words' => isset($settings['lead_trigger_words']) ? $settings['lead_trigger_words'] : '',
            'openai_processing_message' => $settings['openai_processing_message'] ?? __('Your messages are processed by OpenAI to provide responses.', 'ai-chatbot'),
            'lead_consent_label' => $settings['lead_consent_label'] ?? __('I agree to the [privacy policy] and the processing of my data.', 'ai-chatbot'),
            'privacy_policy_url' => $settings['privacy_policy_url'] ?? '', // General URL, used for lead form etc.
            'enable_chat_history' => $settings['enable_chat_history'] ?? '0',
            'enable_consent' => $enable_consent,
            'chat_history_consent_message' => $settings['chat_history_consent_message'] ?? __('To improve your experience and recall past conversations, we can store your chat history. Please consent below.', 'ai-chatbot'),
            'chat_history_consent_opt_in_label' => $settings['chat_history_consent_opt_in_label'] ?? __('I agree to the storage of my chat history.', 'ai-chatbot'),
            'lead_form_fields_config' => isset($settings['lead_form_fields']) ? $settings['lead_form_fields'] : array(
                'name' => array('enabled' => true, 'required' => true),
                'email' => array('enabled' => true, 'required' => true),
                'phone' => array('enabled' => true, 'required' => false),
                'company' => array('enabled' => false, 'required' => false),
                'message' => array('enabled' => false, 'required' => false)
            ),
            'current_language' => substr(get_locale(), 0, 2), // Added current language
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aicb_nonce'),
            'lead_consent_label' => isset( $settings['lead_consent_label'] ) ? wp_kses_post( $settings['lead_consent_label'] ) : __('I agree to the [privacy_policy_link].', 'ai-chatbot'),
            'lead_skip_button_label' => isset( $settings['lead_skip_button_label'] ) ? sanitize_text_field( $settings['lead_skip_button_label'] ) : __('Skip', 'ai-chatbot'),
            'lead_marketing_label' => isset( $settings['lead_marketing_opt_in_label'] ) ? wp_kses_post( $settings['lead_marketing_opt_in_label'] ) : __('I agree to receive marketing updates.', 'ai-chatbot'),
            'lead_marketing_required' => !empty($settings['lead_marketing_opt_in_required']) ? '1' : '0',
            'lead_marketing_enabled' => !empty($settings['lead_enable_marketing_opt_in']) ? '1' : '0',
            'privacy_consent_required_message' => __('Please agree to the privacy policy to continue.', 'ai-chatbot'),
            'marketing_consent_required_message' => __('Please accept the marketing consent to continue.', 'ai-chatbot'),
            'privacy_policy_link_label' => $settings['privacy_policy_link_label'] ?? __('privacy policy', 'ai-chatbot'),

            // Unified Consent Screen Texts
            'consent_screen_title' => $settings['consent_screen_title'] ?? __('Welcome!', 'ai-chatbot'),
            'consent_screen_intro' => $settings['consent_screen_intro'] ?? __('Before you start, please review our data usage policies.', 'ai-chatbot'),
            'consent_gdpr_explanation' => $settings['consent_gdpr_explanation'] ?? __('We use your information to provide and improve our services. By using the chat, you agree to our [privacy_policy_link].', 'ai-chatbot'), // Adjusted default text slightly
            'consent_history_explanation' => $settings['consent_history_explanation'] ?? __('To enhance your experience, we can remember your conversation. This is optional.', 'ai-chatbot'), // Adjusted default text
            'consent_footer_notice' => $settings['consent_footer_notice'] ?? __('You can change your preferences later in the settings.', 'ai-chatbot'),
            'consent_screen_enabled' => $consent_screen_enabled ? '1' : '0',

            // Z-index settings
            'toggle_z_index' => isset($settings['toggle_z_index']) ? intval($settings['toggle_z_index']) : 999,
            'chatbot_z_index' => isset($settings['chatbot_z_index']) ? intval($settings['chatbot_z_index']) : 998,
        );

        // Pass the parameters object to the frontend script
        wp_localize_script($this->plugin_name . '-frontend', 'aicb_params', $params);
        wp_set_script_translations($this->plugin_name . '-frontend', 'ai-chatbot', AICB_PLUGIN_DIR . 'languages');
    }

    public function render_chatbot() {
        // Do not render the global widget if it's disabled in settings.
        if (empty($this->options['show_chatbot'])) {
            return;
        }

        // Do not render the global widget on pages where the shortcode is used,
        // as the shortcode will handle its own rendering.
        if ($this->is_shortcode_present()) {
            return;
        }

        $template_path = AICB_PLUGIN_DIR . 'templates/chatbot.php';
        if (file_exists($template_path)) {
            include $template_path;
        }
    }

    /**
     * Outputs dynamic color styles into the <head> of the document.
     */
    public function output_dynamic_color_styles_in_head() {
        if (is_admin()) {
            return;
        }
        // Use $this->options as it's initialized in the constructor and should be up-to-date
        $primary_color = isset($this->options['primary_color']) && !empty($this->options['primary_color']) ? sanitize_hex_color($this->options['primary_color']) : '#007bff';
        $secondary_color = isset($this->options['secondary_color']) && !empty($this->options['secondary_color']) ? sanitize_hex_color($this->options['secondary_color']) : '#6c757d';

        echo "<style id='aicb-dynamic-colors'>:root { --aicb-primary-color: " . esc_attr($primary_color) . "; --aicb-secondary-color: " . esc_attr($secondary_color) . "; }</style>
";
    }

    /**
     * Shortcode handler for the chatbot.
     *
     * @param array $atts Shortcode attributes.
     * @return string The shortcode output.
     */
    public function render_shortcode($atts) {
        // Extract attributes
        $atts = shortcode_atts(array(
            'position' => '',
            'primary_color' => '',
            'secondary_color' => '',
            'name' => ''
        ), $atts, 'ai-chatbot');

        // Override options with shortcode attributes if provided
        $override_options = $this->options;

        if (!empty($atts['position'])) {
            $override_options['position'] = $atts['position'];
        }

        if (!empty($atts['primary_color'])) {
            $override_options['primary_color'] = $atts['primary_color'];
        }

        if (!empty($atts['secondary_color'])) {
            $override_options['secondary_color'] = $atts['secondary_color'];
        }

        if (!empty($atts['name'])) {
            $override_options['chatbot_name'] = $atts['name'];
        }

        // Start output buffering
        ob_start();

        // Temporarily override options
        $temp_options = $this->options;
        $this->options = $override_options;

        // Get position
        $position = isset($this->options['position']) ? esc_attr($this->options['position']) : 'bottom-right';

        // Include template
        $template_path = AICB_PLUGIN_DIR . 'templates/chatbot.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
        }

        // Restore original options
        $this->options = $temp_options;

        // Return captured output
        return ob_get_clean();
    }

    /**
     * REST API handler for sending messages to the OpenAI API (v2).
     */
    public function handle_send_message(?WP_REST_Request $request = null) {
        if ($this->check_rate_limit()) {
            return new WP_Error(
                'too_many_requests',
                esc_html__('You are sending messages too quickly. Please try again in a moment.', 'ai-chatbot'), // Localized message
                array('status' => 429)
            );
        }

        // Get parameters from REST request or fallback to $_POST
        $params = $request ? $request->get_params() : $_POST;
        $user_message_content = isset($params['message']) ? sanitize_textarea_field($params['message']) : '';
        $thread_id = isset($params['thread_id']) ? sanitize_text_field($params['thread_id']) : '';
        $page_context_full = isset($params['page_context']) ? json_decode(stripslashes($params['page_context']), true) : array();
        $page_context_url = null;
        if (isset($page_context_full['page_url'])) {
            $page_context_url = esc_url_raw($page_context_full['page_url']);
        } elseif (isset($params['page_url'])) {
            $page_context_url = esc_url_raw($params['page_url']);
        } elseif (isset($page_context_full['url'])) {
            $page_context_url = esc_url_raw($page_context_full['url']);
        }

        // Chat History related params
        $chat_history_consent_given = isset($params['chat_history_consent_given']) && $params['chat_history_consent_given'] === true;
        $user_identifier = isset($params['user_identifier']) ? sanitize_text_field($params['user_identifier']) : null;
        $language_code = isset($params['current_language']) ? sanitize_text_field($params['current_language']) : substr(get_locale(), 0, 2); // Added language code

        $settings = get_option('aicb_settings');
        $enable_chat_history = isset($settings['enable_chat_history']) && $settings['enable_chat_history'] === '1';

        if (empty($user_message_content)) {
            return new WP_Error('no_message', esc_html__('No message provided', 'ai-chatbot'), array('status' => 400));
        }

        // Initialize API instance earlier for potential moderation use
        $api = new AICB_API();

        // Content Moderation Check
        if (isset($settings['enable_content_moderation']) && $settings['enable_content_moderation'] === '1') {
            $moderation_result = $api->moderate_content($user_message_content);

            if (isset($moderation_result['error'])) {
                 // Fail open: Log error but proceed without blocking message if moderation API fails.
            } elseif (isset($moderation_result['flagged']) && $moderation_result['flagged'] === true) {
                $flagged_categories = array_keys(array_filter($moderation_result['categories']));
                return new WP_Error(
                    'message_flagged',
                    esc_html__('Your message could not be processed as it may violate content policy.', 'ai-chatbot'),
                    array('status' => 400)
                );
            }
        }

        // Require the chat history manager
        require_once AICB_PLUGIN_DIR . 'includes/class-aicb-chat-history-manager.php';

        try {
            // Save user message to history if consent given and feature enabled
            // Note: $api is already initialized above
            if ($enable_chat_history && $chat_history_consent_given && !empty($thread_id)) {
                AICB_Chat_History_Manager::save_message($thread_id, 'user', $user_message_content, $page_context_url, $user_identifier);
            }

            // Pass language_code to send_message_v2
            $api_response = $api->send_message_v2($user_message_content, $thread_id, $params['page_context'] /* Pass full JSON string context */, $language_code);

            if (is_array($api_response) && isset($api_response['error'])) {
                return new WP_Error('api_error', $api_response['error'], array('status' => 500));
            }

            if ($api_response) {
                $ai_message_content = '';
                $new_thread_id = $thread_id; // Assume existing thread_id unless new one is returned

                if (is_array($api_response) && isset($api_response['message'])) {
                    $ai_message_content = $api_response['message'];
                    if (isset($api_response['thread_id'])) {
                        $new_thread_id = $api_response['thread_id']; // OpenAI might return a new thread_id
                        if (empty($thread_id) && $enable_chat_history && $chat_history_consent_given) {
                             // If it was the first message and a new thread was created by API, save user message now with new_thread_id
                             AICB_Chat_History_Manager::save_message($new_thread_id, 'user', $user_message_content, $page_context_url, $user_identifier);
                        }
                    }
                } elseif (is_string($api_response)) {
                    $ai_message_content = $api_response;
                }

                // Save AI message to history if consent given and feature enabled
                if ($enable_chat_history && $chat_history_consent_given && !empty($new_thread_id) && !empty($ai_message_content)) {
                    AICB_Chat_History_Manager::save_message($new_thread_id, 'assistant', $ai_message_content, $page_context_url, $user_identifier);
                }

                $related_content = $this->get_related_content($user_message_content);
                $final_response_data = is_array($api_response) ? $api_response : array('message' => $api_response, 'thread_id' => $new_thread_id);
                if (!empty($related_content)) {
                    $final_response_data['related_content'] = $related_content;
                }
                $final_response_data['success'] = true;
                return rest_ensure_response($final_response_data);
            } else {
                return new WP_Error('no_response', esc_html__('No response from assistant', 'ai-chatbot'), array('status' => 500)); // Already localized
            }
        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                return new WP_Error('api_error', $e->getMessage(), array('status' => 500));
            } else {
                return new WP_Error('api_error', esc_html__('An error occurred while communicating with the AI assistant.', 'ai-chatbot'), array('status' => 500)); // Already localized
            }
        }
    }

    /**
     * Analyze message and get related content based on keywords.
     *
     * @param string $message The user's message.
     * @return array Array of related content items.
     */
    private function get_related_content($message) {
        // This is a simplified implementation - expand based on your needs
        $related_content = array();

        // Check if content fetching is enabled
        if (!isset($this->options['enable_content_suggestions']) || $this->options['enable_content_suggestions'] !== '1') {
            return $related_content;
        }

        // Convert message to lowercase for case-insensitive matching
        $message_lower = strtolower($message);

        // Extract keywords to search for related content
        $keywords = $this->extract_keywords($message_lower);

        if (empty($keywords)) {
            return $related_content;
        }

        // Knowledge base suggestions
        if (isset($this->options['enable_knowledge_base']) && $this->options['enable_knowledge_base'] === '1') {
            if (!class_exists('AICB_Knowledge_Base')) {
                require_once AICB_PLUGIN_DIR . 'includes/class-aicb-knowledge-base.php';
            }
            $kb = new AICB_Knowledge_Base();
            $kb_related = $kb->get_related_content($message, 3);
            foreach ($kb_related as $item) {
                $related_content[] = array(
                    'title' => $item['title'],
                    'description' => $item['excerpt'],
                    'url' => $item['url'],
                    'type' => 'knowledge',
                    'featured' => false,
                );
            }
        }

        // Get related WooCommerce products if WooCommerce is active
        if (function_exists('wc_get_products') && (!isset($this->options['suggest_products']) || $this->options['suggest_products'] === '1')) {
            $related_content = array_merge($related_content, $this->get_related_products($keywords));
        }

        // Get related posts
        if (!isset($this->options['suggest_posts']) || $this->options['suggest_posts'] === '1') {
            $related_content = array_merge($related_content, $this->get_related_posts($keywords));
        }

        // Limit results
        $max_items = isset($this->options['max_suggested_items']) ? intval($this->options['max_suggested_items']) : 6;
        $related_content = array_slice($related_content, 0, $max_items);

        return $related_content;
    }

    /**
     * Extract relevant keywords from a message.
     *
     * @param string $message The user's message.
     * @return array Array of keywords.
     */
    private function extract_keywords($message) {
        // Remove common words and extract potential keywords
        $stop_words = array('a', 'an', 'the', 'and', 'or', 'but', 'of', 'to', 'in', 'on', 'at', 'for', 'with', 'is', 'are', 'was', 'were');
        $words = preg_split('/\s+/', $message);

        $keywords = array();
        foreach ($words as $word) {
            $word = preg_replace('/[^\w\s]/u', '', $word);
            if (strlen($word) > 3 && !in_array($word, $stop_words)) {
                $keywords[] = $word;
            }
        }

        return $keywords;
    }

    /**
     * Get related WooCommerce products based on keywords.
     *
     * @param array $keywords Array of keywords.
     * @return array Array of product data.
     */
    private function get_related_products($keywords) {
        if (!function_exists('wc_get_products')) {
            return array();
        }

        $products = array();

        // Build search query
        $search_query = implode(' ', $keywords);

        // Query products
        $args = array(
            'limit' => 3,
            's' => $search_query,
            'status' => 'publish',
        );

        $found_products = wc_get_products($args);

        foreach ($found_products as $product) {
            $image_id = $product->get_image_id();
            $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'medium') : wc_placeholder_img_src('medium');

            $products[] = array(
                'title' => $product->get_name(),
                'description' => wp_trim_words($product->get_short_description(), 15, '...'),
                'url' => $product->get_permalink(),
                'image' => $image_url,
                'price' => $product->get_price_html(),
                'type' => 'product',
                'featured' => true
            );
        }

        return $products;
    }

    /**
     * Get related posts based on keywords.
     *
     * @param array $keywords Array of keywords.
     * @return array Array of post data.
     */
    private function get_related_posts($keywords) {
        $posts = array();

        // Build search query
        $search_query = implode(' ', $keywords);

        // Query posts
        $args = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            's' => $search_query,
            'posts_per_page' => 5
        );

        $query = new WP_Query($args);

        if ($query->have_posts()) {
            $featured = true; // First post gets featured status

            while ($query->have_posts()) {
                $query->the_post();

                $image_url = has_post_thumbnail() ? get_the_post_thumbnail_url(get_the_ID(), 'medium') : AICB_PLUGIN_URL . 'assets/images/default-post.png';

                $posts[] = array(
                    'title' => get_the_title(),
                    'description' => wp_trim_words(get_the_excerpt(), 15, '...'),
                    'url' => get_the_permalink(),
                    'image' => $image_url,
                    'type' => 'post',
                    'featured' => $featured
                );

                $featured = false; // Only first post is featured
            }

            wp_reset_postdata();
        }

        return $posts;
    }

    /**
     * Handle lead form submission
     */
    public function handle_save_lead() {
        // Verify nonce
        check_ajax_referer('aicb_nonce', '_ajax_nonce');

        // Get settings
        $settings = get_option('aicb_settings', array());
        $form_fields = isset($settings['lead_form_fields']) ? $settings['lead_form_fields'] : array();

        // Validate required fields
        $required_fields = array();
        foreach ($form_fields as $field => $config) {
            if (!empty($config['enabled']) && !empty($config['required'])) { // Check if keys exist and are true
                $required_fields[] = $field;
            }
        }

        $errors = array();
        foreach ($required_fields as $field) {
            if (!isset($_POST[$field]) || empty(wp_unslash($_POST[$field]))) {
                /* translators: %s: Lead form field label. */
                $errors[] = sprintf(esc_html__('%s is required.', 'ai-chatbot'), esc_html(ucfirst($field))); // Field name itself is not translated here, assuming 'Name', 'Email' are universal enough.
            }
        }

        // Validate email if enabled and provided
        // Ensure $form_fields['email'] and its subkeys 'enabled' exist before accessing
        if (isset($form_fields['email'], $form_fields['email']['enabled']) && $form_fields['email']['enabled'] &&
            isset($_POST['email']) && !empty(wp_unslash($_POST['email']))) {
            if (!is_email(wp_unslash($_POST['email']))) {
                $errors[] = esc_html__('Please enter a valid email address.', 'ai-chatbot'); // Already localized
            }
        }

        if (!empty($errors)) {
            wp_send_json_error(array('message' => implode(' ', $errors)));
            return;
        }

        // Prepare lead data
        $privacy_consent_value = isset($_POST['privacy']) && wp_unslash($_POST['privacy']) === 'true' ? 'given' : 'not_given';
        $consent_timestamp_value = $privacy_consent_value === 'given' ? current_time('mysql') : null;

        $lead_data = array(
            'name' => isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '',
            'email' => isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '',
            'phone' => isset($_POST['phone']) ? sanitize_text_field(wp_unslash($_POST['phone'])) : '',
            'company' => isset($_POST['company']) ? sanitize_text_field(wp_unslash($_POST['company'])) : '',
            'message' => isset($_POST['message']) ? sanitize_textarea_field(wp_unslash($_POST['message'])) : '',
            'page_url' => isset($_POST['page_url']) ? esc_url_raw(wp_unslash($_POST['page_url'])) : '',
            'created_at' => current_time('mysql'),
            'ip_address' => $this->get_client_ip(),
            'privacy_consent' => $privacy_consent_value,
            'consent_timestamp' => $consent_timestamp_value,
            'thread_id' => isset($_POST['thread_id']) ? sanitize_text_field(wp_unslash($_POST['thread_id'])) : null,
            'user_identifier' => isset($_POST['user_identifier']) ? sanitize_text_field(wp_unslash($_POST['user_identifier'])) : null,
        );

        // Save lead to database
        global $wpdb;
        $table_name = $wpdb->prefix . 'aicb_leads';

        $result = $wpdb->insert(
            $table_name,
            $lead_data,
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s') // Added format specifiers for new columns
        );

        if ($result === false) {
            wp_send_json_error(array('message' => esc_html__('Failed to save lead information.', 'ai-chatbot'))); // Already localized
            return;
        }

        // Send notification email
        $this->send_lead_notification($lead_data);

        wp_send_json_success(array('message' => esc_html__('Thank you! Your information has been submitted.', 'ai-chatbot'))); // Already localized
    }

    /**
     * Send notification email for new leads
     */
    private function send_lead_notification($lead_data) {
        $settings = get_option('aicb_settings', array());
        $notification_email = isset($settings['lead_notification_email']) ?
            $settings['lead_notification_email'] : get_option('admin_email');

        /* translators: %s: Site name. */
        $subject = sprintf(esc_html__('New Lead from %s', 'ai-chatbot'), get_bloginfo('name')); // Already localized

        // The main message body with placeholders
        $message_format =
            /* translators: %s: Lead's name */
            esc_html__("New lead captured from the chatbot:", 'ai-chatbot') . "\n\n" .
            /* translators: %s: Lead name. */ esc_html__("Name: %s", 'ai-chatbot') . "\n" .
            /* translators: %s: Lead email. */ esc_html__("Email: %s", 'ai-chatbot') . "\n" .
            /* translators: %s: Lead phone number. */ esc_html__("Phone: %s", 'ai-chatbot') . "\n" .
            /* translators: %s: Lead company. */ esc_html__("Company: %s", 'ai-chatbot') . "\n" .
            /* translators: %s: Lead message. */ esc_html__("Message: %s", 'ai-chatbot') . "\n" .
            /* translators: %s: Page URL. */ esc_html__("Page URL: %s", 'ai-chatbot') . "\n" .
            /* translators: %s: Date of capture. */ esc_html__("Date: %s", 'ai-chatbot') . "\n" .
            /* translators: %s: IP address. */ esc_html__("IP Address: %s", 'ai-chatbot') . "\n" .
            /* translators: %s: Privacy consent status. */ esc_html__("Privacy Consent: %s", 'ai-chatbot') . "\n" .
            /* translators: %s: Consent timestamp. */ esc_html__("Consent Timestamp: %s", 'ai-chatbot') . "\n" .
            /* translators: %s: Thread ID. */ esc_html__("Thread ID: %s", 'ai-chatbot') . "\n" .
            /* translators: %s: User identifier. */ esc_html__("User Identifier: %s", 'ai-chatbot');

        $message = sprintf(
            $message_format,
            $lead_data['name'],
            $lead_data['email'],
            $lead_data['phone'],
            $lead_data['company'],
            $lead_data['message'],
            $lead_data['page_url'],
            $lead_data['created_at'],
            $lead_data['ip_address'],
            $lead_data['privacy_consent'],
            $lead_data['consent_timestamp'],
            isset($lead_data['thread_id']) ? $lead_data['thread_id'] : 'N/A',
            isset($lead_data['user_identifier']) ? $lead_data['user_identifier'] : 'N/A'
        );

        $headers = array('Content-Type: text/plain; charset=UTF-8');

        wp_mail($notification_email, $subject, $message, $headers);
    }

    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip = '';
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_CLIENT_IP']));
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR']));
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
        }
        return $ip;
    }

    /**
     * Render lead form HTML
     */
    private function render_lead_form() {
        $settings = get_option('aicb_settings', array());
        $form_fields = isset($settings['lead_form_fields']) ? $settings['lead_form_fields'] : array(
            'name' => array('required' => true, 'enabled' => true),
            'email' => array('required' => true, 'enabled' => true),
            'phone' => array('required' => false, 'enabled' => true),
            'company' => array('required' => false, 'enabled' => false),
            'message' => array('required' => false, 'enabled' => false)
        );

        ob_start();
        ?>
        <div class="aicb-lead-form">
            <div class="aicb-lead-intro">
                <p><?php echo esc_html($settings['lead_intro_text'] ?? 'Please provide your information to continue.'); ?></p>
            </div>
            <form id="aicb-lead-form-element">
                <?php foreach ($form_fields as $field => $config): ?>
                    <?php if ($config['enabled']): ?>
                        <div class="aicb-form-field">
                            <label for="aicb-lead-<?php echo esc_attr($field); ?>">
                                <?php echo esc_html(ucfirst($field)); ?>
                                <?php if ($config['required']): ?>
                                    <span class="required">*</span>
                                <?php endif; ?>
                            </label>
                            <?php if ($field === 'message'): ?>
                                <textarea id="aicb-lead-<?php echo esc_attr($field); ?>"
                                          name="<?php echo esc_attr($field); ?>"
                                          <?php echo $config['required'] ? 'required' : ''; ?>></textarea>
                            <?php else: ?>
                                <input type="<?php echo $field === 'email' ? 'email' : 'text'; ?>"
                                       id="aicb-lead-<?php echo esc_attr($field); ?>"
                                       name="<?php echo esc_attr($field); ?>"
                                       <?php echo $config['required'] ? 'required' : ''; ?>>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>

                <div class="aicb-privacy-check">
                    <label>
                        <input type="checkbox" id="aicb-privacy" required>
                        <?php esc_html_e('I agree to the privacy policy', 'ai-chatbot'); ?>
                    </label>
                </div>

                <div class="aicb-form-buttons">
                    <button type="submit" id="aicb-lead-submit" class="aicb-button">
                        <?php esc_html_e('Submit', 'ai-chatbot'); ?>
                    </button>
                    <button type="button" class="aicb-skip-lead aicb-button">
                        <?php esc_html_e('Skip', 'ai-chatbot'); ?>
                    </button>
                </div>

                <div id="aicb-lead-form-messages"></div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Check if the shortcode is present in the current page content
     *
     * @return boolean
     */
    private function is_shortcode_present() {
        global $post;
        if (is_a($post, 'WP_Post')) {
            return has_shortcode($post->post_content, 'ai-chatbot');
        }
        return false;
    }

    private function check_rate_limit() {
        $options = get_option('aicb_settings');
        if (empty($options['enable_rate_limiting'])) {
            return false;
        }

        $limit_count = isset($options['rate_limit_requests']) ? intval($options['rate_limit_requests']) : 20;
        $limit_window = isset($options['rate_limit_window']) ? intval($options['rate_limit_window']) : 60;

        if ($limit_count <= 0 || $limit_window <= 0) {
            return false; // Invalid settings, don't limit
        }

        $ip_address = $this->get_client_ip();
        if (empty($ip_address)) {
            // Cannot get IP, or IP is intentionally excluded (e.g. localhost loopback if configured so)
            // Decide if this scenario should bypass limiting or be logged/handled. For now, bypass.
            return false;
        }

        $transient_key = 'aicb_rl_' . md5($ip_address);
        $requests = get_transient($transient_key);
        if (false === $requests) {
            $requests = array();
        }

        $current_time = time();
        // Filter out old timestamps
        $requests = array_filter($requests, function($timestamp) use ($current_time, $limit_window) {
            return ($current_time - $timestamp) < ($limit_window * 60);
        });

        if (count($requests) >= $limit_count) {
            // Optionally, log the rate limit hit
            return true; // Limit exceeded
        }

        // Add current request and update transient
        $requests[] = $current_time;
        set_transient($transient_key, $requests, $limit_window * 60); // Transient expires after the window

        return false; // Not limited
    }
}
