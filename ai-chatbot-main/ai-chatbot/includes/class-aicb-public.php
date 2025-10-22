public function enqueue_scripts() {
    // Enqueue styles first
    wp_enqueue_style('dashicons');
    wp_enqueue_style(
        $this->plugin_name . '-frontend',
        AICB_PLUGIN_URL . 'assets/css/chatbot.css',
        array(),
        $this->version
    );

    wp_enqueue_script(
        $this->plugin_name . '-frontend',
        AICB_PLUGIN_URL . 'assets/js/frontend.js',
        array('jquery'),
        $this->version,
        true
    );

    // Get saved settings from WordPress options
    $settings = get_option('aicb_settings', array());

    // Define a default icon URL (adjust path if needed)
    $default_icon_url = AICB_PLUGIN_URL . 'assets/images/default-icon.png';

    // Prepare parameters for JavaScript, including the missing ones
    $params = array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('aicb_frontend_nonce'), // Use a consistent nonce for frontend actions
        'show_chatbot' => $this->options['show_chatbot'] ?? '1', // Default to '1' (shown)
        'chatbot_name' => $settings['chatbot_name'] ?? 'AI Assistant',
        'chatbot_icon' => $settings['chatbot_icon'] ?? $default_icon_url, // Use default icon if not set
        'position' => $settings['position'] ?? 'bottom-right',
        'primary_color' => $settings['primary_color'] ?? '#007bff',
        'secondary_color' => $settings['secondary_color'] ?? '#6c757d',
        'enable_lead_capture' => $settings['enable_lead_capture'] ?? '0', // Default to '0' (disabled)
        'lead_intro_text' => $settings['lead_intro_text'] ?? 'Please provide your contact details:',
        'is_shortcode' => $this->is_shortcode_present() // Keep existing shortcode check
        // Add any other settings needed by frontend.js here
    );

    // Pass the parameters object 'aicb_params' to the frontend script
    wp_localize_script($this->plugin_name . '-frontend', 'aicb_params', $params);
}

private function is_shortcode_present() {
    global $post;
    return is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'ai-chatbot');
}

public function __construct($plugin_name, $version) {
    $this->plugin_name = $plugin_name;
    $this->version = $version;
    $this->options = get_option('aicb_settings', array());

    // Register shortcode
    add_action('init', array($this, 'register_shortcode'));

    // Add chatbot HTML to footer
    add_action('wp_footer', array($this, 'render_chatbot'));
}

public function render_chatbot() {
    // Get position from options
    $position = isset($this->options['position']) ? $this->options['position'] : 'bottom-right';

    // Include template with position variable
    include AICB_PLUGIN_DIR . 'templates/chatbot.php';
}

public function register_shortcode() {
    add_shortcode('ai-chatbot', array($this, 'render_shortcode'));
}

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

private function get_option($key, $default = '') {
    return isset($this->options[$key]) && !empty($this->options[$key])
        ? $this->options[$key]
        : $default;
}