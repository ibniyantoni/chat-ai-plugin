<?php
/**
 * Template untuk User-to-User Chat.
 *
 * @package AI_Chat_Assistant
 * @subpackage AI_Chat_Assistant/templates
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get conversation ID or user ID if provided
$conversation_id = isset($_GET['conversation_id']) ? intval($_GET['conversation_id']) : 0;
$recipient_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : ($user_id ? $user_id : 0);

$conversation = null;
$messages = array();
$recipient = null;

// Get conversation if ID is provided
if ($conversation_id > 0) {
    $conversation = $this->user_chat->get_conversation($conversation_id);
    
    if (!is_wp_error($conversation)) {
        // Verify that the current user is a participant
        if ($conversation['user_one'] == get_current_user_id() || $conversation['user_two'] == get_current_user_id()) {
            $messages = $this->user_chat->get_messages($conversation_id);
            
            // Get recipient ID
            $recipient_id = ($conversation['user_one'] == get_current_user_id()) ? $conversation['user_two'] : $conversation['user_one'];
            $recipient = get_userdata($recipient_id);
            
            // Mark messages as read
            $this->user_chat->mark_messages_as_read($conversation_id, get_current_user_id());
        } else {
            $conversation = null;
        }
    } else {
        $conversation = null;
    }
}

// Get recipient if user ID is provided but no conversation
if (!$conversation && $recipient_id > 0) {
    $recipient = get_userdata($recipient_id);
    
    if ($recipient && $recipient_id != get_current_user_id()) {
        // Check if conversation already exists
        $existing = $this->user_chat->get_user_conversations(get_current_user_id());
        
        foreach ($existing as $conv) {
            if ($conv['other_user_id'] == $recipient_id) {
                $conversation_id = $conv['id'];
                $conversation = $this->user_chat->get_conversation($conversation_id);
                $messages = $this->user_chat->get_messages($conversation_id);
                
                // Mark messages as read
                $this->user_chat->mark_messages_as_read($conversation_id, get_current_user_id());
                break;
            }
        }
    } else {
        $recipient = null;
        $recipient_id = 0;
    }
}

// Update user activity
$this->user_chat->update_user_activity(get_current_user_id());
?>

<div class="user-chat-container" data-conversation-id="<?php echo $conversation_id; ?>" data-recipient-id="<?php echo $recipient_id; ?>">
    <div class="user-chat-sidebar">
        <div class="user-chat-search">
            <input type="text" placeholder="<?php _e('Search contacts...', 'ai-chat-assistant'); ?>">
        </div>
        
        <div class="user-chat-contacts">
            <?php 
            // Contacts will be loaded via JavaScript
            ?>
            <div class="user-chat-loading">
                <?php _e('Loading conversations...', 'ai-chat-assistant'); ?>
            </div>
        </div>
        
        <div class="user-chat-new-btn">
            <?php _e('New Conversation', 'ai-chat-assistant'); ?>
        </div>
    </div>
    
    <div class="user-chat-main">
        <div class="ai-chat-header">
            <h3 class="ai-chat-title">
                <?php 
                if ($recipient) {
                    echo esc_html($recipient->display_name);
                    
                    // Show online status
                    if ($this->user_chat->is_user_online($recipient_id)) {
                        echo ' <span class="user-chat-status online">' . __('Online', 'ai-chat-assistant') . '</span>';
                    }
                } else {
                    _e('User Chat', 'ai-chat-assistant');
                }
                ?>
            </h3>
        </div>
        
        <div class="ai-chat-body">
            <div class="ai-chat-messages">
                <?php 
                if (!empty($messages)) {
                    foreach ($messages as $message) {
                        $is_own_message = $message['sender_id'] == get_current_user_id();
                        $message_class = $is_own_message ? 'user-message' : 'ai-message';
                        ?>
                        <div class="ai-chat-message <?php echo $message_class; ?>">
                            <?php if (!$is_own_message) : ?>
                                <div class="ai-chat-message-avatar">
                                    <img src="<?php echo esc_url($message['sender_avatar']); ?>" alt="<?php echo esc_attr($message['sender_name']); ?>">
                                </div>
                            <?php endif; ?>
                            
                            <div class="ai-chat-message-content">
                                <div class="ai-chat-message-text"><?php echo nl2br(esc_html($message['message'])); ?></div>
                                <div class="ai-chat-message-time">
                                    <?php echo date_i18n(get_option('time_format'), strtotime($message['created_at'])); ?>
                                </div>
                            </div>
                            
                            <?php if ($is_own_message) : ?>
                                <div class="ai-chat-message-avatar">
                                    <?php echo get_avatar(get_current_user_id(), 36); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php
                    }
                } elseif ($recipient) {
                    ?>
                    <div class="ai-chat-message system-message">
                        <div class="ai-chat-message-content" style="background-color: #f0f0f0; color: #666;">
                            <div class="ai-chat-message-text">
                                <?php echo sprintf(__('Start a conversation with %s.', 'ai-chat-assistant'), esc_html($recipient->display_name)); ?>
                            </div>
                        </div>
                    </div>
                    <?php
                } else {
                    ?>
                    <div class="ai-chat-message system-message">
                        <div class="ai-chat-message-content" style="background-color: #f0f0f0; color: #666;">
                            <div class="ai-chat-message-text">
                                <?php _e('Select a contact from the sidebar or start a new conversation.', 'ai-chat-assistant'); ?>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>
        
        <div class="ai-chat-footer">
            <div class="ai-chat-input-container">
                <textarea class="ai-chat-input" placeholder="<?php 
                    if ($recipient) {
                        echo sprintf(__('Send a message to %s...', 'ai-chat-assistant'), esc_attr($recipient->display_name));
                    } else {
                        echo __('Select a contact first...', 'ai-chat-assistant');
                    }
                ?>" <?php echo !$recipient ? 'disabled' : ''; ?>></textarea>
                <button class="ai-chat-send-btn" <?php echo !$recipient ? 'disabled' : ''; ?>>
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- New Chat Modal -->
<div id="user-chat-new-modal" class="user-chat-modal">
    <div class="user-chat-modal-content">
        <span class="user-chat-modal-close">&times;</span>
        <h3><?php _e('Start New Conversation', 'ai-chat-assistant'); ?></h3>
        
        <form id="user-chat-new-form">
            <div class="user-chat-form-group">
                <label for="user_search"><?php _e('Search for a user:', 'ai-chat-assistant'); ?></label>
                <input type="text" id="user_search" name="user_search" placeholder="<?php _e('Type name or email...', 'ai-chat-assistant'); ?>">
            </div>
            
            <div id="user_search_results" class="user-chat-search-results">
                <p><?php _e('Type at least 2 characters to search for users.', 'ai-chat-assistant'); ?></p>
            </div>
        </form>
    </div>
</div>

<style>
    /* Additional styles for user chat */
    .user-chat-status {
        font-size: 12px;
        font-weight: normal;
        margin-left: 5px;
    }
    
    .user-chat-status.online {
        color: #38c172;
    }
    
    .user-chat-modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.4);
    }
    
    .user-chat-modal-content {
        background-color: #fefefe;
        margin: 10% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 50%;
        max-width: 500px;
        border-radius: 5px;
    }
    
    .user-chat-modal-close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }
    
    .user-chat-modal-close:hover {
        color: black;
    }
    
    .user-chat-form-group {
        margin-bottom: 15px;
    }
    
    .user-chat-form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
    }
    
    .user-chat-form-group input {
        width: 100%;
        padding: 8px;
        border-radius: 4px;
        border: 1px solid #ddd;
    }
    
    .user-chat-search-results {
        max-height: 300px;
        overflow-y: auto;
        margin-top: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 10px;
    }
    
    .user-chat-search-result {
        padding: 8px;
        cursor: pointer;
        border-radius: 4px;
    }
    
    .user-chat-search-result:hover {
        background-color: #f0f0f0;
    }
</style>