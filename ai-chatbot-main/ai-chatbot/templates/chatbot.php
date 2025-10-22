<?php
/**
 * The template for displaying the AI Chatbot.
 * This file is responsible for rendering the complete HTML structure of the chatbot.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Retrieve all settings
$settings = get_option('aicb_settings', array());

// General Settings
$position = isset($settings['position']) ? esc_attr($settings['position']) : 'bottom-right';
$chatbot_name = isset($settings['chatbot_name']) && !empty(trim($settings['chatbot_name'])) ? esc_html($settings['chatbot_name']) : __('AI Assistant', 'ai-chatbot');
$chatbot_icon = !empty($settings['chatbot_icon']) ? esc_url($settings['chatbot_icon']) : '';

// Messages & Labels
$lead_intro_text = isset($settings['lead_intro_text']) && !empty(trim($settings['lead_intro_text'])) ? esc_html($settings['lead_intro_text']) : __('Please provide your information to continue.', 'ai-chatbot');
$lead_consent_label = isset($settings['lead_consent_label']) && !empty($settings['lead_consent_label'])
    ? wp_kses_post($settings['lead_consent_label'])
    : __('I agree to the [privacy_policy_link].', 'ai-chatbot');
$lead_skip_label = isset($settings['lead_skip_button_label']) && !empty($settings['lead_skip_button_label'])
    ? esc_html($settings['lead_skip_button_label'])
    : __('Skip', 'ai-chatbot');
$lead_marketing_enabled = !empty($settings['lead_enable_marketing_opt_in']);
$lead_marketing_required = !empty($settings['lead_marketing_opt_in_required']);
$lead_marketing_label = isset($settings['lead_marketing_opt_in_label']) && !empty($settings['lead_marketing_opt_in_label'])
    ? wp_kses_post($settings['lead_marketing_opt_in_label'])
    : __('I agree to receive marketing updates.', 'ai-chatbot');
$consent_title = isset($settings['consent_screen_title']) ? esc_html($settings['consent_screen_title']) : __('Welcome!', 'ai-chatbot');
$consent_intro = isset($settings['consent_screen_intro']) ? esc_html($settings['consent_screen_intro']) : __('Before you start, please review our data usage policies.', 'ai-chatbot');
$consent_history_explanation = isset($settings['consent_history_explanation']) ? esc_html($settings['consent_history_explanation']) : __('Save conversation history to improve the experience.', 'ai-chatbot');
$privacy_policy_url = isset($settings['privacy_policy_url']) ? esc_url($settings['privacy_policy_url']) : '';
$input_placeholder = __('Type your message...', 'ai-chatbot');

// Lead Form Config
$lead_form_fields_config = isset($settings['lead_form_fields']) ? $settings['lead_form_fields'] : array(
    'name' => array('enabled' => true, 'required' => true),
    'email' => array('enabled' => true, 'required' => true),
    'phone' => array('enabled' => false, 'required' => false),
    'company' => array('enabled' => false, 'required' => false),
    'message' => array('enabled' => false, 'required' => false),
);
$default_field_labels = array(
    'name' => __('Name', 'ai-chatbot'),
    'email' => __('Email', 'ai-chatbot'),
    'phone' => __('Phone', 'ai-chatbot'),
    'company' => __('Company', 'ai-chatbot'),
    'message' => __('Message', 'ai-chatbot'),
);

?>

<!-- Main Chatbot Container -->
<div id="aicb-chatbot" class="aicb-chatbot-container <?php echo esc_attr($position); ?>">
    <div class="aicb-chatbot-header">
        <div class="aicb-chatbot-header-content">
            <div class="aicb-chatbot-icon">
                <?php if (!empty($chatbot_icon)): ?>
                    <img src="<?php echo $chatbot_icon; ?>" alt="<?php echo esc_attr($chatbot_name); ?>">
                <?php else: ?>
                    <span class="dashicons dashicons-format-chat"></span>
                <?php endif; ?>
            </div>
            <div class="aicb-chatbot-name"><?php echo esc_html($chatbot_name); ?></div>
        </div>
        <div class="aicb-header-actions">
            <button class="aicb-clear-chat" aria-label="<?php esc_attr_e('Clear chat', 'ai-chatbot'); ?>">
                <span class="dashicons dashicons-trash"></span>
            </button>
            <button class="aicb-minimize" aria-label="<?php esc_attr_e('Minimize', 'ai-chatbot'); ?>">
                <span class="dashicons dashicons-minus"></span>
            </button>
            <button class="aicb-close" aria-label="<?php esc_attr_e('Close', 'ai-chatbot'); ?>">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
    </div>

    <div class="aicb-consent-screen" style="display:none;">
        <div class="aicb-consent-content">
            <h3><?php echo esc_html($consent_title); ?></h3>
            <p class="aicb-consent-intro"><?php echo esc_html($consent_intro); ?></p>

            <div class="aicb-consent-options">
                <label class="aicb-consent-privacy">
                    <input type="checkbox" id="aicb-consent-privacy">
                    <span class="aicb-consent-privacy-text">
                        <?php
                        $privacy_text = __('I agree to the privacy policy.', 'ai-chatbot');
                        if (!empty($privacy_policy_url)) {
                            /* translators: %s: Privacy policy URL. */
                            $privacy_text = sprintf(
                                __('I agree to the <a href="%s" target="_blank" rel="noopener noreferrer">privacy policy</a>.', 'ai-chatbot'),
                                $privacy_policy_url
                            );
                        }
                        echo wp_kses_post($privacy_text);
                        ?>
                    </span>
                </label>
            </div>

            <div class="aicb-consent-options">
                <label class="aicb-consent-history">
                    <input type="checkbox" id="aicb-consent-history">
                    <span class="aicb-consent-history-text"><?php echo esc_html($consent_history_explanation); ?></span>
                </label>
            </div>

            <button class="aicb-consent-accept" disabled><?php esc_html_e('Continue', 'ai-chatbot'); ?></button>
        </div>
    </div>

    <div class="aicb-messages"></div>
    <div class="aicb-suggested-questions"></div>

    <div class="aicb-typing-indicator" style="display:none;">
        <span class="aicb-typing-dots">
            <span class="aicb-typing-dot"></span>
            <span class="aicb-typing-dot"></span>
            <span class="aicb-typing-dot"></span>
        </span>
        <span class="aicb-typing-label"><?php esc_html_e('AI is typing...', 'ai-chatbot'); ?></span>
    </div>

    <!-- Lead Form Overlay -->
    <div class="aicb-lead-form" style="display: none;">
            <div class="aicb-lead-form-title"><?php echo esc_html($lead_intro_text); ?></div>
        <form id="aicb-lead-capture-form">
            <?php foreach ($lead_form_fields_config as $field_key => $config): ?>
                <?php if (!empty($config['enabled'])): ?>
                    <div class="aicb-lead-form-field">
                        <label for="aicb-lead-<?php echo esc_attr($field_key); ?>">
                            <?php echo esc_html($default_field_labels[$field_key]); ?>
                            <?php if (!empty($config['required'])): ?>
                                <span class="required">*</span>
                            <?php endif; ?>
                        </label>
                        <?php if ($field_key === 'message'): ?>
                            <textarea
                                id="aicb-lead-<?php echo esc_attr($field_key); ?>"
                                name="<?php echo esc_attr($field_key); ?>"
                                <?php if (!empty($config['required'])) echo 'required'; ?>
                            ></textarea>
                        <?php else: ?>
                            <input
                                type="<?php echo $field_key === 'email' ? 'email' : ($field_key === 'phone' ? 'tel' : 'text'); ?>"
                                id="aicb-lead-<?php echo esc_attr($field_key); ?>"
                                name="<?php echo esc_attr($field_key); ?>"
                                <?php if (!empty($config['required'])) echo 'required'; ?>
                            >
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
            <div class="aicb-lead-privacy">
                <label>
                    <input type="checkbox" id="aicb-lead-privacy" name="privacy" value="1" required>
                    <span class="aicb-lead-privacy-text">
                        <?php
                        $privacy_label = $lead_consent_label;
                        if (!empty($privacy_policy_url)) {
                            $privacy_link = sprintf(
                                '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
                                $privacy_policy_url,
                                esc_html($settings['privacy_policy_link_label'] ?? __('privacy policy', 'ai-chatbot'))
                            );
                            $privacy_label = str_replace('[privacy_policy_link]', $privacy_link, $privacy_label);
                        } else {
                            $privacy_label = str_replace('[privacy_policy_link]', esc_html($settings['privacy_policy_link_label'] ?? __('privacy policy', 'ai-chatbot')), $privacy_label);
                        }
                        echo wp_kses_post($privacy_label);
                        ?>
                    </span>
                </label>
            </div>
            <?php if ($lead_marketing_enabled): ?>
                <div class="aicb-lead-marketing">
                    <label>
                        <input type="checkbox"
                               id="aicb-lead-marketing"
                               name="marketing"
                               value="1"
                               <?php echo $lead_marketing_required ? 'required' : ''; ?>>
                        <span class="aicb-lead-marketing-text">
                            <?php echo wp_kses_post($lead_marketing_label); ?>
                        </span>
                    </label>
                </div>
            <?php endif; ?>
            <button type="submit" class="aicb-lead-form-submit"><?php esc_html_e('Submit', 'ai-chatbot'); ?></button>
            <button type="button" class="aicb-lead-form-skip"><?php echo esc_html($lead_skip_label); ?></button>
        </form>
    </div>

    <div class="aicb-input-form">
        <div class="aicb-input-wrapper">
            <input type="text" id="aicb-input" class="aicb-input" placeholder="<?php echo esc_attr($input_placeholder); ?>">
            <button class="aicb-send-button" aria-label="<?php esc_attr_e('Send', 'ai-chatbot'); ?>">
                <span class="dashicons dashicons-arrow-right-alt"></span>
            </button>
        </div>
    </div>
</div>

<!-- Toggle Button -->
<button class="aicb-chatbot-toggle <?php echo esc_attr($position); ?>" aria-label="<?php esc_attr_e('Open chat', 'ai-chatbot'); ?>">
    <span class="dashicons dashicons-format-chat"></span>
</button>
