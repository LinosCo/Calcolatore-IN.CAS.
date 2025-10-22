/**
 * Complete Admin JavaScript for AI Chatbot Plugin
 */

jQuery(document).ready(function($) {
    
    // Initialize color pickers
    $('.color-picker').wpColorPicker();
    
    // Test API Connection
    $('#test-connection').on('click', function(e) {
        e.preventDefault();
        
        const $button = $(this);
        const originalText = $button.html();
        const $result = $('#connection-result');
        
        const apiKey = $('input[name="aicb_settings[openai_api_key]"]').val();
        if (!apiKey) {
            $result.html('<span class="error">Please enter your OpenAI API Key first.</span>').removeClass('success').addClass('error');
            return;
        }
        
        $button.html('<span class="dashicons dashicons-update spin"></span> ' + aicb_admin.strings.test_connection).prop('disabled', true);
        $result.removeClass('success error').html('');
        
        $.ajax({
            url: aicb_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'aicb_test_connection',
                nonce: aicb_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    $result.html('<span class="success">✓ ' + response.data.message + '</span>').addClass('success');
                } else {
                    $result.html('<span class="error">✗ ' + (response.data.message || 'Connection failed') + '</span>').addClass('error');
                }
            },
            error: function() {
                $result.html('<span class="error">✗ Network error. Please try again.</span>').addClass('error');
            },
            complete: function() {
                $button.html(originalText).prop('disabled', false);
            }
        });
    });
    
    // Media Upload Handler for Chatbot Icon
    $(document).on('click', '.aicb-upload-button', function(e) {
        e.preventDefault();
        const $button = $(this);
        const targetId = $button.data('target');
        const $input = $('#' + targetId);
        const $previewContainer = $button.siblings('.aicb-image-preview');

        const mediaUploader = wp.media({
            title: 'Choose Icon',
            button: { text: 'Choose Icon' },
            multiple: false,
            library: { type: 'image' }
        });
        
        mediaUploader.on('select', function() {
            const attachment = mediaUploader.state().get('selection').first().toJSON();
            $input.val(attachment.url);
            
            if ($previewContainer.length) {
                $previewContainer.html('<img src="' + attachment.url + '" style="max-width: 150px; height: auto;" /><button type="button" class="button aicb-remove-image" data-target="' + targetId + '">Remove</button>');
            } else {
                $button.after('<div class="aicb-image-preview" style="margin-top: 10px;"><img src="' + attachment.url + '" style="max-width: 150px; height: auto;" /><button type="button" class="button aicb-remove-image" data-target="' + targetId + '">Remove</button></div>');
            }
        });
        
        mediaUploader.open();
    });
    
    // Remove Image Handler
    $(document).on('click', '.aicb-remove-image', function(e) {
        e.preventDefault();
        const targetId = $(this).data('target');
        $('#' + targetId).val('');
        $(this).closest('.aicb-image-preview').remove();
    });

    // Repeater field for Suggested Questions
    $('.add-item').on('click', function() {
        const container = $(this).prev('div');
        const newItem = `
            <div class="repeater-item">
                <input type="text" name="${container.attr('id').replace('-container', '[]')}" value="" class="regular-text">
                <button type="button" class="button remove-item">Remove</button>
            </div>`;
        container.append(newItem);
    });

    $(document).on('click', '.remove-item', function() {
        $(this).closest('.repeater-item').remove();
    });

    // Knowledge Base Actions
    $('#reindex-content').on('click', function(e) {
        e.preventDefault();
        const $button = $(this);
        const originalText = $button.text();
        
        $button.text(aicb_admin.strings.reindexing).prop('disabled', true);
        
        $.post(aicb_admin.ajax_url, {
            action: 'aicb_reindex_content',
            nonce: aicb_admin.nonce
        })
        .done(response => {
            if (response.success) {
                showNotice(response.data.message, 'success');
            } else {
                showNotice('Error: ' + (response.data.message || 'Unknown error'), 'error');
            }
        })
        .fail(() => showNotice('An unexpected error occurred.', 'error'))
        .always(() => $button.text(originalText).prop('disabled', false));
    });

    $('#aicb-upload-file-button').on('click', () => $('#aicb-knowledge-file-input').click());

    $('#aicb-knowledge-file-input').on('change', function() {
        if (this.files.length === 0) return;

        const file = this.files[0];
        const formData = new FormData();
        formData.append('file', file);
        formData.append('action', 'aicb_upload_knowledge_file');
        formData.append('nonce', aicb_admin.nonce);

        const $spinner = $(this).siblings('.spinner').addClass('is-active');

        $.ajax({
            url: aicb_admin.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showNotice(response.data.message, 'success');
                    const fileInfo = response.data.file_info;
                    const newRow = `
                        <tr data-id="${response.data.file_id}">
                            <td><a href="${fileInfo.url}" target="_blank">${escapeHtml(fileInfo.file)}</a></td>
                            <td>${new Date(fileInfo.date).toLocaleDateString()}</td>
                            <td><button type="button" class="button button-link-delete aicb-remove-file" data-id="${response.data.file_id}">Remove</button></td>
                        </tr>
                    `;
                    const $table = $('#aicb-knowledge-files-list table');
                    if ($table.length) {
                        $table.find('tbody').append(newRow);
                    } else {
                        const tableHtml = `
                            <table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">
                                <thead><tr><th style="width: 60%;">File Name</th><th>Uploaded On</th><th>Actions</th></tr></thead>
                                <tbody>${newRow}</tbody>
                            </table>`;
                        $('#aicb-knowledge-files-list').html(tableHtml);
                    }
                    $('#aicb-no-files-message').hide();
                } else {
                    showNotice('Upload failed: ' + response.data.message, 'error');
                }
            },
            error: function() {
                showNotice('An error occurred during upload.', 'error');
            },
            complete: function() {
                $spinner.removeClass('is-active');
            }
        });
    });

    $('#aicb-knowledge-files-list').on('click', '.aicb-remove-file', function(e) {
        e.preventDefault();
        if (!confirm(aicb_admin.strings.confirm_remove_file)) return;

        const $button = $(this);
        const fileId = $button.data('id');
        
        $.post(aicb_admin.ajax_url, {
            action: 'aicb_remove_knowledge_file',
            nonce: aicb_admin.nonce,
            file_id: fileId
        })
        .done(response => {
            if (response.success) {
                showNotice(response.data.message, 'success');
                $button.closest('tr').fadeOut(300, function() { $(this).remove(); });
            } else {
                showNotice('Error: ' + (response.data.message || 'Could not remove file.'), 'error');
            }
        })
        .fail(() => showNotice('An unexpected error occurred.', 'error'));
    });
    
    // Tab Switching Logic
    $('.nav-tab-wrapper a').on('click', function(e) {
        e.preventDefault();
        const target = $(this).attr('href');

        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');

        $('.tab-content').removeClass('active');
        $(target).addClass('active');

        window.history.pushState(null, null, target);
    });

    // On page load, check hash and show correct tab
    const hash = window.location.hash;
    if (hash) {
        $('.nav-tab-wrapper a[href="' + hash + '"]').click();
    }

    // Utility function to show notices
    function showNotice(message, type = 'info', duration = 5000) {
        const $notice = $(`<div class="notice notice-${type} is-dismissible"><p>${escapeHtml(message)}</p></div>`);
        $('.wrap h1').after($notice);
        setTimeout(() => $notice.fadeOut(), duration);
    }

    function escapeHtml(text) {
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }
});