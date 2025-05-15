<?php
/**
 * Class untuk mengurus bagian public plugin.
 *
 * @package AI_Chat_Assistant
 * @subpackage AI_Chat_Assistant/public
 */
class AI_Chat_Public {

    /**
     * Plugin name.
     *
     * @var string
     */
    private $plugin_name;

    /**
     * Plugin version.
     *
     * @var string
     */
    private $version;

    /**
     * Database instance.
     *
     * @var AI_Chat_Database
     */
    private $database;

    /**
     * AI Integration instance.
     *
     * @var AI_Chat_Integration
     */
    private $ai_integration;

    /**
     * Chat Room instance.
     *
     * @var AI_Chat_Room
     */
    private $chat_room;

    /**
     * User Chat instance.
     *
     * @var AI_Chat_User
     */
    private $user_chat;

    /**
     * Notification instance.
     *
     * @var AI_Chat_Notification
     */
    private $notification;

    /**
     * Initialize class.
     *
     * @param string $plugin_name The name of this plugin.
     * @param string $version The version of this plugin.
     * @param AI_Chat_Database $database Database instance.
     */
    public function __construct($plugin_name, $version, $database) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->database = $database;
        
        // Initialize other instances
        // All requires have been moved to main class
        $this->notification = new AI_Chat_Notification($database);
        $this->ai_integration = new AI_Chat_Integration($database);
        $this->chat_room = new AI_Chat_Room($database, $this->notification);
        $this->user_chat = new AI_Chat_User($database, $this->notification);
    }

    /**
     * Register stylesheets untuk public area.
     */
    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/public.css', array(), $this->version, 'all');
    }

    /**
     * Register scripts untuk public area.
     */
    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/public.js', array('jquery'), $this->version, false);
        
        // AI Chat scripts
        wp_register_script($this->plugin_name . '-ai-chat', plugin_dir_url(__FILE__) . 'js/ai-chat.js', array('jquery'), $this->version, false);
        
        // Group Chat scripts
        wp_register_script($this->plugin_name . '-group-chat', plugin_dir_url(__FILE__) . 'js/group-chat.js', array('jquery'), $this->version, false);
        
        // User Chat scripts
        wp_register_script($this->plugin_name . '-user-chat', plugin_dir_url(__FILE__) . 'js/user-chat.js', array('jquery'), $this->version, false);
        
        // Localize scripts
        wp_localize_script($this->plugin_name, 'ai_chat_public', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_chat_public_nonce'),
            'current_user_id' => get_current_user_id(),
            'strings' => array(
                'send' => __('Send', 'ai-chat-assistant'),
                'typing' => __('Typing...', 'ai-chat-assistant'),
                'send_failed' => __('Failed to send message. Please try again.', 'ai-chat-assistant'),
                'login_required' => __('Please log in to use the chat features.', 'ai-chat-assistant'),
                'load_more' => __('Load older messages', 'ai-chat-assistant'),
                'no_more_messages' => __('No more messages to load.', 'ai-chat-assistant'),
                'online' => __('Online', 'ai-chat-assistant'),
                'offline' => __('Offline', 'ai-chat-assistant'),
                'start_new_chat' => __('Start a new chat', 'ai-chat-assistant'),
                'confirm_delete' => __('Are you sure you want to delete this conversation? This action cannot be undone.', 'ai-chat-assistant')
            )
        ));
    }

    /**
     * Register shortcodes.
     */
    public function register_shortcodes() {
        // AI Chat shortcode
        add_shortcode('ai_chat', array($this, 'render_ai_chat'));
        
        // Group Chat shortcode
        add_shortcode('group_chat', array($this, 'render_group_chat'));
        
        // User Chat shortcode
        add_shortcode('user_chat', array($this, 'render_user_chat'));
    }

    /**
     * Render AI Chat shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string Rendered output.
     */
    public function render_ai_chat($atts) {
        // Check if AI chat is enabled
        if (get_option('ai_chat_enable_ai', 'yes') !== 'yes') {
            return '<div class="ai-chat-disabled">' . __('AI chat is currently disabled by the administrator.', 'ai-chat-assistant') . '</div>';
        }
        
        // Parse attributes
        $atts = shortcode_atts(array(
            'topic_id' => 0,
        ), $atts, 'ai_chat');
        
        $topic_id = intval($atts['topic_id']);
        
        // Enqueue scripts
        wp_enqueue_script($this->plugin_name . '-ai-chat');
        
        // Start output buffer
        ob_start();
        
        // Include template
        include plugin_dir_path(__FILE__) . '../templates/ai-chat-template.php';
        
        // Return output
        return ob_get_clean();
    }

    /**
     * Render Group Chat shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string Rendered output.
     */
    public function render_group_chat($atts) {
        // Check if group chat is enabled
        if (get_option('ai_chat_enable_group', 'yes') !== 'yes') {
            return '<div class="ai-chat-disabled">' . __('Group chat is currently disabled by the administrator.', 'ai-chat-assistant') . '</div>';
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return '<div class="ai-chat-login-required">' . __('Please log in to use the group chat feature.', 'ai-chat-assistant') . '</div>';
        }
        
        // Parse attributes
        $atts = shortcode_atts(array(
            'room_id' => 0,
        ), $atts, 'group_chat');
        
        $room_id = intval($atts['room_id']);
        
        // If room_id is provided, verify user has access to the room
        if ($room_id > 0) {
            $is_member = $this->chat_room->is_user_in_room($room_id, get_current_user_id());
            
            if (is_wp_error($is_member) || !$is_member) {
                $room = $this->chat_room->get_room($room_id);
                
                if (is_wp_error($room)) {
                    return '<div class="ai-chat-error">' . __('The specified chat room does not exist.', 'ai-chat-assistant') . '</div>';
                }
                
                if ($room['is_private']) {
                    return '<div class="ai-chat-error">' . __('You do not have access to this private chat room.', 'ai-chat-assistant') . '</div>';
                } else {
                    // Auto-join public room
                    $this->chat_room->add_user_to_room($room_id, get_current_user_id());
                }
            }
        }
        
        // Enqueue scripts
        wp_enqueue_script($this->plugin_name . '-group-chat');
        
        // Start output buffer
        ob_start();
        
        // Include template
        include plugin_dir_path(__FILE__) . '../templates/group-chat-template.php';
        
        // Return output
        return ob_get_clean();
    }

    /**
     * Render User Chat shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string Rendered output.
     */
    public function render_user_chat($atts) {
        // Check if user chat is enabled
        if (get_option('ai_chat_enable_user', 'yes') !== 'yes') {
            return '<div class="ai-chat-disabled">' . __('User-to-user chat is currently disabled by the administrator.', 'ai-chat-assistant') . '</div>';
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            return '<div class="ai-chat-login-required">' . __('Please log in to use the user chat feature.', 'ai-chat-assistant') . '</div>';
        }
        
        // Parse attributes
        $atts = shortcode_atts(array(
            'user_id' => 0,
        ), $atts, 'user_chat');
        
        $user_id = intval($atts['user_id']);
        
        // Validate user if specified
        if ($user_id > 0) {
            $user = get_userdata($user_id);
            
            if (!$user) {
                return '<div class="ai-chat-error">' . __('The specified user does not exist.', 'ai-chat-assistant') . '</div>';
            }
            
            if ($user_id === get_current_user_id()) {
                return '<div class="ai-chat-error">' . __('You cannot chat with yourself.', 'ai-chat-assistant') . '</div>';
            }
        }
        
        // Enqueue scripts
        wp_enqueue_script($this->plugin_name . '-user-chat');
        
        // Start output buffer
        ob_start();
        
        // Include template
        include plugin_dir_path(__FILE__) . '../templates/user-chat-template.php';
        
        // Return output
        return ob_get_clean();
    }

    /**
     * Handle AI chat message AJAX request.
     */
    public function handle_ai_chat_message() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ai_chat_public_nonce')) {
            wp_send_json_error(__('Security check failed.', 'ai-chat-assistant'));
        }
        
        // Get parameters
        $message = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';
        $conversation_id = isset($_POST['conversation_id']) ? intval($_POST['conversation_id']) : 0;
        $topic_id = isset($_POST['topic_id']) ? intval($_POST['topic_id']) : 0;
        
        if (empty($message)) {
            wp_send_json_error(__('Message cannot be empty.', 'ai-chat-assistant'));
        }
        
        $user_id = get_current_user_id();
        
        // Get conversation history or create new conversation
        if ($conversation_id > 0) {
            $conversation = $this->ai_integration->get_conversation($conversation_id);
            
            if (is_wp_error($conversation)) {
                wp_send_json_error($conversation->get_error_message());
            }
            
            if ($conversation['conversation']['user_id'] != $user_id && $user_id != 0) {
                wp_send_json_error(__('You do not have permission to access this conversation.', 'ai-chat-assistant'));
            }
            
            $messages = $conversation['messages'];
        } else {
            $messages = array();
        }
        
        // Add user message to history
        $messages[] = array(
            'message' => $message,
            'is_ai' => 0
        );
        
        // Get AI response
        $ai_response = $this->ai_integration->get_ai_response($messages, $topic_id);
        
        if (is_wp_error($ai_response)) {
            wp_send_json_error($ai_response->get_error_message());
        }
        
        // Add AI response to history
        $messages[] = array(
            'message' => $ai_response,
            'is_ai' => 1
        );
        
        // Save conversation
        if ($conversation_id > 0) {
            // Add new messages to existing conversation
            $last_messages = array_slice($messages, -2); // Just the last 2 messages (user and AI)
            
            foreach ($last_messages as $msg) {
                $this->database->insert_data('ai_chat_messages', array(
                    'conversation_id' => $conversation_id,
                    'user_id' => $user_id,
                    'message' => $msg['message'],
                    'is_ai' => $msg['is_ai']
                ));
            }
            
            // Update conversation timestamp
            $this->database->update_data('ai_chat_conversations', array(
                'updated_at' => current_time('mysql')
            ), array('id' => $conversation_id));
        } else {
            // Create new conversation
            $title = wp_trim_words($message, 5);
            $conversation_id = $this->ai_integration->save_conversation($user_id, $topic_id, $title, $messages);
            
            if (is_wp_error($conversation_id)) {
                wp_send_json_error($conversation_id->get_error_message());
            }
        }
        
        // Format response
        $response = array(
            'conversation_id' => $conversation_id,
            'user_message' => $message,
            'ai_response' => $ai_response,
            'timestamp' => current_time('mysql')
        );
        
        wp_send_json_success($response);
    }

    /**
     * Handle AI chat message for non-logged in users.
     */
    public function handle_ai_chat_message_nopriv() {
        // Check if guest AI chat is allowed
        $allow_guest = apply_filters('ai_chat_allow_guest', false);
        
        if (!$allow_guest) {
            wp_send_json_error(__('You must be logged in to use AI chat.', 'ai-chat-assistant'));
        }
        
        // Process normally
        $this->handle_ai_chat_message();
    }

    /**
     * Handle group chat message AJAX request.
     */
    public function handle_group_chat_message() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ai_chat_public_nonce')) {
            wp_send_json_error(__('Security check failed.', 'ai-chat-assistant'));
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to use group chat.', 'ai-chat-assistant'));
        }
        
        // Get parameters
        $message = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';
        $room_id = isset($_POST['room_id']) ? intval($_POST['room_id']) : 0;
        
        if (empty($message)) {
            wp_send_json_error(__('Message cannot be empty.', 'ai-chat-assistant'));
        }
        
        if ($room_id <= 0) {
            wp_send_json_error(__('Invalid room ID.', 'ai-chat-assistant'));
        }
        
        $user_id = get_current_user_id();
        
        // Send message
        $result = $this->chat_room->send_message($room_id, $user_id, $message);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        // Format response
        $user = wp_get_current_user();
        
        $response = array(
            'message_id' => $result,
            'user_id' => $user_id,
            'user_name' => $user->display_name,
            'user_avatar' => get_avatar_url($user_id),
            'message' => $message,
            'timestamp' => current_time('mysql')
        );
        
        wp_send_json_success($response);
    }

    /**
     * Handle user chat message AJAX request.
     */
    public function handle_user_chat_message() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ai_chat_public_nonce')) {
            wp_send_json_error(__('Security check failed.', 'ai-chat-assistant'));
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to use user chat.', 'ai-chat-assistant'));
        }
        
        // Get parameters
        $message = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';
        $recipient_id = isset($_POST['recipient_id']) ? intval($_POST['recipient_id']) : 0;
        $conversation_id = isset($_POST['conversation_id']) ? intval($_POST['conversation_id']) : 0;
        
        if (empty($message)) {
            wp_send_json_error(__('Message cannot be empty.', 'ai-chat-assistant'));
        }
        
        $sender_id = get_current_user_id();
        
        if ($conversation_id <= 0 && $recipient_id <= 0) {
            wp_send_json_error(__('Invalid recipient.', 'ai-chat-assistant'));
        }
        
        if ($recipient_id == $sender_id) {
            wp_send_json_error(__('You cannot send messages to yourself.', 'ai-chat-assistant'));
        }
        
        // Get or create conversation
        if ($conversation_id <= 0) {
            $conversation_id = $this->user_chat->get_or_create_conversation($sender_id, $recipient_id);
            
            if (is_wp_error($conversation_id)) {
                wp_send_json_error($conversation_id->get_error_message());
            }
        }
        
        // Send message
        $result = $this->user_chat->send_message($conversation_id, $sender_id, $message);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }
        
        // Update user activity
        $this->user_chat->update_user_activity($sender_id);
        
        // Format response
        $user = wp_get_current_user();
        
        $response = array(
            'message_id' => $result,
            'conversation_id' => $conversation_id,
            'sender_id' => $sender_id,
            'sender_name' => $user->display_name,
            'sender_avatar' => get_avatar_url($sender_id),
            'message' => $message,
            'timestamp' => current_time('mysql')
        );
        
        wp_send_json_success($response);
    }

    /**
     * Get user conversations AJAX request.
     */
    public function get_user_conversations() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ai_chat_public_nonce')) {
            wp_send_json_error(__('Security check failed.', 'ai-chat-assistant'));
        }
        
        // Check if user is logged in
        if (!is_user_logged_in()) {
            wp_send_json_error(__('You must be logged in to access conversations.', 'ai-chat-assistant'));
        }
        
        $user_id = get_current_user_id();
        
        // Update user activity
        $this->user_chat->update_user_activity($user_id);
        
        // Get conversations
        $conversations = $this->user_chat->get_user_conversations($user_id);
        
        wp_send_json_success($conversations);
    }
}