<?php
/**
 * Class untuk mengurus bagian admin plugin.
 *
 * @package AI_Chat_Assistant
 * @subpackage AI_Chat_Assistant/admin
 */
class AI_Chat_Admin {

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
    }

    /**
     * Register stylesheets untuk admin area.
     */
    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/admin.css', array(), $this->version, 'all');
    }

    /**
     * Register scripts untuk admin area.
     */
    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/admin.js', array('jquery'), $this->version, false);
        
        wp_localize_script($this->plugin_name, 'ai_chat_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_chat_admin_nonce'),
            'strings' => array(
                'save_success' => __('Settings saved successfully!', 'ai-chat-assistant'),
                'save_error' => __('Error saving settings.', 'ai-chat-assistant'),
                'confirm_delete' => __('Are you sure you want to delete this item? This action cannot be undone.', 'ai-chat-assistant'),
                'processing' => __('Processing...', 'ai-chat-assistant')
            )
        ));
    }

    /**
     * Tambahkan menu di admin.
     */
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            __('AI Chat Assistant', 'ai-chat-assistant'),
            __('AI Chat', 'ai-chat-assistant'),
            'manage_options',
            'ai-chat-assistant',
            array($this, 'display_settings_page'),
            'dashicons-format-chat',
            30
        );
        
        // Settings submenu
        add_submenu_page(
            'ai-chat-assistant',
            __('Settings', 'ai-chat-assistant'),
            __('Settings', 'ai-chat-assistant'),
            'manage_options',
            'ai-chat-assistant',
            array($this, 'display_settings_page')
        );
        
        // AI Topics submenu (handled by custom post type)
        add_submenu_page(
            'ai-chat-assistant',
            __('AI Topics', 'ai-chat-assistant'),
            __('AI Topics', 'ai-chat-assistant'),
            'manage_options',
            'edit.php?post_type=ai_chat_topic'
        );
        
        // Chat Rooms submenu
        add_submenu_page(
            'ai-chat-assistant',
            __('Chat Rooms', 'ai-chat-assistant'),
            __('Chat Rooms', 'ai-chat-assistant'),
            'manage_options',
            'ai-chat-rooms',
            array($this, 'display_chat_rooms_page')
        );
        
        // User Stats submenu
        add_submenu_page(
            'ai-chat-assistant',
            __('User Stats', 'ai-chat-assistant'),
            __('User Stats', 'ai-chat-assistant'),
            'manage_options',
            'ai-chat-stats',
            array($this, 'display_stats_page')
        );
    }

    /**
     * Display settings page.
     */
    public function display_settings_page() {
        // Get current settings
        $api_key = get_option('ai_chat_api_key', '');
        $provider = get_option('ai_chat_provider', 'openai');
        $model = get_option('ai_chat_model', 'gpt-3.5-turbo');
        $enable_ai_chat = get_option('ai_chat_enable_ai', 'yes');
        $enable_group_chat = get_option('ai_chat_enable_group', 'yes');
        $enable_user_chat = get_option('ai_chat_enable_user', 'yes');
        
        // Load view template
        include_once plugin_dir_path(__FILE__) . 'views/settings-page.php';
    }

    /**
     * Display chat rooms management page.
     */
    public function display_chat_rooms_page() {
        global $wpdb;
        
        // Get all chat rooms
        $rooms_table = $wpdb->prefix . 'ai_chat_rooms';
        $rooms = $wpdb->get_results("SELECT * FROM $rooms_table ORDER BY created_at DESC", ARRAY_A);
        
        // Add user info
        foreach ($rooms as &$room) {
            $creator = get_userdata($room['created_by']);
            $room['creator_name'] = $creator ? $creator->display_name : __('Unknown User', 'ai-chat-assistant');
            
            // Get member count
            $members_table = $wpdb->prefix . 'ai_chat_room_users';
            $room['member_count'] = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $members_table WHERE room_id = %d",
                $room['id']
            ));
            
            // Get message count
            $messages_table = $wpdb->prefix . 'ai_chat_room_messages';
            $room['message_count'] = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $messages_table WHERE room_id = %d",
                $room['id']
            ));
        }
        
        // Load view template
        include_once plugin_dir_path(__FILE__) . 'views/chat-rooms-page.php';
    }

    /**
     * Display user stats page.
     */
    public function display_stats_page() {
        global $wpdb;
        
        // AI Chat stats
        $ai_conversations_table = $wpdb->prefix . 'ai_chat_conversations';
        $ai_messages_table = $wpdb->prefix . 'ai_chat_messages';
        
        $ai_stats = array(
            'total_conversations' => $wpdb->get_var("SELECT COUNT(*) FROM $ai_conversations_table"),
            'total_messages' => $wpdb->get_var("SELECT COUNT(*) FROM $ai_messages_table"),
            'active_users' => $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM $ai_conversations_table"),
            'recent_conversations' => $wpdb->get_var("SELECT COUNT(*) FROM $ai_conversations_table WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")
        );
        
        // Group Chat stats
        $chat_rooms_table = $wpdb->prefix . 'ai_chat_rooms';
        $chat_room_users_table = $wpdb->prefix . 'ai_chat_room_users';
        $chat_room_messages_table = $wpdb->prefix . 'ai_chat_room_messages';
        
        $group_stats = array(
            'total_rooms' => $wpdb->get_var("SELECT COUNT(*) FROM $chat_rooms_table"),
            'total_messages' => $wpdb->get_var("SELECT COUNT(*) FROM $chat_room_messages_table"),
            'active_users' => $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM $chat_room_users_table"),
            'recent_messages' => $wpdb->get_var("SELECT COUNT(*) FROM $chat_room_messages_table WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")
        );
        
        // User Chat stats
        $user_chats_table = $wpdb->prefix . 'ai_chat_user_conversations';
        $user_chat_messages_table = $wpdb->prefix . 'ai_chat_user_messages';
        
        $user_stats = array(
            'total_conversations' => $wpdb->get_var("SELECT COUNT(*) FROM $user_chats_table"),
            'total_messages' => $wpdb->get_var("SELECT COUNT(*) FROM $user_chat_messages_table"),
            'active_users' => $wpdb->get_var("SELECT COUNT(DISTINCT sender_id) FROM $user_chat_messages_table WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"),
            'recent_messages' => $wpdb->get_var("SELECT COUNT(*) FROM $user_chat_messages_table WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")
        );
        
        // Get top users
        $top_ai_users = $wpdb->get_results(
            "SELECT user_id, COUNT(*) as message_count 
            FROM $ai_messages_table 
            WHERE is_ai = 0 
            GROUP BY user_id 
            ORDER BY message_count DESC 
            LIMIT 10",
            ARRAY_A
        );
        
        foreach ($top_ai_users as &$user) {
            $user_data = get_userdata($user['user_id']);
            $user['display_name'] = $user_data ? $user_data->display_name : __('Unknown User', 'ai-chat-assistant');
        }
        
        // Load view template
        include_once plugin_dir_path(__FILE__) . 'views/stats-page.php';
    }

    /**
     * Ajax handler untuk save settings.
     */
    public function save_settings() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ai_chat_admin_nonce')) {
            wp_send_json_error(__('Security check failed.', 'ai-chat-assistant'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have permission to perform this action.', 'ai-chat-assistant'));
        }
        
        // Sanitize and save each setting
        if (isset($_POST['api_key'])) {
            update_option('ai_chat_api_key', sanitize_text_field($_POST['api_key']));
        }
        
        if (isset($_POST['provider'])) {
            update_option('ai_chat_provider', sanitize_text_field($_POST['provider']));
        }
        
        if (isset($_POST['model'])) {
            update_option('ai_chat_model', sanitize_text_field($_POST['model']));
        }
        
        if (isset($_POST['enable_ai'])) {
            update_option('ai_chat_enable_ai', sanitize_text_field($_POST['enable_ai']));
        }
        
        if (isset($_POST['enable_group'])) {
            update_option('ai_chat_enable_group', sanitize_text_field($_POST['enable_group']));
        }
        
        if (isset($_POST['enable_user'])) {
            update_option('ai_chat_enable_user', sanitize_text_field($_POST['enable_user']));
        }
        
        wp_send_json_success(__('Settings saved successfully!', 'ai-chat-assistant'));
    }

    /**
     * Ajax handler untuk manage chat rooms.
     */
    public function manage_chat_room() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ai_chat_admin_nonce')) {
            wp_send_json_error(__('Security check failed.', 'ai-chat-assistant'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have permission to perform this action.', 'ai-chat-assistant'));
        }
        
        $action = isset($_POST['room_action']) ? sanitize_text_field($_POST['room_action']) : '';
        $room_id = isset($_POST['room_id']) ? intval($_POST['room_id']) : 0;
        
        // Load required classes
        require_once AI_CHAT_PLUGIN_DIR . 'includes/class-notification.php';
        $notification = new AI_Chat_Notification($this->database);
        
        require_once AI_CHAT_PLUGIN_DIR . 'includes/class-chat-room.php';
        $chat_room = new AI_Chat_Room($this->database, $notification);
        
        if ('delete' === $action) {
            // Delete room
            $result = $chat_room->delete_room($room_id);
            
            if (is_wp_error($result)) {
                wp_send_json_error($result->get_error_message());
            }
            
            wp_send_json_success(__('Chat room deleted successfully!', 'ai-chat-assistant'));
            
        } elseif ('view_members' === $action) {
            // Get room members
            $members = $chat_room->get_room_users($room_id);
            
            if (is_wp_error($members)) {
                wp_send_json_error($members->get_error_message());
            }
            
            wp_send_json_success(array(
                'members' => $members,
                'room' => $chat_room->get_room($room_id)
            ));
            
        } else {
            wp_send_json_error(__('Invalid action.', 'ai-chat-assistant'));
        }
    }
}