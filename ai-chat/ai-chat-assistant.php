<?php
/**
 * Plugin Name: AI Chat Assistant
 * Plugin URI: https://yourwebsite.com/ai-chat-assistant
 * Description: Plugin WordPress yang menyediakan fitur chat dengan AI, chat room forum, dan chat antar pengguna.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * Text Domain: ai-chat-assistant
 * Domain Path: /languages
 */

// Jika file ini dipanggil langsung, abort.
if (!defined('WPINC')) {
    die;
}

// Definisikan konstanta plugin
define('AI_CHAT_VERSION', '1.0.0');
define('AI_CHAT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AI_CHAT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AI_CHAT_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Kode yang dijalankan saat plugin diaktifkan.
 */
function activate_ai_chat_assistant() {
    require_once AI_CHAT_PLUGIN_DIR . 'includes/class-database.php';
    $database = new AI_Chat_Database();
    $database->create_tables();
    
    // Jalankan pembaruan database jika ada
    require_once AI_CHAT_PLUGIN_DIR . 'includes/class-db-updater.php';
    $updater = new AI_Chat_DB_Updater($database);
    $updater->run();
    
    // Flush rewrite rules after custom post types registration
    flush_rewrite_rules();
}

/**
 * Kode yang dijalankan saat plugin dinonaktifkan.
 */
function deactivate_ai_chat_assistant() {
    // Flush rewrite rules after deactivation
    flush_rewrite_rules();
}

register_activation_hook(__FILE__, 'activate_ai_chat_assistant');
register_deactivation_hook(__FILE__, 'deactivate_ai_chat_assistant');

/**
 * Load compatibility file untuk PHP 8.2.
 */
if (file_exists(AI_CHAT_PLUGIN_DIR . 'includes/ai-chat-compatibility.php')) {
    require_once AI_CHAT_PLUGIN_DIR . 'includes/ai-chat-compatibility.php';
}

/**
 * Load plugin textdomain.
 */
function ai_chat_assistant_load_textdomain() {
    load_plugin_textdomain('ai-chat-assistant', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}
add_action('plugins_loaded', 'ai_chat_assistant_load_textdomain');

/**
 * Include the core plugin class.
 */
require_once AI_CHAT_PLUGIN_DIR . 'includes/class-ai-chat-assistant.php';

/**
 * Begin execution of the plugin.
 */
function run_ai_chat_assistant() {
    $plugin = new AI_Chat_Assistant();
    $plugin->run();
    
    // Registrasi handler fungsi yang belum diimplementasikan
    if (class_exists('AI_Chat_Public_Extension')) {
        // Daftarkan semua handler tanpa mencoba mengakses property yang tidak ada
        add_action('init', 'register_missing_ajax_handlers');
    }
}

/**
 * Register semua handler ajax yang belum diimplementasikan
 */
function register_missing_ajax_handlers() {
    // User chat handlers
    add_action('wp_ajax_user_chat_mark_read', array('AI_Chat_Public_Extension', 'handle_user_chat_mark_read'));
    add_action('wp_ajax_user_chat_get_messages', array('AI_Chat_Public_Extension', 'handle_user_chat_get_messages'));
    add_action('wp_ajax_user_chat_get_new_messages', array('AI_Chat_Public_Extension', 'handle_user_chat_get_new_messages'));
    add_action('wp_ajax_user_chat_get_users', array('AI_Chat_Public_Extension', 'handle_user_chat_get_users'));
    add_action('wp_ajax_user_chat_get_user_info', array('AI_Chat_Public_Extension', 'handle_user_chat_get_user_info'));
    
    // Group chat handlers
    add_action('wp_ajax_group_chat_get_messages', array('AI_Chat_Public_Extension', 'handle_group_chat_get_messages'));
    add_action('wp_ajax_group_chat_get_new_messages', array('AI_Chat_Public_Extension', 'handle_group_chat_get_new_messages'));
    add_action('wp_ajax_group_chat_create_room', array('AI_Chat_Public_Extension', 'handle_group_chat_create_room'));
}

run_ai_chat_assistant();