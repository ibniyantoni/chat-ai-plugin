<?php
/**
 * Mengelola percakapan antar user.
 *
 * @package AI_Chat_Assistant
 * @subpackage AI_Chat_Assistant/includes
 */
class AI_Chat_User {

    /**
     * Database instance.
     *
     * @var AI_Chat_Database
     */
    private $database;

    /**
     * Notification instance.
     *
     * @var AI_Chat_Notification
     */
    private $notification;

    /**
     * Initialize class.
     *
     * @param AI_Chat_Database $database Database instance.
     * @param AI_Chat_Notification $notification Notification instance.
     */
    public function __construct($database, $notification) {
        $this->database = $database;
        $this->notification = $notification;
    }

    /**
     * Membuat atau mendapatkan percakapan antar dua user.
     *
     * @param int $user_one First user ID.
     * @param int $user_two Second user ID.
     * @return int|WP_Error Conversation ID or error.
     */
    public function get_or_create_conversation($user_one, $user_two) {
        // Ensure user_one is the lower ID for consistency
        if ($user_one > $user_two) {
            $temp = $user_one;
            $user_one = $user_two;
            $user_two = $temp;
        }
        
        // Check if conversation already exists
        $conversation = $this->database->get_data('ai_chat_user_conversations', array(
            'where' => array(
                'user_one' => $user_one,
                'user_two' => $user_two
            )
        ), true);
        
        if ($conversation) {
            return $conversation['id'];
        }
        
        // Create new conversation
        $conversation_id = $this->database->insert_data('ai_chat_user_conversations', array(
            'user_one' => $user_one,
            'user_two' => $user_two
        ));
        
        if (!$conversation_id) {
            return new WP_Error('db_error', __('Failed to create conversation', 'ai-chat-assistant'));
        }
        
        return $conversation_id;
    }

    /**
     * Kirim pesan ke user lain.
     *
     * @param int $conversation_id Conversation ID.
     * @param int $sender_id Sender user ID.
     * @param string $message Message content.
     * @return int|WP_Error Message ID or error.
     */
    public function send_message($conversation_id, $sender_id, $message) {
        if (empty($message)) {
            return new WP_Error('empty_message', __('Message cannot be empty', 'ai-chat-assistant'));
        }
        
        // Verify conversation exists and user is part of it
        $conversation = $this->get_conversation($conversation_id);
        if (is_wp_error($conversation)) {
            return $conversation;
        }
        
        if ($conversation['user_one'] != $sender_id && $conversation['user_two'] != $sender_id) {
            return new WP_Error('not_participant', __('User is not a participant in this conversation', 'ai-chat-assistant'));
        }
        
        // Insert message
        $message_id = $this->database->insert_data('ai_chat_user_messages', array(
            'conversation_id' => $conversation_id,
            'sender_id' => $sender_id,
            'message' => $message
        ));
        
        if (!$message_id) {
            return new WP_Error('db_error', __('Failed to send message', 'ai-chat-assistant'));
        }
        
        // Update conversation timestamp
        $this->database->update_data('ai_chat_user_conversations', array(
            'updated_at' => current_time('mysql')
        ), array('id' => $conversation_id));
        
        // Send notification to recipient
        $recipient_id = ($conversation['user_one'] == $sender_id) ? $conversation['user_two'] : $conversation['user_one'];
        
        $sender = get_userdata($sender_id);
        if ($sender) {
            $this->notification->send_notification(
                $recipient_id,
                sprintf(__('New message from %s: %s', 'ai-chat-assistant'), $sender->display_name, wp_trim_words($message, 10)),
                'user_chat_message',
                array('conversation_id' => $conversation_id)
            );
        }
        
        return $message_id;
    }

    /**
     * Ambil pesan dalam percakapan.
     *
     * @param int $conversation_id Conversation ID.
     * @param int $limit Optional. Number of messages to retrieve. Default 50.
     * @param int $offset Optional. Offset for pagination. Default 0.
     * @return array|WP_Error Messages or error.
     */
    public function get_messages($conversation_id, $limit = 50, $offset = 0) {
        // Verify conversation exists
        $conversation = $this->get_conversation($conversation_id);
        if (is_wp_error($conversation)) {
            return $conversation;
        }
        
        // Get messages
        $messages = $this->database->get_data('ai_chat_user_messages', array(
            'where' => array('conversation_id' => $conversation_id),
            'orderby' => 'created_at',
            'order' => 'DESC',
            'limit' => $limit,
            'offset' => $offset
        ));
        
        // Add user data to messages
        foreach ($messages as &$message) {
            $user = get_userdata($message['sender_id']);
            $message['sender_name'] = $user ? $user->display_name : __('Unknown User', 'ai-chat-assistant');
            $message['sender_avatar'] = get_avatar_url($message['sender_id']);
        }
        
        return array_reverse($messages);
    }

    /**
     * Mark messages as read.
     *
     * @param int $conversation_id Conversation ID.
     * @param int $user_id User ID marking messages as read.
     * @return bool|WP_Error True on success or error.
     */
    public function mark_messages_as_read($conversation_id, $user_id) {
        // Verify conversation exists and user is part of it
        $conversation = $this->get_conversation($conversation_id);
        if (is_wp_error($conversation)) {
            return $conversation;
        }
        
        if ($conversation['user_one'] != $user_id && $conversation['user_two'] != $user_id) {
            return new WP_Error('not_participant', __('User is not a participant in this conversation', 'ai-chat-assistant'));
        }
        
        // Mark all messages from the other user as read
        $result = $this->database->update_data(
            'ai_chat_user_messages',
            array('is_read' => 1),
            array(
                'conversation_id' => $conversation_id,
                'sender_id' => ($conversation['user_one'] == $user_id) ? $conversation['user_two'] : $conversation['user_one'],
                'is_read' => 0
            )
        );
        
        if (false === $result) {
            return new WP_Error('db_error', __('Failed to mark messages as read', 'ai-chat-assistant'));
        }
        
        return true;
    }

    /**
     * Ambil informasi percakapan.
     *
     * @param int $conversation_id Conversation ID.
     * @return array|WP_Error Conversation data or error.
     */
    public function get_conversation($conversation_id) {
        $conversation = $this->database->get_data('ai_chat_user_conversations', array(
            'where' => array('id' => $conversation_id)
        ), true);
        
        if (!$conversation) {
            return new WP_Error('not_found', __('Conversation not found', 'ai-chat-assistant'));
        }
        
        return $conversation;
    }

    /**
     * Ambil daftar percakapan user.
     *
     * @param int $user_id User ID.
     * @return array List of conversations.
     */
    public function get_user_conversations($user_id) {
        // Get conversations where user is a participant
        $conversations = $this->database->get_data('ai_chat_user_conversations', array(
            'where' => array(
                'user_one' => $user_id
            ),
            'orderby' => 'updated_at',
            'order' => 'DESC'
        ));
        
        $more_conversations = $this->database->get_data('ai_chat_user_conversations', array(
            'where' => array(
                'user_two' => $user_id
            ),
            'orderby' => 'updated_at',
            'order' => 'DESC'
        ));
        
        $conversations = array_merge($conversations, $more_conversations);
        
        // Sort by updated_at
        usort($conversations, function($a, $b) {
            return strtotime($b['updated_at']) - strtotime($a['updated_at']);
        });
        
        // Add additional data
        foreach ($conversations as &$conversation) {
            // Add other user data
            $other_user_id = ($conversation['user_one'] == $user_id) ? $conversation['user_two'] : $conversation['user_one'];
            $other_user = get_userdata($other_user_id);
            
            $conversation['other_user_id'] = $other_user_id;
            $conversation['other_user_name'] = $other_user ? $other_user->display_name : __('Unknown User', 'ai-chat-assistant');
            $conversation['other_user_avatar'] = get_avatar_url($other_user_id);
            
            // Get last message
            $last_message = $this->database->get_data('ai_chat_user_messages', array(
                'where' => array('conversation_id' => $conversation['id']),
                'orderby' => 'created_at',
                'order' => 'DESC',
                'limit' => 1
            ), true);
            
            $conversation['last_message'] = $last_message ? $last_message['message'] : '';
            $conversation['last_message_time'] = $last_message ? $last_message['created_at'] : '';
            $conversation['last_message_is_mine'] = $last_message ? ($last_message['sender_id'] == $user_id) : false;
            
            // Get unread count
            $unread_count = $this->database->get_data('ai_chat_user_messages', array(
                'where' => array(
                    'conversation_id' => $conversation['id'],
                    'sender_id' => $other_user_id,
                    'is_read' => 0
                )
            ));
            
            $conversation['unread_count'] = count($unread_count);
        }
        
        return $conversations;
    }

    /**
     * Cek apakah user online.
     *
     * @param int $user_id User ID.
     * @return bool True if online, false otherwise.
     */
    public function is_user_online($user_id) {
        // Get user's last activity time from user meta
        $last_activity = get_user_meta($user_id, 'ai_chat_last_activity', true);
        
        if (!$last_activity) {
            return false;
        }
        
        // Consider user online if active in the last 5 minutes
        return (time() - $last_activity) < 300;
    }

    /**
     * Update status online user.
     *
     * @param int $user_id User ID.
     */
    public function update_user_activity($user_id) {
        update_user_meta($user_id, 'ai_chat_last_activity', time());
    }

    /**
     * Ambil daftar user yang bisa dikontak.
     *
     * @param int $user_id Current user ID.
     * @param string $search Optional. Search term for filtering users.
     * @return array List of users.
     */
    public function get_contactable_users($user_id, $search = '') {
        $args = array(
            'exclude' => array($user_id),
            'number' => 20,
            'fields' => array('ID', 'display_name', 'user_email')
        );
        
        if (!empty($search)) {
            $args['search'] = '*' . $search . '*';
            $args['search_columns'] = array('user_login', 'user_email', 'display_name');
        }
        
        $users = get_users($args);
        $result = array();
        
        foreach ($users as $user) {
            $result[] = array(
                'id' => $user->ID,
                'name' => $user->display_name,
                'email' => $user->user_email,
                'avatar' => get_avatar_url($user->ID),
                'is_online' => $this->is_user_online($user->ID)
            );
        }
        
        return $result;
    }
}