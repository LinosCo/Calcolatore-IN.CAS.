/**
 * AI Chatbot Frontend JavaScript
 * Refactored to work with server-side rendered HTML.
 */

(function($) {
    'use strict';

    class AIChatbot {
        constructor() {
            // Properties to be filled in init
            this.container = null;
            this.toggleButton = null;
            this.messages = [];
            this.isTyping = false;
            this.conversationId = null;
            this.hasConsent = false;
            this.leadCaptured = false;
            this.messageCount = 0;
            this.privacyCheckbox = null;
            this.historyCheckbox = null;
            this.consentButton = null;
            
            // Defer initialization to DOM ready
            $(() => this.init());
        }

        init() {
            // Select pre-rendered elements
            this.container = $('#aicb-chatbot');
            this.toggleButton = $('.aicb-chatbot-toggle');

            // If the main container doesn't exist, do nothing.
            if (!this.container.length) {
                return;
            }

            this.bindEvents();
            this.loadConversationState();
            this.checkLeadCaptureTrigger();
            
            // Show welcome message if configured and no prior conversation
            if (window.aicb_params.welcome_message && this.messages.length === 0) {
                setTimeout(() => {
                    this.addMessage('assistant', window.aicb_params.welcome_message);
                    this.showSuggestedQuestions();
                }, 1000);
            }
        }

        bindEvents() {
            this.toggleButton.on('click', () => this.openChat());
            
            this.container.find('.aicb-close').on('click', () => this.closeChat());
            this.container.find('.aicb-minimize').on('click', () => this.minimizeChat());
            this.container.find('.aicb-clear-chat').on('click', () => this.clearChat());
            
            this.container.find('.aicb-send-button').on('click', () => this.sendMessage());
            this.container.find('#aicb-input').on('keypress', (e) => {
                if (e.which === 13 && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage();
                }
            });
            
            this.container.find('.aicb-consent-accept').on('click', () => this.handleConsent());

            this.privacyCheckbox = this.container.find('#aicb-consent-privacy');
            this.historyCheckbox = this.container.find('#aicb-consent-history');
            this.consentButton = this.container.find('.aicb-consent-accept');

            if (this.privacyCheckbox.length && this.consentButton.length) {
                const updateConsentButtonState = () => {
                    this.consentButton.prop('disabled', !this.privacyCheckbox.is(':checked'));
                };
                updateConsentButtonState();
                this.privacyCheckbox.on('change', updateConsentButtonState);
            }
            
            this.container.find('#aicb-lead-capture-form').on('submit', (e) => {
                e.preventDefault();
                this.submitLeadForm();
            });
            
            // Use event delegation for suggested questions as they are added dynamically
            this.container.on('click', '.aicb-suggested-question', (e) => {
                const question = $(e.currentTarget).text();
                this.container.find('#aicb-input').val(question);
                this.sendMessage();
            });

            this.initializeConsentContent();
            this.initializeLeadPrivacyLabel();
        }

        openChat() {
            this.container.addClass('active');
            this.toggleButton.addClass('hidden');
            
            // Check for consent on first open
            if (!this.hasConsent && window.aicb_params.enable_consent === '1') {
                this.container.find('.aicb-consent-screen').show();
                this.container.find('.aicb-messages, .aicb-input-form').hide();
            } else {
                this.container.find('.aicb-consent-screen').hide();
                this.container.find('.aicb-messages, .aicb-input-form').show();
            }
            
            setTimeout(() => this.container.find('#aicb-input').focus(), 300);
            this.trackEvent('chat_opened');
        }

        closeChat() {
            this.container.removeClass('active');
            this.toggleButton.removeClass('hidden');
        }

        minimizeChat() {
            const minimizeButtonIcon = this.container.find('.aicb-minimize .dashicons');
            if (this.container.hasClass('minimized')) {
                this.container.removeClass('minimized');
                this.container.find('.aicb-messages, .aicb-input-form, .aicb-suggested-questions').show();
                minimizeButtonIcon.removeClass('dashicons-arrow-up-alt').addClass('dashicons-minus');
            } else {
                this.container.addClass('minimized');
                this.container.find('.aicb-messages, .aicb-input-form, .aicb-suggested-questions').hide();
                minimizeButtonIcon.removeClass('dashicons-minus').addClass('dashicons-arrow-up-alt');
            }
        }

        clearChat() {
            if (confirm('Are you sure you want to clear the conversation?')) {
                this.container.find('.aicb-messages').empty();
                this.messages = [];
                this.messageCount = 0;
                this.saveConversationState();
                if (window.aicb_params.welcome_message) {
                    this.addMessage('assistant', window.aicb_params.welcome_message);
                }
                this.showSuggestedQuestions();
            }
        }

        sendMessage() {
            const input = this.container.find('#aicb-input');
            const message = input.val().trim();
            if (!message) return;
            
            this.addMessage('user', message);
            input.val('');
            this.container.find('.aicb-suggested-questions').hide();
            this.showTypingIndicator();
            this.sendToAPI(message);
            this.messageCount++;
            this.checkLeadCaptureTrigger(message);
        }

        async sendToAPI(message) {
            const chatHistoryConsent = localStorage.getItem('aicb_save_history') === 'true';
            const contextPayload = JSON.stringify(this.getConversationContext());

            try {
                const response = await fetch(`${window.aicb_params.rest_url}ai-chatbot/v1/send-message`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': window.aicb_params.rest_nonce
                    },
                    body: JSON.stringify({
                        message: message,
                        thread_id: this.conversationId,
                        page_context: contextPayload,
                        chat_history_consent_given: chatHistoryConsent,
                        current_language: window.aicb_params.current_language || 'en'
                    })
                });

                const data = await response.json();
                this.hideTypingIndicator();

                if (!response.ok) {
                    const errorMessage = data && data.message ? data.message : 'Failed to get response';
                    throw new Error(errorMessage);
                }

                const payload = data.data ? data.data : data;
                if (!payload || !payload.message) {
                    throw new Error('No response from assistant');
                }

                this.addMessage('assistant', payload.message);
                if (payload.related_content) {
                    this.showRelatedContent(payload.related_content);
                }
                if (payload.thread_id) {
                    this.conversationId = payload.thread_id;
                }
            } catch (error) {
                console.error('API Error:', error);
                this.hideTypingIndicator();
                this.addMessage('assistant', 'Sorry, I encountered an error. Please try again.');
            }
        }

        addMessage(role, content) {
            const messageHtml = `<div class="aicb-message aicb-message-${role}"><div class="aicb-message-bubble">${this.formatMessage(content)}</div></div>`;
            this.container.find('.aicb-messages').append(messageHtml);
            this.scrollToBottom();
            
            // Only save to history if it's not the initial welcome message
            if (this.messages.length > 0 || role === 'user') {
                this.messages.push({ role, content, timestamp: Date.now() });
                this.saveConversationState();
            }
        }

        formatMessage(content) {
            return content
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                .replace(/\*(.*?)\*/g, '<em>$1</em>')
                .replace(/\[([^\]]+)\]\(([^\)]+)\)/g, '<a href="$2" target="_blank" rel="noopener">$1</a>')
                .replace(/\n/g, '<br>');
        }

        showTypingIndicator() {
            this.container.find('.aicb-typing-indicator').show();
            this.scrollToBottom();
        }

        hideTypingIndicator() {
            this.container.find('.aicb-typing-indicator').hide();
        }

        showSuggestedQuestions() {
            const questions = window.aicb_params.suggested_questions || [];
            const container = this.container.find('.aicb-suggested-questions');
            if (questions.length > 0 && this.messages.length <= 1) { // Only show on start
                const html = questions.map(q => `<button class="aicb-suggested-question">${q}</button>`).join('');
                container.html(html).show();
            } else {
                container.hide();
            }
        }

        showRelatedContent(content) {
            // This function is not fully implemented in the original JS, but we'll keep the placeholder
            if (!content || content.length === 0) return;
            // ... logic to show related content ...
        }

        checkLeadCaptureTrigger(latestMessage = '') {
            if (this.leadCaptured || window.aicb_params.enable_lead_capture !== '1') {
                return;
            }
            const triggerThreshold = parseInt(window.aicb_params.lead_trigger_threshold || window.aicb_params.lead_message_threshold || 3, 10) || 3;
            const triggerWords = (window.aicb_params.lead_trigger_words || '')
                .split(',')
                .map((word) => word.trim().toLowerCase())
                .filter(Boolean);

            if (latestMessage && triggerWords.length) {
                const normalized = latestMessage.toLowerCase();
                const matched = triggerWords.some((word) => normalized.includes(word));
                if (matched) {
                    this.showLeadForm();
                    return;
                }
            }

            if (this.messageCount >= triggerThreshold) {
                this.showLeadForm();
            }
        }

        showLeadForm() {
            this.container.find('.aicb-lead-form').slideDown();
            this.container.find('.aicb-input-form').hide();
        }

        async submitLeadForm() {
            const form = this.container.find('#aicb-lead-capture-form');
            const formData = {
                name: form.find('#aicb-lead-name').val(),
                email: form.find('#aicb-lead-email').val(),
                phone: form.find('#aicb-lead-phone').val(),
                company: form.find('#aicb-lead-company').val(),
                message: form.find('#aicb-lead-message').val(),
                privacy: form.find('#aicb-lead-privacy').is(':checked'),
                marketing: form.find('#aicb-lead-marketing').length ? form.find('#aicb-lead-marketing').is(':checked') : false,
                thread_id: this.conversationId,
                page_url: window.location.href
            };

            // Basic validation
            if (!formData.name || !formData.email) {
                alert('Please fill in all required fields.');
                return;
            }

            if (!formData.privacy) {
                alert('Please accept the privacy policy to continue.');
                return;
            }

            const marketingRequired = window.aicb_params.lead_marketing_required === '1';
            if (marketingRequired && form.find('#aicb-lead-marketing').length && !formData.marketing) {
                alert(window.aicb_params.marketing_consent_required_message || 'Please accept the marketing consent to continue.');
                return;
            }

            try {
                // Use the REST API endpoint for lead submission
                const response = await fetch(`${window.aicb_params.rest_url}ai-chatbot/v1/lead`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': window.aicb_params.rest_nonce
                    },
                    body: JSON.stringify(formData)
                });

                const data = await response.json();

                if (!response.ok) {
                    const errorMessage = data && data.message ? data.message : 'There was an error submitting the form.';
                    throw new Error(errorMessage);
                }

                const payload = data.data ? data.data : data;
                if (payload && payload.success) {
                    this.leadCaptured = true;
                    this.container.find('.aicb-lead-form').slideUp();
                    this.container.find('.aicb-input-form').show();
                    this.addMessage('assistant', 'Thank you for providing your information! How can I help you today?');
                } else {
                    throw new Error(payload && payload.message ? payload.message : 'There was an error submitting the form.');
                }
            } catch (error) {
                console.error('Lead submission error:', error);
                alert(error.message || 'An error occurred while submitting the form.');
            }
        }

        handleConsent() {
            if (this.privacyCheckbox && !this.privacyCheckbox.is(':checked')) {
                alert(window.aicb_params.privacy_consent_required_message || 'Please agree to the privacy policy to continue.');
                return;
            }

            this.hasConsent = true;
            const saveHistory = this.historyCheckbox && this.historyCheckbox.is(':checked');
            
            localStorage.setItem('aicb_consent', 'true');
            localStorage.setItem('aicb_save_history', saveHistory ? 'true' : 'false');
            
            this.container.find('.aicb-consent-screen').hide();
            this.container.find('.aicb-messages, .aicb-input-form').show();
            
            this.updateConsentStatus(saveHistory);
        }

        async updateConsentStatus(saveHistory) {
            try {
                await fetch(`${window.aicb_params.rest_url}ai-chatbot/v1/consent`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': window.aicb_params.rest_nonce
                    },
                    body: JSON.stringify({
                        save_history: saveHistory,
                        conversation_id: this.conversationId
                    })
                });
            } catch (error) {
                console.error('Consent update error:', error);
            }
        }

        scrollToBottom() {
            const messages = this.container.find('.aicb-messages');
            if (messages.length) {
                messages.scrollTop(messages[0].scrollHeight);
            }
        }

        getConversationContext() {
            return {
                messages: this.messages.slice(-10),
                page_url: window.location.href,
                page_title: document.title,
                description: this.getMetaDescription(),
                content_snippet: this.getBodyContentSnippet(),
                headings: this.getHeadingsSnippet(),
                links: this.getImportantLinksSnippet()
            };
        }

        saveConversationState() {
            const state = {
                messages: this.messages,
                conversationId: this.conversationId,
                leadCaptured: this.leadCaptured,
                messageCount: this.messageCount
            };
            sessionStorage.setItem('aicb_conversation', JSON.stringify(state));
        }

        initializeConsentContent() {
            const privacyLabel = this.container.find('.aicb-consent-privacy-text');
            if (privacyLabel.length) {
                let label = window.aicb_params.consent_gdpr_explanation || 'I agree to the privacy policy.';
                let hasLink = false;
                if (window.aicb_params.privacy_policy_url) {
                    const link = `<a href="${this.escapeAttribute(window.aicb_params.privacy_policy_url)}" target="_blank" rel="noopener noreferrer">${this.escapeHTML(window.aicb_params.privacy_policy_link_label || 'privacy policy')}</a>`;
                    label = label.replace('[privacy_policy_link]', link);
                    hasLink = true;
                } else {
                    label = label.replace('[privacy_policy_link]', this.escapeHTML(window.aicb_params.privacy_policy_link_label || 'privacy policy'));
                }
                if (hasLink || label.indexOf('<a') !== -1) {
                    privacyLabel.html(label);
                } else {
                    privacyLabel.text(label);
                }
            }

            const historyLabel = this.container.find('.aicb-consent-history-text');
            if (historyLabel.length && window.aicb_params.consent_history_explanation) {
                historyLabel.text(window.aicb_params.consent_history_explanation);
            }
        }

        initializeLeadPrivacyLabel() {
            const leadPrivacy = this.container.find('.aicb-lead-privacy-text');
            if (leadPrivacy.length) {
                let label = window.aicb_params.lead_consent_label || 'I agree to the privacy policy.';
                let hasLink = false;
                if (window.aicb_params.privacy_policy_url) {
                    const link = `<a href="${this.escapeAttribute(window.aicb_params.privacy_policy_url)}" target="_blank" rel="noopener noreferrer">${this.escapeHTML(window.aicb_params.privacy_policy_link_label || 'privacy policy')}</a>`;
                    label = label.replace('[privacy_policy_link]', link);
                    hasLink = true;
                } else {
                    label = label.replace('[privacy_policy_link]', this.escapeHTML(window.aicb_params.privacy_policy_link_label || 'privacy policy'));
                }
                if (hasLink || label.indexOf('<a') !== -1) {
                    leadPrivacy.html(label);
                } else {
                    leadPrivacy.text(label);
                }
            }

            const marketingLabel = this.container.find('.aicb-lead-marketing-text');
            if (marketingLabel.length && window.aicb_params.lead_marketing_label) {
                let content = window.aicb_params.lead_marketing_label;
                if (window.aicb_params.privacy_policy_url) {
                    const link = `<a href="${this.escapeAttribute(window.aicb_params.privacy_policy_url)}" target="_blank" rel="noopener noreferrer">${this.escapeHTML(window.aicb_params.privacy_policy_link_label || 'privacy policy')}</a>`;
                    content = content.replace('[privacy_policy_link]', link);
                } else {
                    content = content.replace('[privacy_policy_link]', this.escapeHTML(window.aicb_params.privacy_policy_link_label || 'privacy policy'));
                }
                marketingLabel.html(content);
            }

           const skipButton = this.container.find('.aicb-lead-form-skip');
            if (skipButton.length) {
                if (window.aicb_params.lead_skip_button_label) {
                    skipButton.text(window.aicb_params.lead_skip_button_label);
                }
                skipButton.off('click.aicbSkip').on('click.aicbSkip', (event) => {
                    event.preventDefault();
                    this.container.find('.aicb-lead-form').slideUp();
                    this.container.find('.aicb-input-form').show();
                    this.leadCaptured = true;
                });
            }
        }

        getMetaDescription() {
            const meta = document.querySelector('meta[name="description"]');
            return meta ? meta.getAttribute('content') || '' : '';
        }

        getBodyContentSnippet() {
            const main = document.querySelector('main');
            let text = '';
            if (main && main.innerText) {
                text = main.innerText;
            } else if (document.body && document.body.innerText) {
                text = document.body.innerText;
            }
            text = text.replace(/\s+/g, ' ').trim();
            if (text.length > 1200) {
                text = `${text.substring(0, 1200)}…`;
            }
            return text;
        }

        getHeadingsSnippet() {
            const headings = Array.from(document.querySelectorAll('h1, h2, h3'))
                .map((heading) => heading.innerText.trim())
                .filter(Boolean)
                .slice(0, 10);
            return headings.join(' | ');
        }

        getImportantLinksSnippet() {
            const anchors = Array.from(document.querySelectorAll('a[href]'))
                .filter((anchor) => anchor.innerText.trim().length > 0)
                .slice(0, 10)
                .map((anchor) => `${anchor.innerText.trim()} => ${anchor.href}`);
            return anchors.join(' | ');
        }

        loadConversationState() {
            const saved = sessionStorage.getItem('aicb_conversation');
            if (saved) {
                try {
                    const state = JSON.parse(saved);
                    this.messages = state.messages || [];
                    this.conversationId = state.conversationId || null;
                    this.leadCaptured = state.leadCaptured || false;
                    this.messageCount = state.messageCount || 0;
                    
                    if (this.messages.length > 0) {
                        this.container.find('.aicb-messages').empty();
                        this.messages.forEach(msg => {
                           const messageHtml = `<div class="aicb-message aicb-message-${msg.role}"><div class="aicb-message-bubble">${this.formatMessage(msg.content)}</div></div>`;
                           this.container.find('.aicb-messages').append(messageHtml);
                        });
                        this.scrollToBottom();
                    }
                } catch (error) {
                    console.error('Error loading conversation state:', error);
                }
            }
            this.hasConsent = localStorage.getItem('aicb_consent') === 'true';
        }

        trackEvent(eventName, data = {}) {
            // Not implemented in this version
        }

        escapeHTML(value) {
            return $('<div>').text(value || '').html();
        }

        escapeAttribute(value) {
            return this.escapeHTML(value || '');
        }
    }

    // Initialize chatbot when DOM is ready and container exists
    $(() => {
        if ($('#aicb-chatbot').length) {
            window.aiChatbot = new AIChatbot();
        }
    });

})(jQuery);
