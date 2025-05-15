<?php
/**
 * Memperbarui struktur database untuk plugin.
 * 
 * File ini sebaiknya dijalankan saat plugin diaktifkan untuk memastikan
 * semua tabel database yang diperlukan sudah dibuat dengan benar.
 *
 * @package AI_Chat_Assistant
 * @subpackage AI_Chat_Assistant/admin
 */

// Pastikan file ini tidak diakses langsung
if (!defined('WPINC')) {
    die;
}

/**
 * Class untuk memperbarui database plugin.
 */
class AI_Chat_DB_Updater {
    
    /**
     * Database instance.
     *
     * @var AI_Chat_Database
     */
    private $database;
    
    /**
     * Initialize class.
     *
     * @param AI_Chat_Database $database Database instance.
     */
    public function __construct($database) {
        $this->database = $database;
    }
    
    /**
     * Jalankan pembaruan database.
     */
    public function run() {
        // Periksa versi database saat ini
        $current_version = get_option('ai_chat_db_version', '0');
        
        // Periksa versi plugin saat ini
        $plugin_version = AI_CHAT_VERSION;
        
        // Jika versi database tidak sama dengan versi plugin, perbarui
        if (version_compare($current_version, $plugin_version, '<')) {
            $this->update_database($current_version, $plugin_version);
        }
    }
    
    /**
     * Perbarui database ke versi terbaru.
     *
     * @param string $current_version Versi saat ini.
     * @param string $new_version Versi baru.
     */
    private function update_database($current_version, $new_version) {
        global $wpdb;
        
        // Pastikan bahwa semua tabel sudah dibuat dengan benar
        $this->database->create_tables();
        
        // Update versi database
        update_option('ai_chat_db_version', $new_version);
        
        // Periksa apakah tabel notifikasi ada
        $table_name = $wpdb->prefix . 'ai_chat_notifications';
        $table_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(1) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
            DB_NAME,
            $table_name
        ));
        
        // Jika tabel notifikasi belum ada, buat
        if (!$table_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                user_id bigint(20) NOT NULL,
                message text NOT NULL,
                type varchar(50) NOT NULL,
                data longtext,
                is_read tinyint(1) NOT NULL DEFAULT 0,
                created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY  (id),
                KEY user_id (user_id),
                KEY type (type),
                KEY is_read (is_read)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
        
        // Lakukan pembaruan lain jika diperlukan berdasarkan versi
        if (version_compare($current_version, '1.0.0', '<')) {
            // Pembaruan untuk versi 1.0.0
            $this->update_to_1_0_0();
        }
    }
    
    /**
     * Pembaruan khusus untuk versi 1.0.0.
     */
    private function update_to_1_0_0() {
        // Pastikan settings default sudah ada
        $api_key = get_option('ai_chat_api_key', '');
        if (empty($api_key)) {
            update_option('ai_chat_api_key', '');
        }
        
        $provider = get_option('ai_chat_provider', '');
        if (empty($provider)) {
            update_option('ai_chat_provider', 'openai');
        }
        
        $model = get_option('ai_chat_model', '');
        if (empty($model)) {
            update_option('ai_chat_model', 'gpt-3.5-turbo');
        }
        
        $enable_ai = get_option('ai_chat_enable_ai', '');
        if (empty($enable_ai)) {
            update_option('ai_chat_enable_ai', 'yes');
        }
        
        $enable_group = get_option('ai_chat_enable_group', '');
        if (empty($enable_group)) {
            update_option('ai_chat_enable_group', 'yes');
        }
        
        $enable_user = get_option('ai_chat_enable_user', '');
        if (empty($enable_user)) {
            update_option('ai_chat_enable_user', 'yes');
        }
    }
}