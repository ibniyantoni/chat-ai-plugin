/**
 * Admin JavaScript untuk plugin AI Chat Assistant.
 *
 * @package AI_Chat_Assistant
 * @subpackage AI_Chat_Assistant/admin
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        
        // Tab navigation in settings page
        $('.nav-tab').on('click', function(e) {
            e.preventDefault();
            
            $('.nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            
            $('.tab-content').hide();
            $($(this).attr('href')).show();
        });
        
        // Toggle API key visibility
        $('#toggle-api-key').on('click', function() {
            var apiKeyField = $('#api_key');
            
            if (apiKeyField.attr('type') === 'password') {
                apiKeyField.attr('type', 'text');
                $(this).text(ai_chat_admin.strings.hide || 'Hide');
            } else {
                apiKeyField.attr('type', 'password');
                $(this).text(ai_chat_admin.strings.show || 'Show');
            }
        });
        
        // Switch model options based on provider
        $('#provider').on('change', function() {
            var provider = $(this).val();
            
            $('.model-select optgroup').hide();
            $('.' + provider + '-models').show();
            
            // Select first option in visible group
            var firstOption = $('.' + provider + '-models option:first').val();
            $('#model').val(firstOption);
        });
        
        // Settings form submission
        $('#ai-chat-settings-form').on('submit', function(e) {
            e.preventDefault();
            
            var form = $(this);
            var response = $('#ai-chat-settings-response');
            var submitBtn = $('#submit');
            
            submitBtn.prop('disabled', true);
            submitBtn.val(ai_chat_admin.strings.processing || 'Processing...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: form.serialize(),
                success: function(data) {
                    if (data.success) {
                        response.removeClass('notice-error').addClass('notice-success').html('<p>' + data.data + '</p>').show();
                    } else {
                        response.removeClass('notice-success').addClass('notice-error').html('<p>' + data.data + '</p>').show();
                    }
                },
                error: function() {
                    response.removeClass('notice-success').addClass('notice-error').html('<p>' + (ai_chat_admin.strings.ajax_error || 'An error occurred. Please try again.') + '</p>').show();
                },
                complete: function() {
                    submitBtn.prop('disabled', false);
                    submitBtn.val(ai_chat_admin.strings.save_settings || 'Save Settings');
                    
                    // Hide message after 4 seconds
                    setTimeout(function() {
                        response.fadeOut();
                    }, 4000);
                }
            });
        });
        
        // Modal handling
        $('.ai-chat-modal-close').on('click', function() {
            $(this).closest('.ai-chat-modal').hide();
        });
        
        $(window).on('click', function(e) {
            if ($(e.target).hasClass('ai-chat-modal')) {
                $('.ai-chat-modal').hide();
            }
        });
        
        // Delete confirmation
        $('.delete-btn').on('click', function(e) {
            if (!confirm(ai_chat_admin.strings.confirm_delete || 'Are you sure you want to delete this item? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
        
    });

})(jQuery);