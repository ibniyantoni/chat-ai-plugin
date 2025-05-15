<?php
/**
 * Class utama Plugin.
 *
 * @package AI_Chat_Assistant
 * @subpackage AI_Chat_Assistant/includes
 */
class AI_Chat_Assistant {

    /**
     * Loader untuk mendaftarkan semua hooks plugin.
     *
     * @var AI_Chat_Loader
     */
    protected $loader;

    /**
     * Nama plugin.
     *
     * @var string
     */
    protected $plugin_name;

    /**
     * Versi plugin.
     *
     * @var string
     */
    protected $version;

    /**
     * Instance dari database class.
     *
     * @var AI_Chat_Database
     */
    protected $database;

    /**
     * Inisialisasi plugin dan load semua dependencies.
     */
    public function __construct() {
        $this->version = AI_CHAT_VERSION;
        $this->plugin_name = 'ai-chat-assistant';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        
        // Create custom post types
        $this->define_post_types();
    }

    /**
     * Load semua dependencies yang dibutuhkan plugin.
     */
    private function load_dependencies() {
        
        // Class untuk orchestrating the hooks
        require_once AI_CHAT_PLUGIN_DIR . 'includes/class-loader.php';
        
        // Class untuk localization
        require_once AI_CHAT_PLUGIN_DIR . 'includes/class-i18n.php';
        
        // Class untuk database operations
        require_once AI_CHAT_PLUGIN_DIR . 'includes/class-database.php';
        
        // Class untuk Custom Post Types
        require_once AI_CHAT_PLUGIN_DIR . 'includes/class-post-types.php';
        
        // Main feature classes - load ALL required classes here
        require_once AI_CHAT_PLUGIN_DIR . 'includes/class-notification.php';
        require_once AI_CHAT_PLUGIN_DIR . 'includes/class-ai-integration.php';
        require_once AI_CHAT_PLUGIN_DIR . 'includes/class-chat-room.php';
        require_once AI_CHAT_PLUGIN_DIR . 'includes/class-user-chat.php';
        
        // Admin class
        require_once AI_CHAT_PLUGIN_DIR . 'admin/class-admin.php';
        
        // Public class
        require_once AI_CHAT_PLUGIN_DIR . 'public/class-public.php';
        
        $this->loader = new AI_Chat_Loader();
        $this->database = new AI_Chat_Database();
    }

    /**
     * Set locale untuk internationalization.
     */
    private function set_locale() {
        $plugin_i18n = new AI_Chat_i18n();
        
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Register hooks untuk admin area.
     */
    private function define_admin_hooks() {
        $plugin_admin = new AI_Chat_Admin($this->get_plugin_name(), $this->get_version(), $this->database);
        
        // Enqueue admin styles dan scripts
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        
        // Admin menu items
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_admin_menu');
        
        // Admin ajax handlers
        $this->loader->add_action('wp_ajax_ai_chat_save_settings', $plugin_admin, 'save_settings');
    }

    /**
     * Register hooks untuk public area.
     */
    private function define_public_hooks() {
        $plugin_public = new AI_Chat_Public($this->get_plugin_name(), $this->get_version(), $this->database);
        
        // Enqueue public styles dan scripts
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        
        // Register shortcodes
        $this->loader->add_action('init', $plugin_public, 'register_shortcodes');
        
        // Public ajax handlers for AI chat
        $this->loader->add_action('wp_ajax_ai_chat_send_message', $plugin_public, 'handle_ai_chat_message');
        $this->loader->add_action('wp_ajax_nopriv_ai_chat_send_message', $plugin_public, 'handle_ai_chat_message_nopriv');
        
        // Public ajax handlers for Group chat
        $this->loader->add_action('wp_ajax_group_chat_send_message', $plugin_public, 'handle_group_chat_message');
        
        // Public ajax handlers for User chat
        $this->loader->add_action('wp_ajax_user_chat_send_message', $plugin_public, 'handle_user_chat_message');
        $this->loader->add_action('wp_ajax_user_chat_get_conversations', $plugin_public, 'get_user_conversations');
    }

    /**
     * Register custom post types.
     */
    private function define_post_types() {
        $post_types = new AI_Chat_Post_Types();
        
        $this->loader->add_action('init', $post_types, 'register_post_types');
        $this->loader->add_action('init', $post_types, 'register_taxonomies');
    }

    /**
     * Run the loader untuk eksekusi semua hooks.
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * Nama plugin yang digunakan untuk uniquely identify plugin.
     *
     * @return string The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * Reference ke class yang orchestrate the hooks plugin.
     *
     * @return AI_Chat_Loader Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @return string The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }

    /**
     * Retrieve the database instance.
     *
     * @return AI_Chat_Database Database instance.
     */
    public function get_database() {
        return $this->database;
    }
}