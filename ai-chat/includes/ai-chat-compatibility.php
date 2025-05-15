<?php
/**
 * Peningkatan kompatibilitas dan keamanan untuk plugin.
 * 
 * Plugin berikut ini akan mengimplementasikan beberapa peningkatan:
 * 1. Menambahkan handler untuk fungsi yang tidak ada di class-public.php
 * 2. Memperbaiki kompatibilitas dengan PHP 8.2 (deprecated dynamic properties)
 * 
 * @package AI_Chat_Assistant
 * @subpackage AI_Chat_Assistant/includes
 */

class AI_Chat_Public_Extension {
    
    /**
     * Handle user chat mark read AJAX request.
     */
    public static function handle_user_chat_mark_read() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ai_chat_public_nonce')) {
            wp_send_json_error(__('Security check failed.', 'ai-chat-assistant'));
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to use this feature.', 'ai-chat-assistant'));
        }
        
        $conversation_id = isset($_POST['conversation_id']) ? intval($_POST['conversation_id']) : 0;
        
        if ($conversation_id <= 0) {
            wp_send_json_error(__('Invalid conversation ID.', 'ai-chat-assistant'));
        }
        
        $user_chat = self::get_user_chat_instance();
        
        // Mark messages as read
        $result = $user_chat->mark_messages_as_read($conversation_id, get_current_user_id());
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        wp_send_json_success(true);
    }
    
    /**
     * Handle get user chat messages AJAX request.
     */
    public static function handle_user_chat_get_messages() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ai_chat_public_nonce')) {
            wp_send_json_error(__('Security check failed.', 'ai-chat-assistant'));
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to use this feature.', 'ai-chat-assistant'));
        }
        
        $conversation_id = isset($_POST['conversation_id']) ? intval($_POST['conversation_id']) : 0;
        
        if ($conversation_id <= 0) {
            wp_send_json_error(__('Invalid conversation ID.', 'ai-chat-assistant'));
        }
        
        $user_chat = self::get_user_chat_instance();
        
        // Get conversation
        $conversation = $user_chat->get_conversation($conversation_id);
        
        if (is_wp_error($conversation)) {
            wp_send_json_error($conversation->get_error_message());
        }
        
        // Check if user is part of the conversation
        if ($conversation['user_one'] != get_current_user_id() && $conversation['user_two'] != get_current_user_id()) {
            wp_send_json_error(__('You do not have permission to access this conversation.', 'ai-chat-assistant'));
        }
        
        // Get messages
        $messages = $user_chat->get_messages($conversation_id);
        
        // Mark messages as read
        $user_chat->mark_messages_as_read($conversation_id, get_current_user_id());
        
        wp_send_json_success($messages);
    }
    
    /**
     * Handle get new user chat messages AJAX request.
     */
    public static function handle_user_chat_get_new_messages() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ai_chat_public_nonce')) {
            wp_send_json_error(__('Security check failed.', 'ai-chat-assistant'));
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to use this feature.', 'ai-chat-assistant'));
        }
        
        $conversation_id = isset($_POST['conversation_id']) ? intval($_POST['conversation_id']) : 0;
        $last_time = isset($_POST['last_time']) ? sanitize_text_field($_POST['last_time']) : '';
        
        if ($conversation_id <= 0) {
            wp_send_json_error(__('Invalid conversation ID.', 'ai-chat-assistant'));
        }
        
        if (empty($last_time)) {
            wp_send_json_error(__('Last message time is required.', 'ai-chat-assistant'));
        }
        
        $user_chat = self::get_user_chat_instance();
        
        // Get conversation
        $conversation = $user_chat->get_conversation($conversation_id);
        
        if (is_wp_error($conversation)) {
            wp_send_json_error($conversation->get_error_message());
        }
        
        // Check if user is part of the conversation
        if ($conversation['user_one'] != get_current_user_id() && $conversation['user_two'] != get_current_user_id()) {
            wp_send_json_error(__('You do not have permission to access this conversation.', 'ai-chat-assistant'));
        }
        
        // Get new messages
        global $wpdb;
        $messages_table = $wpdb->prefix . 'ai_chat_user_messages';
        
        $messages = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $messages_table WHERE conversation_id = %d AND created_at > %s ORDER BY created_at ASC",
            $conversation_id,
            $last_time
        ), ARRAY_A);
        
        // Add sender info
        foreach ($messages as &$message) {
            $user = get_userdata($message['sender_id']);
            $message['sender_name'] = $user ? $user->display_name : __('Unknown User', 'ai-chat-assistant');
            $message['sender_avatar'] = get_avatar_url($message['sender_id']);
        }
        
        // Mark messages as read
        $user_chat->mark_messages_as_read($conversation_id, get_current_user_id());
        
        wp_send_json_success($messages);
    }
    
    /**
     * Handle get users AJAX request.
     */
    public static function handle_user_chat_get_users() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ai_chat_public_nonce')) {
            wp_send_json_error(__('Security check failed.', 'ai-chat-assistant'));
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to use this feature.', 'ai-chat-assistant'));
        }
        
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        
        $user_chat = self::get_user_chat_instance();
        
        // Get users
        $users = $user_chat->get_contactable_users(get_current_user_id(), $search);
        
        wp_send_json_success($users);
    }
    
    /**
     * Handle get user info AJAX request.
     */
    public static function handle_user_chat_get_user_info() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ai_chat_public_nonce')) {
            wp_send_json_error(__('Security check failed.', 'ai-chat-assistant'));
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to use this feature.', 'ai-chat-assistant'));
        }
        
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        
        if ($user_id <= 0) {
            wp_send_json_error(__('Invalid user ID.', 'ai-chat-assistant'));
        }
        
        $user = get_userdata($user_id);
        
        if (!$user) {
            wp_send_json_error(__('User not found.', 'ai-chat-assistant'));
        }
        
        $user_chat = self::get_user_chat_instance();
        
        // Get user info
        $user_info = array(
            'id' => $user->ID,
            'name' => $user->display_name,
            'email' => $user->user_email,
            'avatar' => get_avatar_url($user->ID),
            'is_online' => $user_chat->is_user_online($user->ID)
        );
        
        wp_send_json_success($user_info);
    }
    
    /**
     * Handle get group chat messages AJAX request.
     */
    public static function handle_group_chat_get_messages() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ai_chat_public_nonce')) {
            wp_send_json_error(__('Security check failed.', 'ai-chat-assistant'));
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to use this feature.', 'ai-chat-assistant'));
        }
        
        $room_id = isset($_POST['room_id']) ? intval($_POST['room_id']) : 0;
        
        if ($room_id <= 0) {
            wp_send_json_error(__('Invalid room ID.', 'ai-chat-assistant'));
        }
        
        $chat_room = self::get_chat_room_instance();
        
        // Check if user is in room
        $is_member = $chat_room->is_user_in_room($room_id, get_current_user_id());
        
        if (is_wp_error($is_member)) {
            wp_send_json_error($is_member->get_error_message());
        }
        
        if (!$is_member) {
            // Check if it's a public room
            $room = $chat_room->get_room($room_id);
            
            if (is_wp_error($room)) {
                wp_send_json_error($room->get_error_message());
            }
            
            if ($room['is_private']) {
                wp_send_json_error(__('You are not a member of this private chat room.', 'ai-chat-assistant'));
            } else {
                // Auto-join public room
                $chat_room->add_user_to_room($room_id, get_current_user_id());
            }
        }
        
        // Get messages
        $messages = $chat_room->get_messages($room_id);
        
        wp_send_json_success($messages);
    }
    
    /**
     * Handle get new group chat messages AJAX request.
     */
    public static function handle_group_chat_get_new_messages() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ai_chat_public_nonce')) {
            wp_send_json_error(__('Security check failed.', 'ai-chat-assistant'));
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to use this feature.', 'ai-chat-assistant'));
        }
        
        $room_id = isset($_POST['room_id']) ? intval($_POST['room_id']) : 0;
        $last_time = isset($_POST['last_time']) ? sanitize_text_field($_POST['last_time']) : '';
        
        if ($room_id <= 0) {
            wp_send_json_error(__('Invalid room ID.', 'ai-chat-assistant'));
        }
        
        if (empty($last_time)) {
            wp_send_json_error(__('Last message time is required.', 'ai-chat-assistant'));
        }
        
        $chat_room = self::get_chat_room_instance();
        
        // Check if user is in room
        $is_member = $chat_room->is_user_in_room($room_id, get_current_user_id());
        
        if (is_wp_error($is_member)) {
            wp_send_json_error($is_member->get_error_message());
        }
        
        if (!$is_member) {
            wp_send_json_error(__('You are not a member of this chat room.', 'ai-chat-assistant'));
        }
        
        // Get new messages
        global $wpdb;
        $messages_table = $wpdb->prefix . 'ai_chat_room_messages';
        
        $messages = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $messages_table WHERE room_id = %d AND created_at > %s ORDER BY created_at ASC",
            $room_id,
            $last_time
        ), ARRAY_A);
        
        // Add user data to messages
        foreach ($messages as &$message) {
            $user = get_userdata($message['user_id']);
            $message['user_name'] = $user ? $user->display_name : __('Unknown User', 'ai-chat-assistant');
            $message['user_avatar'] = get_avatar_url($message['user_id']);
        }
        
        wp_send_json_success($messages);
    }
    
    /**
     * Handle create group chat room AJAX request.
     */
    public static function handle_group_chat_create_room() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ai_chat_public_nonce')) {
            wp_send_json_error(__('Security check failed.', 'ai-chat-assistant'));
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to use this feature.', 'ai-chat-assistant'));
        }
        
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
        $is_private = isset($_POST['is_private']) ? (bool) intval($_POST['is_private']) : false;
        
        if (empty($name)) {
            wp_send_json_error(__('Room name is required.', 'ai-chat-assistant'));
        }
        
        $chat_room = self::get_chat_room_instance();
        
        // Create room
        $room_id = $chat_room->create_room($name, $description, get_current_user_id(), $is_private);
        
        if (is_wp_error($room_id)) {
            wp_send_json_error($room_id->get_error_message());
        }
        
        wp_send_json_success(array(
            'room_id' => $room_id,
            'name' => $name,
            'description' => $description,
            'is_private' => $is_private
        ));
    }
    
    /**
     * Get user chat instance.
     */
    private static function get_user_chat_instance() {
        // Get database instance
        $database = new AI_Chat_Database();
        
        // Get notification instance
        $notification = new AI_Chat_Notification($database);
        
        // Create user chat instance
        return new AI_Chat_User($database, $notification);
    }
    
    /**
     * Get chat room instance.
     */
    private static function get_chat_room_instance() {
        // Get database instance
        $database = new AI_Chat_Database();
        
        // Get notification instance
        $notification = new AI_Chat_Notification($database);
        
        // Create chat room instance
        return new AI_Chat_Room($database, $notification);
    }
}

// Tambahkan hook untuk meningkatkan kompatibilitas
add_action('plugins_loaded', function() {
    // Menangani properti dinamis di ACF class (menyelesaikan warning)
    if (class_exists('ACF')) {
        $acf = acf();
        
        if (!property_exists($acf, 'fields')) {
            $acf->fields = null;
        }
        
        if (!property_exists($acf, 'loop')) {
            $acf->loop = null;
        }
        
        if (!property_exists($acf, 'revisions')) {
            $acf->revisions = null;
        }
        
        if (!property_exists($acf, 'validation')) {
            $acf->validation = null;
        }
        
        if (!property_exists($acf, 'form_front')) {
            $acf->form_front = null;
        }
        
        if (!property_exists($acf, 'admin_tools')) {
            $acf->admin_tools = null;
        }
    }
}, 5); // Prioritas rendah untuk memastikan ACF sudah diload