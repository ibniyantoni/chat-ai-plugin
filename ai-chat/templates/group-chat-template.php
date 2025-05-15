<?php
/**
 * Template untuk Group Chat.
 *
 * @package AI_Chat_Assistant
 * @subpackage AI_Chat_Assistant/templates
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get room ID if provided
$room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : ($room_id ? $room_id : 0);
$room = null;
$room_messages = array();

// Get available rooms for the user
$user_rooms = $this->chat_room->get_user_rooms(get_current_user_id());
$public_rooms = $this->chat_room->get_public_rooms();

// Remove public rooms that user is already a member of
foreach ($public_rooms as $key => $public_room) {
    foreach ($user_rooms as $user_room) {
        if ($public_room['id'] == $user_room['id']) {
            unset($public_rooms[$key]);
            break;
        }
    }
}

// Get room details if ID is provided
if ($room_id > 0) {
    $is_member = $this->chat_room->is_user_in_room($room_id, get_current_user_id());
    
    if (!is_wp_error($is_member)) {
        if ($is_member) {
            $room = $this->chat_room->get_room($room_id);
            $room_messages = $this->chat_room->get_messages($room_id);
        } else {
            // Check if it's a public room
            $room = $this->chat_room->get_room($room_id);
            
            if (!is_wp_error($room) && !$room['is_private']) {
                // Auto-join public room
                $this->chat_room->add_user_to_room($room_id, get_current_user_id());
                $room_messages = $this->chat_room->get_messages($room_id);
            } else {
                $room = null;
            }
        }
    }
}

// If room is not valid, set ID to 0
if (is_wp_error($room) || !$room) {
    $room_id = 0;
}
?>

<div class="group-chat-container" data-room-id="<?php echo $room_id; ?>">
    <div class="group-chat-sidebar">
        <div class="group-chat-rooms">
            <h3><?php _e('My Chat Rooms', 'ai-chat-assistant'); ?></h3>
            
            <?php if (empty($user_rooms)) : ?>
                <p><?php _e('You are not a member of any chat rooms.', 'ai-chat-assistant'); ?></p>
            <?php else : ?>
                <?php foreach ($user_rooms as $user_room) : ?>
                    <div class="group-chat-room-item <?php echo ($room_id == $user_room['id']) ? 'active' : ''; ?>" data-room-id="<?php echo $user_room['id']; ?>">
                        <div class="group-chat-room-name"><?php echo esc_html($user_room['name']); ?></div>
                        <div class="group-chat-room-description">
                            <?php 
                            echo esc_html(wp_trim_words($user_room['description'], 10));
                            echo ' • ';
                            echo sprintf(_n('%d member', '%d members', $user_room['member_count'], 'ai-chat-assistant'), $user_room['member_count']);
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <?php if (!empty($public_rooms)) : ?>
                <h3><?php _e('Public Rooms', 'ai-chat-assistant'); ?></h3>
                <?php foreach ($public_rooms as $public_room) : ?>
                    <div class="group-chat-room-item" data-room-id="<?php echo $public_room['id']; ?>">
                        <div class="group-chat-room-name"><?php echo esc_html($public_room['name']); ?></div>
                        <div class="group-chat-room-description">
                            <?php 
                            echo esc_html(wp_trim_words($public_room['description'], 10));
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="group-chat-create-btn">
            <?php _e('Create New Room', 'ai-chat-assistant'); ?>
        </div>
    </div>
    
    <div class="group-chat-main">
        <?php if ($room_id > 0 && $room) : ?>
            <div class="group-chat-room-info">
                <div class="group-chat-room-title"><?php echo esc_html($room['name']); ?></div>
                <div class="group-chat-room-members">
                    <?php 
                    $room_users = $this->chat_room->get_room_users($room_id);
                    if (!is_wp_error($room_users)) {
                        echo sprintf(_n('%d member', '%d members', count($room_users), 'ai-chat-assistant'), count($room_users));
                    }
                    ?>
                </div>
            </div>
            
            <div class="ai-chat-body">
                <div class="ai-chat-messages">
                    <?php 
                    if (!empty($room_messages)) {
                        foreach ($room_messages as $message) {
                            $is_own_message = $message['user_id'] == get_current_user_id();
                            $message_class = $is_own_message ? 'user-message' : 'ai-message';
                            ?>
                            <div class="ai-chat-message <?php echo $message_class; ?>">
                                <?php if (!$is_own_message) : ?>
                                    <div class="ai-chat-message-avatar">
                                        <img src="<?php echo esc_url($message['user_avatar']); ?>" alt="<?php echo esc_attr($message['user_name']); ?>">
                                    </div>
                                <?php endif; ?>
                                
                                <div class="ai-chat-message-content">
                                    <?php if (!$is_own_message) : ?>
                                        <div class="ai-chat-message-sender"><?php echo esc_html($message['user_name']); ?></div>
                                    <?php endif; ?>
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
                    } else {
                        ?>
                        <div class="ai-chat-message system-message">
                            <div class="ai-chat-message-content" style="background-color: #f0f0f0; color: #666;">
                                <div class="ai-chat-message-text">
                                    <?php _e('No messages yet. Be the first to send a message!', 'ai-chat-assistant'); ?>
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
                    <textarea class="ai-chat-input" placeholder="<?php _e('Type your message here...', 'ai-chat-assistant'); ?>"></textarea>
                    <button class="ai-chat-send-btn">
                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                    </button>
                </div>
            </div>
        <?php else : ?>
            <div class="ai-chat-body">
                <div class="ai-chat-messages">
                    <div class="ai-chat-message system-message">
                        <div class="ai-chat-message-content" style="background-color: #f0f0f0; color: #666;">
                            <div class="ai-chat-message-text">
                                <?php _e('Select a chat room from the sidebar or create a new one to start chatting.', 'ai-chat-assistant'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="ai-chat-footer">
                <div class="ai-chat-input-container">
                    <textarea class="ai-chat-input" placeholder="<?php _e('Select a chat room first...', 'ai-chat-assistant'); ?>" disabled></textarea>
                    <button class="ai-chat-send-btn" disabled>
                        <span class="dashicons dashicons-arrow-right-alt2"></span>
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Create Room Modal -->
<div id="group-chat-create-modal" class="group-chat-modal">
    <div class="group-chat-modal-content">
        <span class="group-chat-modal-close">&times;</span>
        <h3><?php _e('Create New Chat Room', 'ai-chat-assistant'); ?></h3>
        
        <form id="group-chat-create-form">
            <div class="group-chat-form-group">
                <label for="room_name"><?php _e('Room Name', 'ai-chat-assistant'); ?></label>
                <input type="text" id="room_name" name="room_name" required>
            </div>
            
            <div class="group-chat-form-group">
                <label for="room_description"><?php _e('Description', 'ai-chat-assistant'); ?></label>
                <textarea id="room_description" name="room_description"></textarea>
            </div>
            
            <div class="group-chat-form-group">
                <label>
                    <input type="checkbox" id="room_private" name="room_private">
                    <?php _e('Make this room private', 'ai-chat-assistant'); ?>
                </label>
                <p class="description"><?php _e('Private rooms are only visible to invited members.', 'ai-chat-assistant'); ?></p>
            </div>
            
            <button type="submit" id="room_submit" class="group-chat-submit-btn"><?php _e('Create Room', 'ai-chat-assistant'); ?></button>
        </form>
    </div>
</div>