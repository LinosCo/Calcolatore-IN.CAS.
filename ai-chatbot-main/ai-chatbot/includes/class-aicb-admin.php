<?php
/**
 * Admin functionality for AI Chatbot
 * 
 * @package AI_Chatbot
 */

if (!defined('ABSPATH')) {
    exit;
}

class AICB_Admin {
    
    private $plugin_name;
    private $version;
    private $options;
    
    public function __construct($plugin_name = 'ai-chatbot', $version = '1.0.0') {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->options = get_option('aicb_settings', array());
    }
    
    public function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_aicb_test_connection', array($this, 'test_openai_connection'));
        add_action('wp_ajax_aicb_reindex_content', array($this, 'reindex_content'));
        add_action('wp_ajax_aicb_upload_knowledge_file', array($this, 'upload_knowledge_file'));
        add_action('wp_ajax_aicb_remove_knowledge_file', array($this, 'remove_knowledge_file'));
        add_action('wp_ajax_aicb_get_theme_templates', array($this, 'get_theme_templates'));
        add_action('admin_post_aicb_export_leads', array($this, 'export_leads_csv'));
        add_action('admin_post_aicb_export_history', array($this, 'export_history_csv'));

        // Load list table classes on admin init to ensure WP_List_Table is available
        add_action('admin_init', array($this, 'load_list_tables'));
    }

    /**
     * Loads the WP_List_Table dependent classes.
     */
    public function load_list_tables() {
        require_once AICB_PLUGIN_DIR . 'includes/class-aicb-leads-table.php';
        require_once AICB_PLUGIN_DIR . 'includes/class-aicb-chat-history-table.php';
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'aicb') === false) {
            return;
        }
        
        // Enqueue WordPress media uploader
        wp_enqueue_media();
        
        // Color picker
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        
        // Admin styles
        wp_enqueue_style(
            $this->plugin_name . '-admin',
            AICB_PLUGIN_URL . 'assets/css/aicb-admin.css',
            array(),
            $this->version
        );
        
        // Admin scripts
        wp_enqueue_script(
            $this->plugin_name . '-admin',
            AICB_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-color-picker'),
            $this->version,
            true
        );
        
        wp_localize_script($this->plugin_name . '-admin', 'aicb_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('aicb_admin_nonce'),
            'strings' => array(
                'test_connection' => __('Testing connection...', 'ai-chatbot'),
                'connection_success' => __('Connection successful!', 'ai-chatbot'),
                'connection_failed' => __('Connection failed:', 'ai-chatbot'),
                'reindexing' => __('Reindexing content...', 'ai-chatbot'),
                'reindex_complete' => __('Content reindexed successfully!', 'ai-chatbot'),
                'confirm_remove_file' => __('Are you sure you want to remove this file from the knowledge base?', 'ai-chatbot')
            )
        ));
    }
    
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            __('AI Chatbot', 'ai-chatbot'),
            __('AI Chatbot', 'ai-chatbot'),
            'manage_options',
            'aicb-settings',
            array($this, 'render_settings_page'),
            'dashicons-format-chat',
            80
        );
        
        // Settings submenu
        add_submenu_page(
            'aicb-settings',
            __('Settings', 'ai-chatbot'),
            __('Settings', 'ai-chatbot'),
            'manage_options',
            'aicb-settings'
        );
        
        // Leads submenu
        add_submenu_page(
            'aicb-settings',
            __('Leads', 'ai-chatbot'),
            __('Leads', 'ai-chatbot'),
            'manage_options',
            'aicb-leads',
            array($this, 'render_leads_page')
        );

        // Chat History submenu
        add_submenu_page(
            'aicb-settings',
            __('Chat History', 'ai-chatbot'),
            __('Chat History', 'ai-chatbot'),
            'manage_options',
            'aicb-history',
            array($this, 'render_history_page')
        );

        // Analytics submenu
        add_submenu_page(
            'aicb-settings',
            __('Analytics', 'ai-chatbot'),
            __('Analytics', 'ai-chatbot'),
            'manage_options',
            'aicb-analytics',
            array($this, 'render_analytics_page')
        );
    }
    
    public function register_settings() {
        register_setting('aicb_settings', 'aicb_settings', array($this, 'sanitize_settings'));
        
        // 1. General Settings Section
        add_settings_section(
            'aicb_general_section',
            __('General Settings', 'ai-chatbot'),
            array($this, 'render_general_section'),
            'aicb-settings-general'
        );
        
        $this->add_general_fields();
        
        // 2. Appearance Section
        add_settings_section(
            'aicb_appearance_section',
            __('Appearance', 'ai-chatbot'),
            array($this, 'render_appearance_section'),
            'aicb-settings-appearance'
        );
        
        $this->add_appearance_fields();
        
        // 3. OpenAI & Model Section
        add_settings_section(
            'aicb_openai_section',
            __('OpenAI Configuration', 'ai-chatbot'),
            array($this, 'render_openai_section'),
            'aicb-settings-openai'
        );
        
        $this->add_openai_fields();
        
        // 4. Knowledge Base Section
        add_settings_section(
            'aicb_knowledge_section',
            __('Knowledge Base (RAG)', 'ai-chatbot'),
            array($this, 'render_knowledge_section'),
            'aicb-settings-knowledge'
        );
        
        $this->add_knowledge_fields();
        
        // 5. Lead Generation Section
        add_settings_section(
            'aicb_lead_section',
            __('Lead Generation', 'ai-chatbot'),
            array($this, 'render_lead_section'),
            'aicb-settings-lead'
        );
        
        $this->add_lead_fields();
        
        // 6. Messages & Labels Section
        add_settings_section(
            'aicb_messages_section',
            __('Messages & Labels', 'ai-chatbot'),
            array($this, 'render_messages_section'),
            'aicb-settings-messages'
        );
        
        $this->add_messages_fields();
        
        // 7. Security & Privacy Section
        add_settings_section(
            'aicb_security_section',
            __('Security & Privacy', 'ai-chatbot'),
            array($this, 'render_security_section'),
            'aicb-settings-security'
        );
        
        $this->add_security_fields();
    }
    
    private function add_general_fields() {
        add_settings_field(
            'openai_api_key',
            __('OpenAI API Key', 'ai-chatbot'),
            array($this, 'render_password_field'),
            'aicb-settings-general',
            'aicb_general_section',
            array(
                'name' => 'openai_api_key',
                'description' => __('Your OpenAI API key for connecting to the service.', 'ai-chatbot')
            )
        );
        
        add_settings_field(
            'chatbot_name',
            __('Chatbot Name', 'ai-chatbot'),
            array($this, 'render_text_field'),
            'aicb-settings-general',
            'aicb_general_section',
            array(
                'name' => 'chatbot_name',
                'default' => 'AI Assistant',
                'description' => __('The name displayed in the chat header.', 'ai-chatbot')
            )
        );
        
        add_settings_field(
            'show_chatbot',
            __('Show Chatbot on Website', 'ai-chatbot'),
            array($this, 'render_checkbox_field'),
            'aicb-settings-general',
            'aicb_general_section',
            array(
                'name' => 'show_chatbot',
                'label' => __('Enable chatbot globally', 'ai-chatbot'),
                'description' => __('Master switch to enable or disable the chatbot on your website.', 'ai-chatbot')
            )
        );
        
        add_settings_field(
            'test_connection',
            __('Test Connection', 'ai-chatbot'),
            array($this, 'render_test_connection'),
            'aicb-settings-general',
            'aicb_general_section'
        );
    }
    
    private function add_appearance_fields() {
        add_settings_field(
            'chatbot_icon',
            __('Chatbot Icon', 'ai-chatbot'),
            array($this, 'render_media_upload_field'),
            'aicb-settings-appearance',
            'aicb_appearance_section',
            array(
                'name' => 'chatbot_icon',
                'description' => __('Upload an icon for the chatbot header.', 'ai-chatbot')
            )
        );
        
        add_settings_field(
            'position',
            __('Position', 'ai-chatbot'),
            array($this, 'render_select_field'),
            'aicb-settings-appearance',
            'aicb_appearance_section',
            array(
                'name' => 'position',
                'options' => array(
                    'bottom-right' => __('Bottom Right', 'ai-chatbot'),
                    'bottom-left' => __('Bottom Left', 'ai-chatbot'),
                    'top-right' => __('Top Right', 'ai-chatbot'),
                    'top-left' => __('Top Left', 'ai-chatbot')
                ),
                'default' => 'bottom-right',
                'description' => __('Position of the chatbot widget on your website.', 'ai-chatbot')
            )
        );
        
        add_settings_field(
            'primary_color',
            __('Primary Color', 'ai-chatbot'),
            array($this, 'render_color_picker_field'),
            'aicb-settings-appearance',
            'aicb_appearance_section',
            array(
                'name' => 'primary_color',
                'default' => '#007bff',
                'description' => __('Primary color for the header and user messages.', 'ai-chatbot')
            )
        );
        
        add_settings_field(
            'secondary_color',
            __('Secondary Color', 'ai-chatbot'),
            array($this, 'render_color_picker_field'),
            'aicb-settings-appearance',
            'aicb_appearance_section',
            array(
                'name' => 'secondary_color',
                'default' => '#6c757d',
                'description' => __('Secondary color for bot messages and accents.', 'ai-chatbot')
            )
        );
        
        add_settings_field(
            'chatbot_z_index',
            __('Chat Window Z-Index', 'ai-chatbot'),
            array($this, 'render_number_field'),
            'aicb-settings-appearance',
            'aicb_appearance_section',
            array(
                'name' => 'chatbot_z_index',
                'default' => 998,
                'description' => __('Z-index for the chat window to resolve theme conflicts.', 'ai-chatbot')
            )
        );
        
        add_settings_field(
            'toggle_z_index',
            __('Toggle Button Z-Index', 'ai-chatbot'),
            array($this, 'render_number_field'),
            'aicb-settings-appearance',
            'aicb_appearance_section',
            array(
                'name' => 'toggle_z_index',
                'default' => 999,
                'description' => __('Z-index for the toggle button.', 'ai-chatbot')
            )
        );

        add_settings_field(
            'grid_layout_template',
            __('Content Grid Layout', 'ai-chatbot'),
            array($this, 'render_template_selector'),
            'aicb-settings-appearance',
            'aicb_appearance_section',
            array(
                'name' => 'grid_layout_template',
                'description' => __('Select a template for displaying related content grids.', 'ai-chatbot')
            )
        );
    }
    
    private function add_openai_fields() {
        add_settings_field(
            'prompt_mode',
            __('Prompt Mode', 'ai-chatbot'),
            array($this, 'render_radio_field'),
            'aicb-settings-openai',
            'aicb_openai_section',
            array(
                'name' => 'prompt_mode',
                'options' => array(
                    'simple' => __('Simple Mode - Use custom instructions', 'ai-chatbot'),
                    'advanced' => __('Advanced Mode - Use Assistant ID', 'ai-chatbot')
                ),
                'default' => 'simple',
                'description' => __('Choose how to configure your AI assistant.', 'ai-chatbot')
            )
        );
        
        add_settings_field(
            'system_prompt',
            __('System Instructions', 'ai-chatbot'),
            array($this, 'render_textarea_field'),
            'aicb-settings-openai',
            'aicb_openai_section',
            array(
                'name' => 'system_prompt',
                'rows' => 10,
                'default' => 'You are a helpful assistant for our website. Provide accurate, friendly, and professional responses.',
                'description' => __('Instructions for the AI assistant (Simple Mode).', 'ai-chatbot'),
                'class' => 'simple-mode-field'
            )
        );
        
        add_settings_field(
            'assistant_id',
            __('Assistant ID', 'ai-chatbot'),
            array($this, 'render_text_field'),
            'aicb-settings-openai',
            'aicb_openai_section',
            array(
                'name' => 'assistant_id',
                'placeholder' => 'asst_...',
                'description' => __('OpenAI Assistant ID (Advanced Mode).', 'ai-chatbot'),
                'class' => 'advanced-mode-field'
            )
        );
        
        add_settings_field(
            'model',
            __('Model', 'ai-chatbot'),
            array($this, 'render_select_field'),
            'aicb-settings-openai',
            'aicb_openai_section',
            array(
                'name' => 'model',
                'options' => array(
                    'gpt-4o' => 'GPT-4o (Recommended)',
                    'gpt-4-turbo' => 'GPT-4 Turbo',
                    'gpt-4' => 'GPT-4',
                    'gpt-3.5-turbo' => 'GPT-3.5 Turbo'
                ),
                'default' => 'gpt-4o',
                'description' => __('Select the OpenAI model to use.', 'ai-chatbot')
            )
        );
        
        add_settings_field(
            'max_tokens',
            __('Max Response Tokens', 'ai-chatbot'),
            array($this, 'render_number_field'),
            'aicb-settings-openai',
            'aicb_openai_section',
            array(
                'name' => 'max_tokens',
                'default' => 500,
                'min' => 50,
                'max' => 4000,
                'description' => __('Maximum number of tokens for AI responses.', 'ai-chatbot')
            )
        );
    }
    
    
    private function add_knowledge_fields() {
        add_settings_field(
            'enable_knowledge_base',
            __('Enable Local Knowledge Base', 'ai-chatbot'),
            array($this, 'render_checkbox_field'),
            'aicb-settings-knowledge',
            'aicb_knowledge_section',
            array(
                'name' => 'enable_knowledge_base',
                'label' => __('Enable RAG (Retrieval-Augmented Generation)', 'ai-chatbot'),
                'description' => __('Use local content to enhance AI responses.', 'ai-chatbot')
            )
        );
        
        add_settings_field(
            'content_sources',
            __('WordPress Content Sources', 'ai-chatbot'),
            array($this, 'render_content_sources'),
            'aicb-settings-knowledge',
            'aicb_knowledge_section'
        );
        
        add_settings_field(
            'knowledge_files',
            __('Knowledge Files', 'ai-chatbot'),
            array($this, 'render_knowledge_files'),
            'aicb-settings-knowledge',
            'aicb_knowledge_section'
        );
        
        add_settings_field(
            'content_categories',
            __('Content Categorization', 'ai-chatbot'),
            array($this, 'render_content_categories'),
            'aicb-settings-knowledge',
            'aicb_knowledge_section'
        );
        
        add_settings_field(
            'reindex_content',
            __('Manage Index', 'ai-chatbot'),
            array($this, 'render_reindex_button'),
            'aicb-settings-knowledge',
            'aicb_knowledge_section'
        );
    }
    
    private function add_lead_fields() {
        add_settings_field(
            'enable_lead_capture',
            __('Enable Lead Capture', 'ai-chatbot'),
            array($this, 'render_checkbox_field'),
            'aicb-settings-lead',
            'aicb_lead_section',
            array(
                'name' => 'enable_lead_capture',
                'label' => __('Activate lead generation feature', 'ai-chatbot'),
                'description' => __('Collect visitor information during chat sessions.', 'ai-chatbot')
            )
        );
        
        add_settings_field(
            'lead_intro_text',
            __('Lead Form Introduction', 'ai-chatbot'),
            array($this, 'render_text_field'),
            'aicb-settings-lead',
            'aicb_lead_section',
            array(
                'name' => 'lead_intro_text',
                'default' => 'Please provide your contact information to continue.',
                'description' => __('Text displayed above the lead form.', 'ai-chatbot')
            )
        );
        
        add_settings_field(
            'lead_trigger_threshold',
            __('Trigger After Messages', 'ai-chatbot'),
            array($this, 'render_number_field'),
            'aicb-settings-lead',
            'aicb_lead_section',
            array(
                'name' => 'lead_trigger_threshold',
                'default' => 3,
                'min' => 1,
                'max' => 20,
                'description' => __('Show lead form after this many messages.', 'ai-chatbot')
            )
        );
        
        add_settings_field(
            'lead_trigger_words',
            __('Trigger Words', 'ai-chatbot'),
            array($this, 'render_textarea_field'),
            'aicb-settings-lead',
            'aicb_lead_section',
            array(
                'name' => 'lead_trigger_words',
                'rows' => 3,
                'placeholder' => 'pricing, quote, contact, demo',
                'description' => __('Comma-separated keywords that trigger the lead form.', 'ai-chatbot')
            )
        );

        add_settings_field(
            'lead_skip_button_label',
            __('Skip Button Label', 'ai-chatbot'),
            array($this, 'render_text_field'),
            'aicb-settings-lead',
            'aicb_lead_section',
            array(
                'name' => 'lead_skip_button_label',
                'default' => __('Skip', 'ai-chatbot'),
                'description' => __('Text shown on the “Skip” button inside the lead form.', 'ai-chatbot')
            )
        );

        add_settings_field(
            'lead_form_fields',
            __('Form Fields', 'ai-chatbot'),
            array($this, 'render_lead_form_fields'),
            'aicb-settings-lead',
            'aicb_lead_section'
        );
        
        add_settings_field(
            'lead_notification_email',
            __('Notification Email', 'ai-chatbot'),
            array($this, 'render_email_field'),
            'aicb-settings-lead',
            'aicb_lead_section',
            array(
                'name' => 'lead_notification_email',
                'default' => get_option('admin_email'),
                'description' => __('Email address for lead notifications.', 'ai-chatbot')
            )
        );

        add_settings_field(
            'lead_enable_marketing_opt_in',
            __('Marketing Opt-in Checkbox', 'ai-chatbot'),
            array($this, 'render_checkbox_field'),
            'aicb-settings-lead',
            'aicb_lead_section',
            array(
                'name' => 'lead_enable_marketing_opt_in',
                'label' => __('Display marketing consent checkbox in the lead form.', 'ai-chatbot')
            )
        );

        add_settings_field(
            'lead_marketing_opt_in_required',
            __('Marketing Opt-in Required', 'ai-chatbot'),
            array($this, 'render_checkbox_field'),
            'aicb-settings-lead',
            'aicb_lead_section',
            array(
                'name' => 'lead_marketing_opt_in_required',
                'label' => __('Require marketing consent before submitting the lead form.', 'ai-chatbot')
            )
        );

        add_settings_field(
            'lead_marketing_opt_in_label',
            __('Marketing Opt-in Label', 'ai-chatbot'),
            array($this, 'render_textarea_field'),
            'aicb-settings-lead',
            'aicb_lead_section',
            array(
                'name' => 'lead_marketing_opt_in_label',
                'rows' => 3,
                'default' => __('I agree to receive marketing updates.', 'ai-chatbot'),
                'description' => __('Text displayed next to the marketing consent checkbox. Accepts the same placeholder as the privacy label.', 'ai-chatbot')
            )
        );
    }
    
    private function add_messages_fields() {
        add_settings_field(
            'welcome_message',
            __('Welcome Message', 'ai-chatbot'),
            array($this, 'render_textarea_field'),
            'aicb-settings-messages',
            'aicb_messages_section',
            array(
                'name' => 'welcome_message',
                'rows' => 3,
                'default' => 'Welcome! How can I help you today?',
                'description' => __('First message users see when opening the chat.', 'ai-chatbot')
            )
        );
        
        add_settings_field(
            'suggested_questions',
            __('Suggested Questions', 'ai-chatbot'),
            array($this, 'render_repeater_field'),
            'aicb-settings-messages',
            'aicb_messages_section',
            array(
                'name' => 'suggested_questions',
                'label' => __('Add Question', 'ai-chatbot'),
                'default' => array(
                    'What services do you offer?',
                    'How can I get started?',
                    'What are your business hours?'
                )
            )
        );

        add_settings_field(
            'enable_consent_screen',
            __('Display Consent Screen', 'ai-chatbot'),
            array($this, 'render_checkbox_field'),
            'aicb-settings-messages',
            'aicb_messages_section',
            array(
                'name' => 'enable_consent_screen',
                'label' => __('Show the welcome/consent screen before the first message.', 'ai-chatbot'),
                'default' => '1'
            )
        );
        
        add_settings_field(
            'consent_screen_title',
            __('Consent Screen Title', 'ai-chatbot'),
            array($this, 'render_text_field'),
            'aicb-settings-messages',
            'aicb_messages_section',
            array(
                'name' => 'consent_screen_title',
                'default' => 'Welcome to our Chat Assistant',
                'description' => __('Title for the consent screen.', 'ai-chatbot')
            )
        );
        
        add_settings_field(
            'consent_screen_intro',
            __('Consent Screen Introduction', 'ai-chatbot'),
            array($this, 'render_textarea_field'),
            'aicb-settings-messages',
            'aicb_messages_section',
            array(
                'name' => 'consent_screen_intro',
                'rows' => 3,
                'default' => 'Before we begin, please review our data usage policies.',
                'description' => __('Introduction text for the consent screen.', 'ai-chatbot')
            )
        );
    }
    
    private function add_security_fields() {
        add_settings_field(
            'enable_rate_limiting',
            __('Enable Rate Limiting', 'ai-chatbot'),
            array($this, 'render_checkbox_field'),
            'aicb-settings-security',
            'aicb_security_section',
            array(
                'name' => 'enable_rate_limiting',
                'label' => __('Protect against abuse', 'ai-chatbot'),
                'description' => __('Limit the number of requests per user.', 'ai-chatbot')
            )
        );
        
        add_settings_field(
            'rate_limit_requests',
            __('Max Requests', 'ai-chatbot'),
            array($this, 'render_number_field'),
            'aicb-settings-security',
            'aicb_security_section',
            array(
                'name' => 'rate_limit_requests',
                'default' => 20,
                'min' => 5,
                'max' => 100,
                'description' => __('Maximum requests per time window.', 'ai-chatbot')
            )
        );
        
        add_settings_field(
            'rate_limit_window',
            __('Time Window (minutes)', 'ai-chatbot'),
            array($this, 'render_number_field'),
            'aicb-settings-security',
            'aicb_security_section',
            array(
                'name' => 'rate_limit_window',
                'default' => 60,
                'min' => 5,
                'max' => 1440,
                'description' => __('Time window for rate limiting.', 'ai-chatbot')
            )
        );
        
        add_settings_field(
            'enable_content_moderation',
            __('Enable Content Moderation', 'ai-chatbot'),
            array($this, 'render_checkbox_field'),
            'aicb-settings-security',
            'aicb_security_section',
            array(
                'name' => 'enable_content_moderation',
                'label' => __('Use OpenAI content moderation', 'ai-chatbot'),
                'description' => __('Filter inappropriate content in conversations.', 'ai-chatbot')
            )
        );
        
        add_settings_field(
            'privacy_policy_url',
            __('Privacy Policy URL', 'ai-chatbot'),
            array($this, 'render_url_field'),
            'aicb-settings-security',
            'aicb_security_section',
            array(
                'name' => 'privacy_policy_url',
                'default' => get_privacy_policy_url(),
                'description' => __('URL for privacy policy links.', 'ai-chatbot')
            )
        );
    }
    
    // Render methods for different field types
    
    public function render_text_field($args) {
        $name = $args['name'];
        $value = isset($this->options[$name]) ? $this->options[$name] : ($args['default'] ?? '');
        $class = isset($args['class']) ? $args['class'] : '';
        $placeholder = isset($args['placeholder']) ? $args['placeholder'] : '';
        ?>
        <input type="text" 
               name="aicb_settings[<?php echo esc_attr($name); ?>]" 
               value="<?php echo esc_attr($value); ?>"
               class="regular-text <?php echo esc_attr($class); ?>"
               placeholder="<?php echo esc_attr($placeholder); ?>" />
        <?php if (isset($args['description'])): ?>
            <p class="description"><?php echo esc_html($args['description']); ?></p>
        <?php endif;
    }
    
    public function render_password_field($args) {
        $name = $args['name'];
        $value = isset($this->options[$name]) ? $this->options[$name] : '';
        ?>
        <input type="password" 
               name="aicb_settings[<?php echo esc_attr($name); ?>]" 
               value="<?php echo esc_attr($value); ?>"
               class="regular-text" />
        <?php if (isset($args['description'])): ?>
            <p class="description"><?php echo esc_html($args['description']); ?></p>
        <?php endif;
    }
    
    public function render_textarea_field($args) {
        $name = $args['name'];
        $value = isset($this->options[$name]) ? $this->options[$name] : ($args['default'] ?? '');
        $rows = isset($args['rows']) ? $args['rows'] : 5;
        $class = isset($args['class']) ? $args['class'] : '';
        ?>
        <textarea name="aicb_settings[<?php echo esc_attr($name); ?>]" 
                  rows="<?php echo esc_attr($rows); ?>"
                  class="large-text <?php echo esc_attr($class); ?>"><?php echo esc_textarea($value); ?></textarea>
        <?php if (isset($args['description'])): ?>
            <p class="description"><?php echo esc_html($args['description']); ?></p>
        <?php endif;
    }
    
    public function render_checkbox_field($args) {
        $name = $args['name'];
        $default = isset($args['default']) ? $args['default'] : '0';
        $checked = isset($this->options[$name])
            ? $this->options[$name] == '1'
            : ($default === '1');
        ?>
        <label>
            <input type="checkbox" 
                   name="aicb_settings[<?php echo esc_attr($name); ?>]" 
                   value="1" 
                   <?php checked($checked); ?> />
            <?php echo esc_html($args['label']); ?>
        </label>
        <?php if (isset($args['description'])): ?>
            <p class="description"><?php echo esc_html($args['description']); ?></p>
        <?php endif;
    }
    
    public function render_select_field($args) {
        $name = $args['name'];
        $value = isset($this->options[$name]) ? $this->options[$name] : ($args['default'] ?? '');
        ?>
        <select name="aicb_settings[<?php echo esc_attr($name); ?>]">
            <?php foreach ($args['options'] as $key => $label): ?>
                <option value="<?php echo esc_attr($key); ?>" <?php selected($value, $key); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if (isset($args['description'])): ?>
            <p class="description"><?php echo esc_html($args['description']); ?></p>
        <?php endif;
    }
    
    public function render_radio_field($args) {
        $name = $args['name'];
        $value = isset($this->options[$name]) ? $this->options[$name] : ($args['default'] ?? '');
        ?>
        <fieldset>
            <?php foreach ($args['options'] as $key => $label): ?>
                <label style="display: block; margin-bottom: 10px;">
                    <input type="radio" 
                           name="aicb_settings[<?php echo esc_attr($name); ?>]" 
                           value="<?php echo esc_attr($key); ?>" 
                           <?php checked($value, $key); ?> />
                    <?php echo esc_html($label); ?>
                </label>
            <?php endforeach; ?>
        </fieldset>
        <?php if (isset($args['description'])): ?>
            <p class="description"><?php echo esc_html($args['description']); ?></p>
        <?php endif;
    }
    
    public function render_color_picker_field($args) {
        $name = $args['name'];
        $value = isset($this->options[$name]) ? $this->options[$name] : ($args['default'] ?? '#000000');
        ?>
        <input type="text" 
               name="aicb_settings[<?php echo esc_attr($name); ?>]" 
               value="<?php echo esc_attr($value); ?>"
               class="aicb-color-picker" />
        <?php if (isset($args['description'])): ?>
            <p class="description"><?php echo esc_html($args['description']); ?></p>
        <?php endif;
    }
    
    public function render_number_field($args) {
        $name = $args['name'];
        $value = isset($this->options[$name]) ? $this->options[$name] : ($args['default'] ?? 0);
        $min = isset($args['min']) ? $args['min'] : 0;
        $max = isset($args['max']) ? $args['max'] : 999999;
        ?>
        <input type="number" 
               name="aicb_settings[<?php echo esc_attr($name); ?>]" 
               value="<?php echo esc_attr($value); ?>"
               min="<?php echo esc_attr($min); ?>"
               max="<?php echo esc_attr($max); ?>"
               class="small-text" />
        <?php if (isset($args['description'])): ?>
            <p class="description"><?php echo esc_html($args['description']); ?></p>
        <?php endif;
    }
    
    public function render_email_field($args) {
        $name = $args['name'];
        $value = isset($this->options[$name]) ? $this->options[$name] : ($args['default'] ?? '');
        ?>
        <input type="email" 
               name="aicb_settings[<?php echo esc_attr($name); ?>]" 
               value="<?php echo esc_attr($value); ?>"
               class="regular-text" />
        <?php if (isset($args['description'])): ?>
            <p class="description"><?php echo esc_html($args['description']); ?></p>
        <?php endif;
    }
    
    public function render_url_field($args) {
        $name = $args['name'];
        $value = isset($this->options[$name]) ? $this->options[$name] : ($args['default'] ?? '');
        ?>
        <input type="url" 
               name="aicb_settings[<?php echo esc_attr($name); ?>]" 
               value="<?php echo esc_attr($value); ?>"
               class="regular-text" />
        <?php if (isset($args['description'])): ?>
            <p class="description"><?php echo esc_html($args['description']); ?></p>
        <?php endif;
    }
    
    public function render_media_upload_field($args) {
        $name = $args['name'];
        $value = isset($this->options[$name]) ? $this->options[$name] : '';
        ?>
        <div class="aicb-media-upload">
            <input type="hidden" 
                   id="<?php echo esc_attr($name); ?>" 
                   name="aicb_settings[<?php echo esc_attr($name); ?>]" 
                   value="<?php echo esc_attr($value); ?>" />
            <button type="button" class="button aicb-upload-button" data-target="<?php echo esc_attr($name); ?>">
                <?php esc_html_e('Choose Image', 'ai-chatbot'); ?>
            </button>
            <?php if ($value): ?>
                <div class="aicb-image-preview" style="margin-top: 10px;">
                    <img src="<?php echo esc_url($value); ?>" style="max-width: 150px; height: auto;" />
                    <button type="button" class="button aicb-remove-image" data-target="<?php echo esc_attr($name); ?>">
                        <?php esc_html_e('Remove', 'ai-chatbot'); ?>
                    </button>
                </div>
            <?php endif; ?>
        </div>
        <?php if (isset($args['description'])): ?>
            <p class="description"><?php echo esc_html($args['description']); ?></p>
        <?php endif;
    }
    // Section render methods
public function render_general_section() {
    echo '<p>' . esc_html__('Configure the basic settings for your AI chatbot.', 'ai-chatbot') . '</p>';
}

public function render_appearance_section() {
    echo '<p>' . esc_html__('Customize the visual appearance of your chatbot.', 'ai-chatbot') . '</p>';
}

public function render_openai_section() {
    echo '<p>' . esc_html__('Configure OpenAI API settings and model parameters.', 'ai-chatbot') . '</p>';
}

public function render_knowledge_section() {
    echo '<p>' . esc_html__('Set up your knowledge base for enhanced AI responses.', 'ai-chatbot') . '</p>';
}

public function render_lead_section() {
    echo '<p>' . esc_html__('Configure lead generation and capture settings.', 'ai-chatbot') . '</p>';
}

public function render_messages_section() {
    echo '<p>' . esc_html__('Customize messages and labels shown to users.', 'ai-chatbot') . '</p>';
}

public function render_security_section() {
    echo '<p>' . esc_html__('Configure security and privacy settings.', 'ai-chatbot') . '</p>';
}
public function render_test_connection() {
    ?>
    <button type="button" id="test-connection" class="button">
        <?php esc_html_e('Test Connection', 'ai-chatbot'); ?>
    </button>
    <div id="connection-result"></div>
    <?php
}

public function render_template_selector($args) {
    echo '<select name="aicb_settings[' . $args['name'] . ']">';
    echo '<option value="default">' . esc_html__('Default Template', 'ai-chatbot') . '</option>';
    echo '</select>';
    if (isset($args['description'])) {
        echo '<p class="description">' . esc_html($args['description']) . '</p>';
    }
}

public function render_content_sources() {
    $settings = get_option('aicb_settings', array());
    $selected_post_types = isset($settings['content_post_types']) ? $settings['content_post_types'] : array();
    
    $post_types = get_post_types(array('public' => true), 'objects');
    ?>
    <fieldset>
        <legend><?php esc_html_e('Select content types to include in knowledge base:', 'ai-chatbot'); ?></legend>
        <?php foreach ($post_types as $post_type): ?>
            <label style="display: block; margin-bottom: 5px;">
                <input type="checkbox" 
                       name="aicb_settings[content_post_types][]" 
                       value="<?php echo esc_attr($post_type->name); ?>"
                       <?php checked(in_array($post_type->name, $selected_post_types)); ?> />
                <?php echo esc_html($post_type->label); ?>
            </label>
        <?php endforeach; ?>
    </fieldset>
    <?php
}

public function render_lead_form_fields() {
    $settings = get_option('aicb_settings', array());
    $fields_config = isset($settings['lead_form_fields']) ? $settings['lead_form_fields'] : array();
    
    $default_fields = array(
        'name' => array('label' => __('Name', 'ai-chatbot'), 'enabled' => true, 'required' => true),
        'email' => array('label' => __('Email', 'ai-chatbot'), 'enabled' => true, 'required' => true),
        'phone' => array('label' => __('Phone', 'ai-chatbot'), 'enabled' => false, 'required' => false),
        'company' => array('label' => __('Company', 'ai-chatbot'), 'enabled' => false, 'required' => false),
        'message' => array('label' => __('Message', 'ai-chatbot'), 'enabled' => false, 'required' => false)
    );
    ?>
    <table class="widefat">
        <thead>
            <tr>
                <th><?php esc_html_e('Field', 'ai-chatbot'); ?></th>
                <th><?php esc_html_e('Enabled', 'ai-chatbot'); ?></th>
                <th><?php esc_html_e('Required', 'ai-chatbot'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($default_fields as $field_key => $field_data): 
                $enabled = isset($fields_config[$field_key]['enabled']) ? $fields_config[$field_key]['enabled'] : $field_data['enabled'];
                $required = isset($fields_config[$field_key]['required']) ? $fields_config[$field_key]['required'] : $field_data['required'];
            ?>
                <tr>
                    <td><?php echo esc_html($field_data['label']); ?></td>
                    <td>
                        <input type="checkbox" 
                               name="aicb_settings[lead_form_fields][<?php echo esc_attr($field_key); ?>][enabled]" 
                               value="1" <?php checked($enabled); ?> />
                    </td>
                    <td>
                        <input type="checkbox" 
                               name="aicb_settings[lead_form_fields][<?php echo esc_attr($field_key); ?>][required]" 
                               value="1" <?php checked($required); ?> />
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

public function render_knowledge_files() {
    ?>
    <div class="aicb-knowledge-files-manager">
        <h4><?php esc_html_e('Upload and Manage Files', 'ai-chatbot'); ?></h4>
        <p><?php esc_html_e('Upload TXT files to add their content to the knowledge base. PDF and other formats will be supported in future updates.', 'ai-chatbot'); ?></p>

        <div class="aicb-file-upload-area">
            <input type="file" id="aicb-knowledge-file-input" accept=".txt" style="display:none;">
            <button type="button" id="aicb-upload-file-button" class="button">
                <?php esc_html_e('Choose File to Upload', 'ai-chatbot'); ?>
            </button>
            <span class="spinner"></span>
        </div>

        <div id="aicb-knowledge-files-list">
            <?php
            $uploaded_files = get_option('aicb_knowledge_files', []);
            if (!empty($uploaded_files)) {
                echo '<table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">';
                echo '<thead><tr><th style="width: 60%;">' . esc_html__('File Name', 'ai-chatbot') . '</th><th>' . esc_html__('Uploaded On', 'ai-chatbot') . '</th><th>' . esc_html__('Actions', 'ai-chatbot') . '</th></tr></thead>';
                echo '<tbody>';
                foreach ($uploaded_files as $id => $file_info) {
                    echo '<tr data-id="' . esc_attr($id) . '">';
                    echo '<td><a href="' . esc_url($file_info['url']) . '" target="_blank">' . esc_html($file_info['file']) . '</a></td>';
                    echo '<td>' . esc_html(date_i18n(get_option('date_format'), strtotime($file_info['date']))) . '</td>';
                    echo '<td><button type="button" class="button button-link-delete aicb-remove-file" data-id="' . esc_attr($id) . '">' . esc_html__('Remove', 'ai-chatbot') . '</button></td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
            } else {
                echo '<p id="aicb-no-files-message" style="margin-top: 15px;">' . esc_html__('No files have been uploaded yet.', 'ai-chatbot') . '</p>';
            }
            ?>
        </div>
    </div>
    <?php
}

public function render_content_categories() {
    echo '<p>' . esc_html__('Content categorization options will be available here.', 'ai-chatbot') . '</p>';
}

public function render_reindex_button() {
    ?>
    <button type="button" id="reindex-content" class="button">
        <?php esc_html_e('Reindex Content', 'ai-chatbot'); ?>
    </button>
    <?php
}

public function render_leads_page() {
    echo '<div class="wrap">';
    echo '<h1>' . esc_html__( 'Chatbot Leads', 'ai-chatbot' ) . '</h1>';

    if ( ! class_exists( 'AICB_Leads_Table' ) ) {
        echo '<p>' . esc_html__( 'Leads table class not found.', 'ai-chatbot' ) . '</p>';
        echo '</div>';
        return;
    }

    $table = new AICB_Leads_Table();
    $table->prepare_items();

    echo '<form method="post">';
    $table->display();
    echo '</form>';
    echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin-top:20px;">';
    wp_nonce_field( 'aicb_export_leads' );
    echo '<input type="hidden" name="action" value="aicb_export_leads" />';
    submit_button( esc_html__( 'Export Leads CSV', 'ai-chatbot' ), 'secondary', 'submit', false );
    echo '</form>';
    echo '</div>';
}

public function render_history_page() {
    echo '<div class="wrap">';
    echo '<h1>' . esc_html__( 'Chat History', 'ai-chatbot' ) . '</h1>';

    if ( ! class_exists( 'AICB_Chat_History_Table' ) ) {
        echo '<p>' . esc_html__( 'Chat history table class not found.', 'ai-chatbot' ) . '</p>';
        echo '</div>';
        return;
    }

    $table = new AICB_Chat_History_Table();
    $table->prepare_items();

    echo '<form method="get">';
    foreach ( $_GET as $key => $value ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( 'paged' === $key ) {
            continue;
        }
        printf(
            '<input type="hidden" name="%1$s" value="%2$s" />',
            esc_attr( $key ),
            esc_attr( wp_unslash( $value ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        );
    }
    $table->display();
    echo '</form>';
    echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin-top:20px;">';
    wp_nonce_field( 'aicb_export_history' );
    echo '<input type="hidden" name="action" value="aicb_export_history" />';
    submit_button( esc_html__( 'Export Chat History CSV', 'ai-chatbot' ), 'secondary', 'submit', false );
    echo '</form>';
    echo '</div>';
}

    /**
     * Render the analytics dashboard page.
     */
    public function render_analytics_page() {
        $analytics = $this->get_analytics_data();

        echo '<div class="wrap aicb-analytics-wrap">';
        echo '<h1>' . esc_html__( 'Chat Analytics', 'ai-chatbot' ) . '</h1>';

        if ( empty( $analytics['has_data'] ) ) {
            echo '<div class="notice notice-info"><p>' . esc_html__( 'No chat activity has been recorded yet. Statistics will appear once visitors start interacting with the chatbot.', 'ai-chatbot' ) . '</p></div>';
            echo '</div>';
            return;
        }

        $summary = $analytics['summary'];

        echo '<div class="aicb-analytics-summary">';
        echo $this->render_analytics_card(
            __( 'Total Chats', 'ai-chatbot' ),
            number_format_i18n( $summary['total_chats'] ),
            __( 'Unique threads captured', 'ai-chatbot' )
        );
        echo $this->render_analytics_card(
            __( 'Total Messages', 'ai-chatbot' ),
            number_format_i18n( $summary['total_messages'] ),
            __( 'Stored across all sessions', 'ai-chatbot' )
        );
        echo $this->render_analytics_card(
            __( 'Avg. Messages / Chat', 'ai-chatbot' ),
            number_format_i18n( $summary['avg_messages'], 1 ),
            sprintf(
                /* translators: %s max number of messages per chat. */
                __( 'Most active chat: %s messages', 'ai-chatbot' ),
                number_format_i18n( $summary['max_messages'] )
            )
        );
        echo $this->render_analytics_card(
            __( 'Active Pages', 'ai-chatbot' ),
            number_format_i18n( $summary['active_pages'] ),
            __( 'Pages that triggered at least one chat', 'ai-chatbot' )
        );
        echo '</div>';

        echo '<div class="aicb-analytics-section">';
        echo '<h2>' . esc_html__( 'Chats per Day (Last 14 Days)', 'ai-chatbot' ) . '</h2>';
        if ( ! empty( $analytics['chats_by_day'] ) ) {
            echo '<table class="widefat striped">';
            echo '<thead><tr><th>' . esc_html__( 'Date', 'ai-chatbot' ) . '</th><th>' . esc_html__( 'Chats', 'ai-chatbot' ) . '</th><th>' . esc_html__( 'Messages', 'ai-chatbot' ) . '</th></tr></thead><tbody>';
            foreach ( $analytics['chats_by_day'] as $row ) {
                $timestamp = strtotime( $row['chat_date'] );
                $label     = $timestamp ? date_i18n( get_option( 'date_format' ), $timestamp ) : $row['chat_date'];
                echo '<tr>';
                echo '<td>' . esc_html( $label ) . '</td>';
                echo '<td>' . esc_html( number_format_i18n( $row['chats'] ) ) . '</td>';
                echo '<td>' . esc_html( number_format_i18n( $row['messages'] ) ) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p>' . esc_html__( 'No chat activity detected in the selected period.', 'ai-chatbot' ) . '</p>';
        }
        echo '</div>';

        echo '<div class="aicb-analytics-section">';
        echo '<h2>' . esc_html__( 'Messages per Chat Distribution', 'ai-chatbot' ) . '</h2>';
        if ( ! empty( $analytics['message_distribution'] ) ) {
            echo '<table class="widefat striped">';
            echo '<thead><tr><th>' . esc_html__( 'Messages per Chat', 'ai-chatbot' ) . '</th><th>' . esc_html__( 'Chats', 'ai-chatbot' ) . '</th></tr></thead><tbody>';
            foreach ( $analytics['message_distribution'] as $row ) {
                echo '<tr>';
                echo '<td>' . esc_html( number_format_i18n( $row['msg_count'] ) ) . '</td>';
                echo '<td>' . esc_html( number_format_i18n( $row['chat_count'] ) ) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p>' . esc_html__( 'Not enough data to build a distribution yet.', 'ai-chatbot' ) . '</p>';
        }
        echo '</div>';

        echo '<div class="aicb-analytics-section">';
        echo '<h2>' . esc_html__( 'Top Pages Starting Chats', 'ai-chatbot' ) . '</h2>';
        if ( ! empty( $analytics['top_pages'] ) ) {
            echo '<table class="widefat striped">';
            echo '<thead><tr><th>' . esc_html__( 'Page URL', 'ai-chatbot' ) . '</th><th>' . esc_html__( 'Chats', 'ai-chatbot' ) . '</th><th>' . esc_html__( 'Messages', 'ai-chatbot' ) . '</th></tr></thead><tbody>';
            foreach ( $analytics['top_pages'] as $row ) {
                $url = $row['page_context_url'];
                $label = $url ? esc_url( $url ) : '';
                echo '<tr>';
                echo '<td>';
                if ( ! empty( $label ) ) {
                    $display = mb_strlen( $label ) > 70 ? mb_substr( $label, 0, 67 ) . '…' : $label;
                    echo '<a href="' . esc_url( $label ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $display ) . '</a>';
                } else {
                    esc_html_e( 'Unknown', 'ai-chatbot' );
                }
                echo '</td>';
                echo '<td>' . esc_html( number_format_i18n( $row['chats'] ) ) . '</td>';
                echo '<td>' . esc_html( number_format_i18n( $row['messages'] ) ) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p>' . esc_html__( 'No page information is available for recorded chats.', 'ai-chatbot' ) . '</p>';
        }
        echo '</div>';

        if ( ! empty( $analytics['busy_hours'] ) ) {
            echo '<div class="aicb-analytics-section">';
            echo '<h2>' . esc_html__( 'Peak Chat Hours (Last 14 Days)', 'ai-chatbot' ) . '</h2>';
            echo '<table class="widefat striped">';
            echo '<thead><tr><th>' . esc_html__( 'Hour', 'ai-chatbot' ) . '</th><th>' . esc_html__( 'Messages', 'ai-chatbot' ) . '</th></tr></thead><tbody>';
            foreach ( $analytics['busy_hours'] as $row ) {
                $hour_label = sprintf( '%02d:00', (int) $row['hour'] );
                echo '<tr>';
                echo '<td>' . esc_html( $hour_label ) . '</td>';
                echo '<td>' . esc_html( number_format_i18n( $row['messages'] ) ) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
            echo '</div>';
        }

        echo '</div>';
    }

    /**
     * Build analytics data set used by the dashboard.
     *
     * @return array
     */
    private function get_analytics_data() {
        global $wpdb;

        $table = $wpdb->prefix . 'aicb_chat_history';
        $table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );

        if ( $table_exists !== $table ) {
            return array( 'has_data' => false );
        }

        $total_messages = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

        if ( 0 === $total_messages ) {
            return array( 'has_data' => false );
        }

        $total_chats = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT thread_id) FROM {$table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

        $avg_messages = (float) $wpdb->get_var(
            "SELECT AVG(msg_count) FROM (SELECT COUNT(*) AS msg_count FROM {$table} GROUP BY thread_id) msg_stats"
        ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

        $max_messages = (int) $wpdb->get_var(
            "SELECT MAX(msg_count) FROM (SELECT COUNT(*) AS msg_count FROM {$table} GROUP BY thread_id) msg_stats"
        ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

        $active_pages = (int) $wpdb->get_var(
            "SELECT COUNT(DISTINCT page_context_url) FROM {$table} WHERE page_context_url IS NOT NULL AND page_context_url <> ''"
        ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

        $start_date = gmdate( 'Y-m-d', strtotime( '-13 days' ) );

        $chats_by_day_sql = $wpdb->prepare(
            "SELECT DATE(timestamp) AS chat_date, COUNT(DISTINCT thread_id) AS chats, COUNT(*) AS messages
             FROM {$table}
             WHERE timestamp >= %s
             GROUP BY DATE(timestamp)
             ORDER BY chat_date DESC",
            $start_date
        );
        $chats_by_day = $wpdb->get_results( $chats_by_day_sql, ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared

        $distribution_limit = 10;
        $distribution_sql   = $wpdb->prepare(
            "SELECT msg_count, COUNT(*) AS chat_count
             FROM (SELECT COUNT(*) AS msg_count FROM {$table} GROUP BY thread_id) msg_stats
             GROUP BY msg_count
             ORDER BY msg_count ASC
             LIMIT %d",
            $distribution_limit
        );
        $message_distribution = $wpdb->get_results( $distribution_sql, ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared

        $top_pages_sql = $wpdb->prepare(
            "SELECT page_context_url, COUNT(DISTINCT thread_id) AS chats, COUNT(*) AS messages
             FROM {$table}
             WHERE page_context_url IS NOT NULL AND page_context_url <> ''
             GROUP BY page_context_url
             ORDER BY chats DESC, messages DESC
             LIMIT %d",
            5
        );
        $top_pages = $wpdb->get_results( $top_pages_sql, ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared

        $busy_hours_sql = $wpdb->prepare(
            "SELECT HOUR(timestamp) AS hour, COUNT(*) AS messages
             FROM {$table}
             WHERE timestamp >= %s
             GROUP BY HOUR(timestamp)
             ORDER BY messages DESC
             LIMIT %d",
            $start_date,
            6
        );
        $busy_hours = $wpdb->get_results( $busy_hours_sql, ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared

        return array(
            'has_data'             => true,
            'summary'              => array(
                'total_chats'    => $total_chats,
                'total_messages' => $total_messages,
                'avg_messages'   => $avg_messages,
                'max_messages'   => max( $max_messages, 0 ),
                'active_pages'   => $active_pages,
            ),
            'chats_by_day'         => $chats_by_day,
            'message_distribution' => $message_distribution,
            'top_pages'            => $top_pages,
            'busy_hours'           => $busy_hours,
        );
    }

    /**
     * Helper to render analytics summary cards.
     *
     * @param string $title Card title.
     * @param string $value Primary value.
     * @param string $subtitle Subtitle text.
     * @return string
     */
    private function render_analytics_card( $title, $value, $subtitle ) {
        ob_start();
        ?>
        <div class="aicb-analytics-card">
            <h3><?php echo esc_html( $title ); ?></h3>
            <p class="aicb-analytics-value"><?php echo esc_html( $value ); ?></p>
            <p class="aicb-analytics-subtext"><?php echo esc_html( $subtitle ); ?></p>
        </div>
        <?php
        return ob_get_clean();
    }

    public function export_leads_csv() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to export leads.', 'ai-chatbot' ) );
        }

        check_admin_referer( 'aicb_export_leads' );

        global $wpdb;
        $table_name = $wpdb->prefix . 'aicb_leads';

		$rows = $wpdb->get_results( 'SELECT name, email, phone, company, message, page_url, created_at, ip_address, privacy_consent, marketing_consent, consent_timestamp, thread_id, user_identifier FROM ' . esc_sql( $table_name ) . ' ORDER BY created_at DESC', ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		$columns = array(
			'name'             => __( 'Name', 'ai-chatbot' ),
			'email'            => __( 'Email', 'ai-chatbot' ),
			'phone'            => __( 'Phone', 'ai-chatbot' ),
			'company'          => __( 'Company', 'ai-chatbot' ),
			'message'          => __( 'Message', 'ai-chatbot' ),
			'page_url'         => __( 'Page URL', 'ai-chatbot' ),
			'created_at'       => __( 'Created At', 'ai-chatbot' ),
			'ip_address'       => __( 'IP Address', 'ai-chatbot' ),
			'privacy_consent'  => __( 'Privacy Consent', 'ai-chatbot' ),
			'marketing_consent'=> __( 'Marketing Consent', 'ai-chatbot' ),
			'consent_timestamp'=> __( 'Consent Timestamp', 'ai-chatbot' ),
			'thread_id'        => __( 'Thread ID', 'ai-chatbot' ),
			'user_identifier'  => __( 'User Identifier', 'ai-chatbot' ),
		);

        $this->stream_csv( 'ai-chatbot-leads', $columns, $rows );
    }

    public function export_history_csv() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to export chat history.', 'ai-chatbot' ) );
        }

        check_admin_referer( 'aicb_export_history' );

        global $wpdb;
        $table_name = $wpdb->prefix . 'aicb_chat_history';

        $rows = $wpdb->get_results( 'SELECT thread_id, message_sender, message_content, timestamp, page_context_url, user_identifier FROM ' . esc_sql( $table_name ) . ' ORDER BY timestamp DESC', ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

        $columns = array(
            'thread_id'       => __( 'Thread ID', 'ai-chatbot' ),
            'message_sender'  => __( 'Sender', 'ai-chatbot' ),
            'message_content' => __( 'Message', 'ai-chatbot' ),
            'timestamp'       => __( 'Timestamp', 'ai-chatbot' ),
            'page_context_url'=> __( 'Page URL', 'ai-chatbot' ),
            'user_identifier' => __( 'User Identifier', 'ai-chatbot' ),
        );

        $this->stream_csv( 'ai-chatbot-history', $columns, $rows );
    }

    private function stream_csv( $basename, array $columns, array $rows ) {
        nocache_headers();

        $filename = sprintf( '%s-%s.csv', sanitize_key( $basename ), gmdate( 'Y-m-d' ) );

        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename=' . $filename );

        $output = fopen( 'php://output', 'w' );
        if ( false === $output ) {
            wp_die( esc_html__( 'Unable to generate CSV export.', 'ai-chatbot' ) );
        }

        fputcsv( $output, array_values( $columns ) );

        foreach ( $rows as $row ) {
            $clean = array();
            foreach ( $columns as $key => $label ) {
                $value    = isset( $row[ $key ] ) ? $row[ $key ] : '';
                $clean[] = $this->prepare_csv_value( $value );
            }
            fputcsv( $output, $clean );
        }

        fclose( $output );
        exit;
    }

    private function prepare_csv_value( $value ) {
        if ( is_scalar( $value ) ) {
            return wp_strip_all_tags( (string) $value );
        }

        if ( is_array( $value ) || is_object( $value ) ) {
            return wp_strip_all_tags( wp_json_encode( $value ) );
        }

        return '';
    }

public function render_repeater_field($args) {
    $name = $args['name'];
    $values = isset($this->options[$name]) ? $this->options[$name] : ($args['default'] ?? array());
    ?>
    <div id="<?php echo esc_attr($name); ?>-container">
        <?php foreach ($values as $value): ?>
            <div class="repeater-item">
                <input type="text" name="aicb_settings[<?php echo esc_attr($name); ?>][]" value="<?php echo esc_attr($value); ?>" class="regular-text">
                <button type="button" class="button remove-item"><?php esc_html_e('Remove', 'ai-chatbot'); ?></button>
            </div>
        <?php endforeach; ?>
    </div>
    <button type="button" class="button add-item" data-target="<?php echo esc_attr($name); ?>">
        <?php echo esc_html($args['label'] ?? __('Add Item', 'ai-chatbot')); ?>
    </button>
    <?php
}   
    
public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('AI Chatbot Settings', 'ai-chatbot'); ?></h1>
            
            <?php settings_errors(); ?>
            
            <div class="aicb-admin-tabs">
                <h2 class="nav-tab-wrapper">
                    <a href="#general" class="nav-tab nav-tab-active"><?php esc_html_e('General', 'ai-chatbot'); ?></a>
                    <a href="#appearance" class="nav-tab"><?php esc_html_e('Appearance', 'ai-chatbot'); ?></a>
                    <a href="#openai" class="nav-tab"><?php esc_html_e('OpenAI', 'ai-chatbot'); ?></a>
                    <a href="#knowledge" class="nav-tab"><?php esc_html_e('Knowledge Base', 'ai-chatbot'); ?></a>
                    <a href="#lead" class="nav-tab"><?php esc_html_e('Lead Generation', 'ai-chatbot'); ?></a>
                    <a href="#messages" class="nav-tab"><?php esc_html_e('Messages', 'ai-chatbot'); ?></a>
                    <a href="#security" class="nav-tab"><?php esc_html_e('Security', 'ai-chatbot'); ?></a>
                </h2>
                
                <form method="post" action="options.php">
                    <?php settings_fields('aicb_settings'); ?>
                    
                    <div id="general" class="tab-content active">
                        <?php do_settings_sections('aicb-settings-general'); ?>
                    </div>
                    
                    <div id="appearance" class="tab-content">
                        <?php do_settings_sections('aicb-settings-appearance'); ?>
                    </div>
                    
                    <div id="openai" class="tab-content">
                        <?php do_settings_sections('aicb-settings-openai'); ?>
                    </div>
                    
                    <div id="knowledge" class="tab-content">
                        <?php do_settings_sections('aicb-settings-knowledge'); ?>
                    </div>
                    
                    <div id="lead" class="tab-content">
                        <?php do_settings_sections('aicb-settings-lead'); ?>
                    </div>
                    
                    <div id="messages" class="tab-content">
                        <?php do_settings_sections('aicb-settings-messages'); ?>
                    </div>
                    
                    <div id="security" class="tab-content">
                        <?php do_settings_sections('aicb-settings-security'); ?>
                    </div>
                    
                    <?php submit_button(); ?>
                </form>
            </div>
        </div>
        <?php
    }
    
    public function sanitize_settings($input) {
        $sanitized = array();
        $existing  = get_option('aicb_settings', array());

        if (!is_array($input)) {
            return $existing;
        }

        foreach ($input as $key => $value) {
            switch ($key) {
                case 'openai_api_key':
                case 'assistant_id':
                    $value = sanitize_text_field($value);
                    if ($value === '' && isset($existing[$key])) {
                        $sanitized[$key] = $existing[$key];
                    } else {
                        $sanitized[$key] = $value;
                    }
                    break;

                case 'chatbot_name':
                    $sanitized[$key] = sanitize_text_field($value);
                    break;

                case 'system_prompt':
                case 'lead_intro_text':
                case 'welcome_message':
                case 'consent_screen_intro':
                    $sanitized[$key] = sanitize_textarea_field($value);
                    break;

                case 'lead_trigger_words':
                    $sanitized[$key] = sanitize_textarea_field($value);
                    break;

                case 'lead_marketing_opt_in_label':
                case 'lead_consent_label':
                    $sanitized[$key] = wp_kses_post($value);
                    break;

                case 'lead_skip_button_label':
                case 'consent_screen_title':
                    $sanitized[$key] = sanitize_text_field($value);
                    break;

                case 'primary_color':
                case 'secondary_color':
                    $sanitized[$key] = sanitize_hex_color($value);
                    break;

                case 'lead_notification_email':
                    $sanitized[$key] = sanitize_email($value);
                    break;

                case 'privacy_policy_url':
                case 'chatbot_icon':
                    $sanitized[$key] = esc_url_raw($value);
                    break;

                case 'lead_trigger_threshold':
                case 'lead_message_threshold':
                    $sanitized[$key] = absint($value);
                    break;

                case 'show_chatbot':
                case 'enable_knowledge_base':
                case 'enable_lead_capture':
                case 'enable_rate_limiting':
                case 'enable_content_moderation':
                case 'enable_chat_history':
                case 'enable_content_suggestions':
                case 'suggest_posts':
                case 'suggest_products':
                case 'lead_enable_marketing_opt_in':
                case 'lead_marketing_opt_in_required':
                case 'enable_consent_screen':
                    $sanitized[$key] = $value === '1' ? '1' : '0';
                    break;

                case 'lead_form_fields':
                    $sanitized[$key] = array();
                    if (is_array($value)) {
                        foreach ($value as $field_key => $field_config) {
                            $sanitized[$key][ sanitize_key($field_key) ] = array(
                                'enabled'  => (isset($field_config['enabled']) && $field_config['enabled'] === '1') ? '1' : '0',
                                'required' => (isset($field_config['required']) && $field_config['required'] === '1') ? '1' : '0',
                            );
                        }
                    }
                    break;

                case 'suggested_questions':
                    if (is_array($value)) {
                        $sanitized[$key] = array_filter(array_map('sanitize_text_field', $value));
                    } else {
                        $sanitized[$key] = array();
                    }
                    break;

                default:
                    if (is_array($value)) {
                        $sanitized[$key] = $this->sanitize_array_recursive($value);
                    } else {
                        $sanitized[$key] = sanitize_text_field($value);
                    }
            }
        }

        $checkbox_keys = array(
            'show_chatbot',
            'enable_knowledge_base',
            'enable_lead_capture',
            'enable_rate_limiting',
            'enable_content_moderation',
            'enable_chat_history',
            'enable_content_suggestions',
            'suggest_posts',
            'suggest_products',
            'lead_enable_marketing_opt_in',
            'lead_marketing_opt_in_required',
            'enable_consent_screen',
        );

        foreach ($checkbox_keys as $checkbox_key) {
            if (!array_key_exists($checkbox_key, $sanitized)) {
                $sanitized[$checkbox_key] = '0';
            }
        }

        foreach ($existing as $key => $value) {
            if (!array_key_exists($key, $sanitized)) {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    private function sanitize_array_recursive($value) {
        if (!is_array($value)) {
            return sanitize_text_field($value);
        }

        $sanitized = array();
        foreach ($value as $key => $item) {
            $sanitized[sanitize_key($key)] = $this->sanitize_array_recursive($item);
        }

        return $sanitized;
    }
    
    public function test_openai_connection() {
        check_ajax_referer('aicb_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'ai-chatbot')));
        }

        // The API key is not passed directly; the API class fetches it from options
        if (!class_exists('AICB_API')) {
            require_once AICB_PLUGIN_DIR . 'includes/class-aicb-api.php';
        }

        $api = new AICB_API();
        $result = $api->test_connection();

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    public function reindex_content() {
        check_ajax_referer('aicb_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied.']);
        }
        if (!class_exists('AICB_Knowledge_Base')) {
            require_once AICB_PLUGIN_DIR . 'includes/class-aicb-knowledge-base.php';
        }
        $kb = new AICB_Knowledge_Base();
        $kb->clear_all('post');
        if (class_exists('WooCommerce')) {
            $kb->clear_all('product');
        }
        if ( class_exists( 'AICB_Embedding_Manager' ) ) {
            $embedding_manager = AICB_Embedding_Manager::instance();
            $embedding_manager->schedule_rebuild();
            $rebuild_result = $embedding_manager->rebuild_embeddings();
            if ( is_wp_error( $rebuild_result ) ) {
                wp_send_json_error( array( 'message' => $rebuild_result->get_error_message() ) );
            }
            $scheduled = wp_next_scheduled( 'aicb_run_embedding_rebuild' );
            if ( $scheduled ) {
                wp_unschedule_event( $scheduled, 'aicb_run_embedding_rebuild' );
            }
            delete_option( 'aicb_embedding_rebuild_scheduled' );
        }
        $options = get_option('aicb_settings', []);
        $post_types_to_sync = isset($options['content_post_types']) ? $options['content_post_types'] : [];
        $synced_count = 0;
        if (!empty($post_types_to_sync)) {
            $is_woo_active = class_exists('WooCommerce');
            $product_key = array_search('product', $post_types_to_sync);
            if ($is_woo_active && $product_key !== false) {
                $synced_count += $kb->sync_products();
                unset($post_types_to_sync[$product_key]);
            }
            if (!empty($post_types_to_sync)) {
                $synced_count += $kb->sync_posts($post_types_to_sync);
            }
        }
        wp_send_json_success([
            'message' => sprintf(__('%d items re-indexed successfully.', 'ai-chatbot'), $synced_count),
            'count' => $synced_count
        ]);
    }

    public function upload_knowledge_file() {
        check_ajax_referer('aicb_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied.']);
        }
        if (!isset($_FILES['file'])) {
            wp_send_json_error(['message' => 'No file uploaded.']);
        }
        $allowed_mimes = array(
            'txt'      => 'text/plain',
            'md'       => 'text/plain',
            'markdown' => 'text/markdown',
            'pdf'      => 'application/pdf',
        );

        $upload_overrides = array(
            'test_form' => false,
            'test_type' => false,
            'mimes'     => $allowed_mimes,
        );
        $movefile = wp_handle_upload($_FILES['file'], $upload_overrides);
        if ($movefile && !isset($movefile['error'])) {
            if (!class_exists('AICB_Knowledge_Base')) {
                require_once AICB_PLUGIN_DIR . 'includes/class-aicb-knowledge-base.php';
            }
            $kb = new AICB_Knowledge_Base();
            $import = $kb->import_from_file(
                $movefile['file'],
                array(
                    'source_type' => 'file',
                    'source_url'  => $movefile['url'],
                    'priority'    => 80,
                )
            );

            if (!empty($import['error'])) {
                wp_delete_file($movefile['file']);
                wp_send_json_error(['message' => $import['error']]);
            }

            if ( class_exists( 'AICB_Embedding_Manager' ) ) {
                $embedding_manager = AICB_Embedding_Manager::instance();
                $embedding_manager->schedule_rebuild();
                $rebuild_result = $embedding_manager->rebuild_embeddings();
                if ( is_wp_error( $rebuild_result ) ) {
                    wp_delete_file( $movefile['file'] );
                    wp_send_json_error( array( 'message' => $rebuild_result->get_error_message() ) );
                }
                $scheduled = wp_next_scheduled( 'aicb_run_embedding_rebuild' );
                if ( $scheduled ) {
                    wp_unschedule_event( $scheduled, 'aicb_run_embedding_rebuild' );
                }
                delete_option( 'aicb_embedding_rebuild_scheduled' );
            }

            $file_id = isset($import['id']) ? intval($import['id']) : 0;

            $file_info = [
                'file'   => basename($movefile['file']),
                'title'  => isset($import['title']) ? $import['title'] : basename($movefile['file']),
                'url'    => $movefile['url'],
                'path'   => $movefile['file'],
                'date'   => current_time('mysql'),
                'type'   => strtolower(pathinfo($movefile['file'], PATHINFO_EXTENSION)),
                'length' => isset($import['length']) ? (int) $import['length'] : 0,
            ];

            if ($file_id > 0) {
                $uploaded_files = get_option('aicb_knowledge_files', []);
                $uploaded_files[$file_id] = $file_info;
                update_option('aicb_knowledge_files', $uploaded_files);
            } else {
                $uploaded_files = get_option('aicb_knowledge_files', []);
            }

            $type_label = strtoupper($file_info['type']);
            $length_label = $file_info['length'] > 0 ? number_format_i18n($file_info['length']) : '';
            $message = __('File uploaded and indexed successfully.', 'ai-chatbot');

            if ($file_info['length'] > 0) {
                /* translators: 1: file type (e.g. PDF), 2: number of characters processed */
                $message = sprintf(__('Indexed %1$s file (%2$s characters processed).', 'ai-chatbot'), $type_label, $length_label);
            }

            wp_send_json_success([
                'message' => $message,
                'file_id' => $file_id,
                'file_info' => $file_info,
            ]);
        } else {
            wp_send_json_error(['message' => $movefile['error']]);
        }
    }

    public function remove_knowledge_file() {
        check_ajax_referer('aicb_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied.']);
        }
        $file_id = isset($_POST['file_id']) ? intval($_POST['file_id']) : 0;
        if (!$file_id) {
            wp_send_json_error(['message' => 'Invalid file ID.']);
        }
        if (!class_exists('AICB_Knowledge_Base')) {
            require_once AICB_PLUGIN_DIR . 'includes/class-aicb-knowledge-base.php';
        }
        $kb = new AICB_Knowledge_Base();
        if ( class_exists( 'AICB_Embedding_Manager' ) ) {
            AICB_Embedding_Manager::instance()->schedule_rebuild();
        }
        $uploaded_files = get_option('aicb_knowledge_files', []);
        if (isset($uploaded_files[$file_id])) {
            $file_path = $uploaded_files[$file_id]['path'];
            if (file_exists($file_path)) {
                wp_delete_file($file_path);
            }
            unset($uploaded_files[$file_id]);
            update_option('aicb_knowledge_files', $uploaded_files);
        }
        $kb->delete_content($file_id);
        wp_send_json_success(['message' => 'File removed successfully.']);
    }

    public function get_theme_templates() {
        check_ajax_referer('aicb_admin_nonce', 'nonce');
        wp_send_json_success([]);
    }
}
