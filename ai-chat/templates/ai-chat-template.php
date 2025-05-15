<?php
/**
 * Template untuk AI Chat.
 *
 * @package AI_Chat_Assistant
 * @subpackage AI_Chat_Assistant/templates
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get conversation if provided
$conversation_id = isset($_GET['conversation_id']) ? intval($_GET['conversation_id']) : 0;
$conversation = null;
$messages = array();

if ($conversation_id > 0) {
    $conversation = $this->ai_integration->get_conversation($conversation_id);
    
    if (!is_wp_error($conversation)) {
        // Verify that the current user owns the conversation
        if (is_user_logged_in() && $conversation['conversation']['user_id'] != get_current_user_id()) {
            $conversation = null;
        } else {
            $messages = $conversation['messages'];
        }
    } else {
        $conversation = null;
    }
}

// Override conversation ID if not valid
if (!$conversation) {
    $conversation_id = 0;
}

// Get topics
$topics = get_posts(array(
    'post_type' => 'ai_chat_topic',
    'posts_per_page' => -1,
    'orderby' => 'title',
    'order' => 'ASC'
));

// Get conversation history for logged in users
$history = array();
if (is_user_logged_in()) {
    $history = $this->ai_integration->get_user_conversations(get_current_user_id());
}
?>

<div class="ai-chat-container" data-conversation-id="<?php echo $conversation_id; ?>" data-topic-id="<?php echo $topic_id; ?>">
    <?php if (count($topics) > 0 || !empty($history)) : ?>
        <div class="ai-chat-sidebar">
            <?php if (count($topics) > 0) : ?>
                <div class="ai-chat-topics">
                    <h3><?php _e('Topics', 'ai-chat-assistant'); ?></h3>
                    <select class="ai-chat-topic-select">
                        <option value="0"><?php _e('General Chat', 'ai-chat-assistant'); ?></option>
                        <?php foreach ($topics as $topic) : ?>
                            <option value="<?php echo $topic->ID; ?>" <?php selected($topic_id, $topic->ID); ?>>
                                <?php echo esc_html($topic->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($history)) : ?>
                <div class="ai-chat-history">
                    <h3><?php _e('Recent Conversations', 'ai-chat-assistant'); ?></h3>
                    <a href="#" class="ai-chat-new-chat-btn"><?php _e('Start New Chat', 'ai-chat-assistant'); ?></a>
                    <select class="ai-chat-history-select">
                        <option value=""><?php _e('Select a conversation', 'ai-chat-assistant'); ?></option>
                        <?php foreach ($history as $conv) : ?>
                            <option value="<?php echo $conv['id']; ?>" <?php selected($conversation_id, $conv['id']); ?>>
                                <?php echo esc_html($conv['title']); ?>
                                (<?php echo date_i18n(get_option('date_format'), strtotime($conv['updated_at'])); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <div class="ai-chat-main">
        <div class="ai-chat-header">
            <h3 class="ai-chat-title">
                <?php 
                if ($conversation_id > 0 && $conversation) {
                    echo esc_html($conversation['conversation']['title']);
                } elseif ($topic_id > 0) {
                    $topic = get_post($topic_id);
                    if ($topic) {
                        echo esc_html($topic->post_title);
                    } else {
                        _e('AI Chat', 'ai-chat-assistant');
                    }
                } else {
                    _e('AI Chat', 'ai-chat-assistant');
                }
                ?>
            </h3>
            
            <div class="ai-chat-actions">
                <?php if (is_user_logged_in() && $conversation_id > 0) : ?>
                    <button class="ai-chat-action-btn ai-chat-export-btn" title="<?php _e('Export Conversation', 'ai-chat-assistant'); ?>">
                        <span class="dashicons dashicons-download"></span>
                    </button>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="ai-chat-body">
            <div class="ai-chat-messages">
                <?php 
                if (!empty($messages)) {
                    foreach ($messages as $message) {
                        $message_class = $message['is_ai'] ? 'ai-message' : 'user-message';
                        ?>
                        <div class="ai-chat-message <?php echo $message_class; ?>">
                            <?php if ($message['is_ai']) : ?>
                                <div class="ai-chat-message-avatar">
                                    <img src="<?php echo file_exists(plugin_dir_path(dirname(__FILE__)) . 'public/images/ai-avatar.png') ? plugin_dir_url(dirname(__FILE__)) . 'public/images/ai-avatar.png' : 'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=identicon&f=y'; ?>" alt="AI">
                                </div>
                            <?php endif; ?>
                            
                            <div class="ai-chat-message-content">
                                <div class="ai-chat-message-text"><?php echo nl2br(esc_html($message['message'])); ?></div>
                                <div class="ai-chat-message-time">
                                    <?php 
                                    if (isset($message['created_at'])) {
                                        echo date_i18n(get_option('time_format'), strtotime($message['created_at']));
                                    } else {
                                        echo date_i18n(get_option('time_format'));
                                    }
                                    ?>
                                </div>
                            </div>
                            
                            <?php if (!$message['is_ai']) : ?>
                                <div class="ai-chat-message-avatar">
                                    <?php echo get_avatar($message['user_id'] ? $message['user_id'] : 0, 36); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php
                    }
                } else {
                    // Display welcome message
                    ?>
                    <div class="ai-chat-message ai-message">
                        <div class="ai-chat-message-avatar">
                            <img src="<?php echo file_exists(plugin_dir_path(dirname(__FILE__)) . 'public/images/ai-avatar.png') ? plugin_dir_url(dirname(__FILE__)) . 'public/images/ai-avatar.png' : 'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=identicon&f=y'; ?>" alt="AI">
                        </div>
                        <div class="ai-chat-message-content">
                            <div class="ai-chat-message-text">
                                <?php 
                                if ($topic_id > 0) {
                                    $topic = get_post($topic_id);
                                    if ($topic) {
                                        echo nl2br(esc_html(sprintf(
                                            __('Hello! You are chatting about: %s. How can I help you with this topic?', 'ai-chat-assistant'),
                                            $topic->post_title
                                        )));
                                    } else {
                                        echo nl2br(esc_html(__('Hello! How can I assist you today?', 'ai-chat-assistant')));
                                    }
                                } else {
                                    echo nl2br(esc_html(__('Hello! How can I assist you today?', 'ai-chat-assistant')));
                                }
                                ?>
                            </div>
                            <div class="ai-chat-message-time"><?php echo date_i18n(get_option('time_format')); ?></div>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>
        
        <div class="ai-chat-footer">
            <div class="ai-chat-typing">
                <?php _e('AI is typing...', 'ai-chat-assistant'); ?>
            </div>
            
            <div class="ai-chat-input-container">
                <textarea class="ai-chat-input" placeholder="<?php _e('Type your message here...', 'ai-chat-assistant'); ?>"></textarea>
                <button class="ai-chat-send-btn">
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Fallback styles if CSS file is not loaded */
    .ai-chat-container {
        display: flex;
        border: 1px solid #ddd;
        border-radius: 5px;
        overflow: hidden;
        height: 600px;
        max-height: 80vh;
    }
    
    .ai-chat-sidebar {
        width: 280px;
        padding: 15px;
        border-right: 1px solid #ddd;
        background: #f9f9f9;
    }
    
    .ai-chat-main {
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    
    .ai-chat-body {
        flex: 1;
        overflow-y: auto;
        padding: 15px;
        background-color: #f5f5f5;
    }
    
    .ai-chat-footer {
        padding: 15px;
        border-top: 1px solid #ddd;
    }
    
    .ai-chat-input-container {
        display: flex;
    }
    
    .ai-chat-input {
        flex: 1;
        border: 1px solid #ddd;
        border-radius: 20px;
        padding: 10px 15px;
        outline: none;
    }
    
    .ai-chat-send-btn {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #4e9af1;
        color: white;
        border: none;
        margin-left: 10px;
        cursor: pointer;
    }
    
    @media (max-width: 768px) {
        .ai-chat-container {
            flex-direction: column;
        }
        
        .ai-chat-sidebar {
            width: auto;
            border-right: none;
            border-bottom: 1px solid #ddd;
            height: auto;
        }
    }
</style>