<?php
/**
 * Template untuk halaman settings admin.
 *
 * @package AI_Chat_Assistant
 * @subpackage AI_Chat_Assistant/admin/views
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="notice notice-info">
        <p><?php _e('Configure your AI Chat Assistant settings below. You need to provide API credentials for the AI functionality to work.', 'ai-chat-assistant'); ?></p>
    </div>
    
    <form id="ai-chat-settings-form" method="post">
        <div id="ai-chat-settings-response" class="notice" style="display:none;"></div>
        
        <h2 class="nav-tab-wrapper">
            <a href="#general-settings" class="nav-tab nav-tab-active"><?php _e('General Settings', 'ai-chat-assistant'); ?></a>
            <a href="#ai-settings" class="nav-tab"><?php _e('AI Integration', 'ai-chat-assistant'); ?></a>
            <a href="#chat-settings" class="nav-tab"><?php _e('Chat Features', 'ai-chat-assistant'); ?></a>
        </h2>
        
        <div id="general-settings" class="tab-content">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="enable_ai"><?php _e('Enable AI Chat', 'ai-chat-assistant'); ?></label>
                    </th>
                    <td>
                        <select name="enable_ai" id="enable_ai">
                            <option value="yes" <?php selected($enable_ai_chat, 'yes'); ?>><?php _e('Yes', 'ai-chat-assistant'); ?></option>
                            <option value="no" <?php selected($enable_ai_chat, 'no'); ?>><?php _e('No', 'ai-chat-assistant'); ?></option>
                        </select>
                        <p class="description"><?php _e('Enable or disable the AI chat functionality.', 'ai-chat-assistant'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="enable_group"><?php _e('Enable Group Chat', 'ai-chat-assistant'); ?></label>
                    </th>
                    <td>
                        <select name="enable_group" id="enable_group">
                            <option value="yes" <?php selected($enable_group_chat, 'yes'); ?>><?php _e('Yes', 'ai-chat-assistant'); ?></option>
                            <option value="no" <?php selected($enable_group_chat, 'no'); ?>><?php _e('No', 'ai-chat-assistant'); ?></option>
                        </select>
                        <p class="description"><?php _e('Enable or disable the group chat functionality.', 'ai-chat-assistant'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="enable_user"><?php _e('Enable User-to-User Chat', 'ai-chat-assistant'); ?></label>
                    </th>
                    <td>
                        <select name="enable_user" id="enable_user">
                            <option value="yes" <?php selected($enable_user_chat, 'yes'); ?>><?php _e('Yes', 'ai-chat-assistant'); ?></option>
                            <option value="no" <?php selected($enable_user_chat, 'no'); ?>><?php _e('No', 'ai-chat-assistant'); ?></option>
                        </select>
                        <p class="description"><?php _e('Enable or disable the user-to-user chat functionality.', 'ai-chat-assistant'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div id="ai-settings" class="tab-content" style="display:none;">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="provider"><?php _e('AI Provider', 'ai-chat-assistant'); ?></label>
                    </th>
                    <td>
                        <select name="provider" id="provider">
                            <option value="openai" <?php selected($provider, 'openai'); ?>><?php _e('OpenAI', 'ai-chat-assistant'); ?></option>
                            <option value="anthropic" <?php selected($provider, 'anthropic'); ?>><?php _e('Anthropic', 'ai-chat-assistant'); ?></option>
                        </select>
                        <p class="description"><?php _e('Select the AI service provider.', 'ai-chat-assistant'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="api_key"><?php _e('API Key', 'ai-chat-assistant'); ?></label>
                    </th>
                    <td>
                        <input type="password" name="api_key" id="api_key" class="regular-text" value="<?php echo esc_attr($api_key); ?>" />
                        <button type="button" class="button" id="toggle-api-key"><?php _e('Show', 'ai-chat-assistant'); ?></button>
                        <p class="description"><?php _e('Enter your API key for the selected AI provider.', 'ai-chat-assistant'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="model"><?php _e('AI Model', 'ai-chat-assistant'); ?></label>
                    </th>
                    <td>
                        <select name="model" id="model" class="model-select" data-provider="<?php echo esc_attr($provider); ?>">
                            <optgroup label="<?php _e('OpenAI Models', 'ai-chat-assistant'); ?>" class="openai-models" <?php echo $provider !== 'openai' ? 'style="display:none;"' : ''; ?>>
                                <option value="gpt-3.5-turbo" <?php selected($model, 'gpt-3.5-turbo'); ?>>GPT-3.5 Turbo</option>
                                <option value="gpt-4" <?php selected($model, 'gpt-4'); ?>>GPT-4</option>
                                <option value="gpt-4-turbo" <?php selected($model, 'gpt-4-turbo'); ?>>GPT-4 Turbo</option>
                            </optgroup>
                            <optgroup label="<?php _e('Anthropic Models', 'ai-chat-assistant'); ?>" class="anthropic-models" <?php echo $provider !== 'anthropic' ? 'style="display:none;"' : ''; ?>>
                                <option value="claude-2" <?php selected($model, 'claude-2'); ?>>Claude 2</option>
                                <option value="claude-instant-1" <?php selected($model, 'claude-instant-1'); ?>>Claude Instant</option>
                            </optgroup>
                        </select>
                        <p class="description"><?php _e('Select the AI model to use for chat responses.', 'ai-chat-assistant'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div id="chat-settings" class="tab-content" style="display:none;">
            <h3><?php _e('Shortcodes', 'ai-chat-assistant'); ?></h3>
            <p><?php _e('Use these shortcodes to add chat functionality to your pages:', 'ai-chat-assistant'); ?></p>
            
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php _e('Feature', 'ai-chat-assistant'); ?></th>
                        <th><?php _e('Shortcode', 'ai-chat-assistant'); ?></th>
                        <th><?php _e('Description', 'ai-chat-assistant'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php _e('AI Chat', 'ai-chat-assistant'); ?></td>
                        <td><code>[ai_chat]</code></td>
                        <td><?php _e('Displays the AI chat interface.', 'ai-chat-assistant'); ?></td>
                    </tr>
                    <tr>
                        <td><?php _e('AI Chat with Topic', 'ai-chat-assistant'); ?></td>
                        <td><code>[ai_chat topic_id="123"]</code></td>
                        <td><?php _e('Displays the AI chat interface with a specific topic as context.', 'ai-chat-assistant'); ?></td>
                    </tr>
                    <tr>
                        <td><?php _e('Group Chat', 'ai-chat-assistant'); ?></td>
                        <td><code>[group_chat]</code></td>
                        <td><?php _e('Displays the group chat interface with available chat rooms.', 'ai-chat-assistant'); ?></td>
                    </tr>
                    <tr>
                        <td><?php _e('Specific Group Chat', 'ai-chat-assistant'); ?></td>
                        <td><code>[group_chat room_id="123"]</code></td>
                        <td><?php _e('Displays the interface for a specific chat room.', 'ai-chat-assistant'); ?></td>
                    </tr>
                    <tr>
                        <td><?php _e('User to User Chat', 'ai-chat-assistant'); ?></td>
                        <td><code>[user_chat]</code></td>
                        <td><?php _e('Displays the user-to-user chat interface.', 'ai-chat-assistant'); ?></td>
                    </tr>
                    <tr>
                        <td><?php _e('Chat with Specific User', 'ai-chat-assistant'); ?></td>
                        <td><code>[user_chat user_id="123"]</code></td>
                        <td><?php _e('Displays the chat interface with a specific user.', 'ai-chat-assistant'); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <p class="submit">
            <input type="hidden" name="action" value="ai_chat_save_settings">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('ai_chat_admin_nonce'); ?>">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Save Settings', 'ai-chat-assistant'); ?>">
        </p>
    </form>
</div>

<script>
    jQuery(document).ready(function($) {
        // Tab navigation
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
                $(this).text('<?php _e('Hide', 'ai-chat-assistant'); ?>');
            } else {
                apiKeyField.attr('type', 'password');
                $(this).text('<?php _e('Show', 'ai-chat-assistant'); ?>');
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
            submitBtn.val('<?php _e('Saving...', 'ai-chat-assistant'); ?>');
            
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
                    response.removeClass('notice-success').addClass('notice-error').html('<p><?php _e('An error occurred. Please try again.', 'ai-chat-assistant'); ?></p>').show();
                },
                complete: function() {
                    submitBtn.prop('disabled', false);
                    submitBtn.val('<?php _e('Save Settings', 'ai-chat-assistant'); ?>');
                    
                    // Hide message after 4 seconds
                    setTimeout(function() {
                        response.fadeOut();
                    }, 4000);
                }
            });
        });
    });
</script>