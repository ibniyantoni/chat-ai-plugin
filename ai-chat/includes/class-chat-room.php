<?php
/**
 * Mengelola forum chat room.
 *
 * @package AI_Chat_Assistant
 * @subpackage AI_Chat_Assistant/includes
 */
class AI_Chat_Room {

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
     * Buat chat room baru.
     *
     * @param string $name Room name.
     * @param string $description Room description.
     * @param int $created_by User ID of creator.
     * @param bool $is_private Whether the room is private.
     * @return int|WP_Error Room ID or error.
     */
    public function create_room($name, $description, $created_by, $is_private = false) {
        if (empty($name)) {
            return new WP_Error('empty_name', __('Room name cannot be empty', 'ai-chat-assistant'));
        }
        
        // Insert room
        $room_id = $this->database->insert_data('ai_chat_rooms', array(
            'name' => $name,
            'description' => $description,
            'created_by' => $created_by,
            'is_private' => $is_private ? 1 : 0
        ));
        
        if (!$room_id) {
            return new WP_Error('db_error', __('Failed to create chat room', 'ai-chat-assistant'));
        }
        
        // Add creator as room moderator
        $result = $this->database->insert_data('ai_chat_room_users', array(
            'room_id' => $room_id,
            'user_id' => $created_by,
            'is_moderator' => 1
        ));
        
        if (!$result) {
            // Rollback
            $this->database->delete_data('ai_chat_rooms', array('id' => $room_id));
            return new WP_Error('db_error', __('Failed to add creator to chat room', 'ai-chat-assistant'));
        }
        
        return $room_id;
    }

    /**
     * Update chat room.
     *
     * @param int $room_id Room ID.
     * @param string $name Room name.
     * @param string $description Room description.
     * @param bool $is_private Whether the room is private.
     * @return bool|WP_Error True on success or error.
     */
    public function update_room($room_id, $name, $description, $is_private = false) {
        if (empty($name)) {
            return new WP_Error('empty_name', __('Room name cannot be empty', 'ai-chat-assistant'));
        }
        
        // Update room
        $result = $this->database->update_data('ai_chat_rooms', array(
            'name' => $name,
            'description' => $description,
            'is_private' => $is_private ? 1 : 0
        ), array('id' => $room_id));
        
        if (false === $result) {
            return new WP_Error('db_error', __('Failed to update chat room', 'ai-chat-assistant'));
        }
        
        return true;
    }

    /**
     * Hapus chat room.
     *
     * @param int $room_id Room ID.
     * @return bool|WP_Error True on success or error.
     */
    public function delete_room($room_id) {
        // Delete room users
        $this->database->delete_data('ai_chat_room_users', array('room_id' => $room_id));
        
        // Delete room messages
        $this->database->delete_data('ai_chat_room_messages', array('room_id' => $room_id));
        
        // Delete room
        $result = $this->database->delete_data('ai_chat_rooms', array('id' => $room_id));
        
        if (false === $result) {
            return new WP_Error('db_error', __('Failed to delete chat room', 'ai-chat-assistant'));
        }
        
        return true;
    }

    /**
     * Tambahkan user ke chat room.
     *
     * @param int $room_id Room ID.
     * @param int $user_id User ID.
     * @param bool $is_moderator Whether the user is a moderator.
     * @return bool|WP_Error True on success or error.
     */
    public function add_user_to_room($room_id, $user_id, $is_moderator = false) {
        // Check if user already exists in room
        $existing = $this->database->get_data('ai_chat_room_users', array(
            'where' => array(
                'room_id' => $room_id,
                'user_id' => $user_id
            )
        ), true);
        
        if ($existing) {
            // Update moderator status if different
            if ($existing['is_moderator'] != ($is_moderator ? 1 : 0)) {
                return $this->database->update_data('ai_chat_room_users', array(
                    'is_moderator' => $is_moderator ? 1 : 0
                ), array(
                    'room_id' => $room_id,
                    'user_id' => $user_id
                ));
            }
            
            return true;
        }
        
        // Add user to room
        $result = $this->database->insert_data('ai_chat_room_users', array(
            'room_id' => $room_id,
            'user_id' => $user_id,
            'is_moderator' => $is_moderator ? 1 : 0
        ));
        
        if (!$result) {
            return new WP_Error('db_error', __('Failed to add user to chat room', 'ai-chat-assistant'));
        }
        
        // Send notification to user
        $room = $this->get_room($room_id);
        if (!is_wp_error($room)) {
            $this->notification->send_notification(
                $user_id,
                sprintf(__('You have been added to the chat room: %s', 'ai-chat-assistant'), $room['name']),
                'chat_room_invitation',
                array('room_id' => $room_id)
            );
        }
        
        return true;
    }

    /**
     * Hapus user dari chat room.
     *
     * @param int $room_id Room ID.
     * @param int $user_id User ID.
     * @return bool|WP_Error True on success or error.
     */
    public function remove_user_from_room($room_id, $user_id) {
        // Check if user is room creator
        $room = $this->get_room($room_id);
        if (is_wp_error($room)) {
            return $room;
        }
        
        if ($room['created_by'] == $user_id) {
            return new WP_Error('cannot_remove_creator', __('Cannot remove the room creator', 'ai-chat-assistant'));
        }
        
        // Remove user from room
        $result = $this->database->delete_data('ai_chat_room_users', array(
            'room_id' => $room_id,
            'user_id' => $user_id
        ));
        
        if (false === $result) {
            return new WP_Error('db_error', __('Failed to remove user from chat room', 'ai-chat-assistant'));
        }
        
        return true;
    }

    /**
     * Kirim pesan ke chat room.
     *
     * @param int $room_id Room ID.
     * @param int $user_id User ID.
     * @param string $message Message content.
     * @return int|WP_Error Message ID or error.
     */
    public function send_message($room_id, $user_id, $message) {
        if (empty($message)) {
            return new WP_Error('empty_message', __('Message cannot be empty', 'ai-chat-assistant'));
        }
        
        // Check if user is in room
        $is_member = $this->is_user_in_room($room_id, $user_id);
        if (is_wp_error($is_member)) {
            return $is_member;
        }
        
        if (!$is_member) {
            return new WP_Error('not_member', __('User is not a member of this chat room', 'ai-chat-assistant'));
        }
        
        // Insert message
        $message_id = $this->database->insert_data('ai_chat_room_messages', array(
            'room_id' => $room_id,
            'user_id' => $user_id,
            'message' => $message
        ));
        
        if (!$message_id) {
            return new WP_Error('db_error', __('Failed to send message', 'ai-chat-assistant'));
        }
        
        // Update room timestamp
        $this->database->update_data('ai_chat_rooms', array(
            'updated_at' => current_time('mysql')
        ), array('id' => $room_id));
        
        // Send notifications to room members
        $this->notify_room_members($room_id, $user_id, $message);
        
        return $message_id;
    }

    /**
     * Kirim notifikasi ke semua anggota chat room.
     *
     * @param int $room_id Room ID.
     * @param int $sender_id Sender user ID.
     * @param string $message Message content.
     */
    private function notify_room_members($room_id, $sender_id, $message) {
        // Get room members
        $members = $this->get_room_users($room_id);
        if (is_wp_error($members)) {
            return;
        }
        
        $room = $this->get_room($room_id);
        if (is_wp_error($room)) {
            return;
        }
        
        $sender = get_userdata($sender_id);
        if (!$sender) {
            return;
        }
        
        $notification_message = sprintf(
            __('%s sent a message in %s: %s', 'ai-chat-assistant'),
            $sender->display_name,
            $room['name'],
            wp_trim_words($message, 10)
        );
        
        foreach ($members as $member) {
            // Don't notify sender
            if ($member['user_id'] == $sender_id) {
                continue;
            }
            
            $this->notification->send_notification(
                $member['user_id'],
                $notification_message,
                'chat_room_message',
                array('room_id' => $room_id)
            );
        }
    }

    /**
     * Ambil pesan chat room.
     *
     * @param int $room_id Room ID.
     * @param int $limit Optional. Number of messages to retrieve. Default 50.
     * @param int $offset Optional. Offset for pagination. Default 0.
     * @return array|WP_Error Messages or error.
     */
    public function get_messages($room_id, $limit = 50, $offset = 0) {
        // Get messages
        $messages = $this->database->get_data('ai_chat_room_messages', array(
            'where' => array('room_id' => $room_id),
            'orderby' => 'created_at',
            'order' => 'DESC',
            'limit' => $limit,
            'offset' => $offset
        ));
        
        // Add user data to messages
        foreach ($messages as &$message) {
            $user = get_userdata($message['user_id']);
            $message['user_name'] = $user ? $user->display_name : __('Unknown User', 'ai-chat-assistant');
            $message['user_avatar'] = get_avatar_url($message['user_id']);
        }
        
        return array_reverse($messages);
    }

    /**
     * Ambil informasi chat room.
     *
     * @param int $room_id Room ID.
     * @return array|WP_Error Room data or error.
     */
    public function get_room($room_id) {
        $room = $this->database->get_data('ai_chat_rooms', array(
            'where' => array('id' => $room_id)
        ), true);
        
        if (!$room) {
            return new WP_Error('not_found', __('Chat room not found', 'ai-chat-assistant'));
        }
        
        return $room;
    }

    /**
     * Ambil daftar chat room untuk user tertentu.
     *
     * @param int $user_id User ID.
     * @return array List of rooms.
     */
    public function get_user_rooms($user_id) {
        // Get room IDs where user is a member
        $room_users = $this->database->get_data('ai_chat_room_users', array(
            'where' => array('user_id' => $user_id)
        ));
        
        if (empty($room_users)) {
            return array();
        }
        
        $room_ids = array_column($room_users, 'room_id');
        $rooms = array();
        
        // Get room details for each ID
        foreach ($room_ids as $room_id) {
            $room = $this->get_room($room_id);
            if (!is_wp_error($room)) {
                // Add user's role in the room
                foreach ($room_users as $room_user) {
                    if ($room_user['room_id'] == $room_id) {
                        $room['is_moderator'] = (bool) $room_user['is_moderator'];
                        break;
                    }
                }
                
                // Add creator info
                $creator = get_userdata($room['created_by']);
                $room['creator_name'] = $creator ? $creator->display_name : __('Unknown User', 'ai-chat-assistant');
                
                // Add member count
                $members = $this->get_room_users($room_id);
                $room['member_count'] = is_wp_error($members) ? 0 : count($members);
                
                $rooms[] = $room;
            }
        }
        
        return $rooms;
    }

    /**
     * Ambil daftar room yang bisa diakses publik.
     *
     * @return array List of public rooms.
     */
    public function get_public_rooms() {
        return $this->database->get_data('ai_chat_rooms', array(
            'where' => array('is_private' => 0),
            'orderby' => 'updated_at',
            'order' => 'DESC'
        ));
    }

    /**
     * Ambil daftar user dalam chat room.
     *
     * @param int $room_id Room ID.
     * @return array|WP_Error List of users or error.
     */
    public function get_room_users($room_id) {
        // Check if room exists
        $room = $this->get_room($room_id);
        if (is_wp_error($room)) {
            return $room;
        }
        
        $room_users = $this->database->get_data('ai_chat_room_users', array(
            'where' => array('room_id' => $room_id)
        ));
        
        // Add user data
        foreach ($room_users as &$room_user) {
            $user = get_userdata($room_user['user_id']);
            $room_user['user_name'] = $user ? $user->display_name : __('Unknown User', 'ai-chat-assistant');
            $room_user['user_email'] = $user ? $user->user_email : '';
            $room_user['user_avatar'] = get_avatar_url($room_user['user_id']);
            $room_user['is_creator'] = ($room['created_by'] == $room_user['user_id']);
        }
        
        return $room_users;
    }

    /**
     * Cek apakah user adalah anggota chat room.
     *
     * @param int $room_id Room ID.
     * @param int $user_id User ID.
     * @return bool|WP_Error True if member, false if not, or error.
     */
    public function is_user_in_room($room_id, $user_id) {
        // Check if room exists
        $room = $this->get_room($room_id);
        if (is_wp_error($room)) {
            return $room;
        }
        
        $room_user = $this->database->get_data('ai_chat_room_users', array(
            'where' => array(
                'room_id' => $room_id,
                'user_id' => $user_id
            )
        ), true);
        
        return !empty($room_user);
    }

    /**
     * Cek apakah user adalah moderator chat room.
     *
     * @param int $room_id Room ID.
     * @param int $user_id User ID.
     * @return bool|WP_Error True if moderator, false if not, or error.
     */
    public function is_user_moderator($room_id, $user_id) {
        // Check if room exists
        $room = $this->get_room($room_id);
        if (is_wp_error($room)) {
            return $room;
        }
        
        // Creator is always a moderator
        if ($room['created_by'] == $user_id) {
            return true;
        }
        
        $room_user = $this->database->get_data('ai_chat_room_users', array(
            'where' => array(
                'room_id' => $room_id,
                'user_id' => $user_id
            )
        ), true);
        
        if (empty($room_user)) {
            return false;
        }
        
        return (bool) $room_user['is_moderator'];
    }

    /**
     * Kirim undangan email ke user yang belum bergabung.
     *
     * @param int $room_id Room ID.
     * @param string $email Email address.
     * @param int $inviter_id User ID of inviter.
     * @return bool|WP_Error True on success or error.
     */
    public function send_invitation($room_id, $email, $inviter_id) {
        // Check if room exists
        $room = $this->get_room($room_id);
        if (is_wp_error($room)) {
            return $room;
        }
        
        // Check if inviter is in room
        $is_member = $this->is_user_in_room($room_id, $inviter_id);
        if (is_wp_error($is_member)) {
            return $is_member;
        }
        
        if (!$is_member) {
            return new WP_Error('not_member', __('Inviter is not a member of this chat room', 'ai-chat-assistant'));
        }
        
        // Check if email is valid
        if (!is_email($email)) {
            return new WP_Error('invalid_email', __('Invalid email address', 'ai-chat-assistant'));
        }
        
        // Check if user with this email exists
        $user = get_user_by('email', $email);
        if ($user) {
            // If user exists, check if already in room
            $is_member = $this->is_user_in_room($room_id, $user->ID);
            if (is_wp_error($is_member)) {
                return $is_member;
            }
            
            if ($is_member) {
                return new WP_Error('already_member', __('User is already a member of this chat room', 'ai-chat-assistant'));
            }
            
            // Add user to room
            $result = $this->add_user_to_room($room_id, $user->ID);
            if (is_wp_error($result)) {
                return $result;
            }
        }
        
        // Send invitation email
        $inviter = get_userdata($inviter_id);
        $inviter_name = $inviter ? $inviter->display_name : __('A user', 'ai-chat-assistant');
        
        $subject = sprintf(__('[%s] Invitation to join chat room: %s', 'ai-chat-assistant'), get_bloginfo('name'), $room['name']);
        
        $message = sprintf(
            __('%s has invited you to join the chat room "%s" on %s.', 'ai-chat-assistant'),
            $inviter_name,
            $room['name'],
            get_bloginfo('name')
        );
        
        $message .= "\n\n";
        
        if ($room['description']) {
            $message .= sprintf(__("Description: %s\n\n", 'ai-chat-assistant'), $room['description']);
        }
        
        $join_url = add_query_arg(array(
            'action' => 'join_chat_room',
            'room_id' => $room_id,
            'email' => urlencode($email),
            'token' => wp_create_nonce('join_chat_room_' . $room_id . '_' . $email)
        ), site_url());
        
        $message .= sprintf(__("To join this chat room, click the link below:\n%s", 'ai-chat-assistant'), $join_url);
        
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        
        $result = wp_mail($email, $subject, $message, $headers);
        
        if (!$result) {
            return new WP_Error('email_failed', __('Failed to send invitation email', 'ai-chat-assistant'));
        }
        
        return true;
    }
}