<?php
/**
 * Class untuk mengelola database plugin.
 *
 * @package AI_Chat_Assistant
 * @subpackage AI_Chat_Assistant/includes
 */
class AI_Chat_Database {

    /**
     * Inisialisasi class.
     */
    public function __construct() {
        
    }

    /**
     * Membuat tabel-tabel database yang diperlukan.
     */
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Tabel untuk menyimpan percakapan AI
        $table_ai_conversations = $wpdb->prefix . 'ai_chat_conversations';
        $table_ai_messages = $wpdb->prefix . 'ai_chat_messages';
        
        // Tabel untuk chat room
        $table_chat_rooms = $wpdb->prefix . 'ai_chat_rooms';
        $table_chat_room_users = $wpdb->prefix . 'ai_chat_room_users';
        $table_chat_room_messages = $wpdb->prefix . 'ai_chat_room_messages';
        
        // Tabel untuk chat antar user
        $table_user_chats = $wpdb->prefix . 'ai_chat_user_conversations';
        $table_user_chat_messages = $wpdb->prefix . 'ai_chat_user_messages';
        
        // SQL untuk membuat tabel percakapan AI
        $sql_ai_conversations = "CREATE TABLE IF NOT EXISTS $table_ai_conversations (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            topic_id bigint(20) NOT NULL,
            title varchar(255) NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY topic_id (topic_id)
        ) $charset_collate;";
        
        $sql_ai_messages = "CREATE TABLE IF NOT EXISTS $table_ai_messages (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            conversation_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            message longtext NOT NULL,
            is_ai tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY conversation_id (conversation_id)
        ) $charset_collate;";
        
        // SQL untuk membuat tabel chat room
        $sql_chat_rooms = "CREATE TABLE IF NOT EXISTS $table_chat_rooms (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            created_by bigint(20) NOT NULL,
            is_private tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY created_by (created_by)
        ) $charset_collate;";
        
        $sql_chat_room_users = "CREATE TABLE IF NOT EXISTS $table_chat_room_users (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            room_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            is_moderator tinyint(1) NOT NULL DEFAULT 0,
            joined_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY room_user (room_id, user_id),
            KEY room_id (room_id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        $sql_chat_room_messages = "CREATE TABLE IF NOT EXISTS $table_chat_room_messages (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            room_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            message longtext NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY room_id (room_id)
        ) $charset_collate;";
        
        // SQL untuk membuat tabel chat antar user
        $sql_user_chats = "CREATE TABLE IF NOT EXISTS $table_user_chats (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_one bigint(20) NOT NULL,
            user_two bigint(20) NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY user_pair (user_one, user_two),
            KEY user_one (user_one),
            KEY user_two (user_two)
        ) $charset_collate;";
        
        $sql_user_chat_messages = "CREATE TABLE IF NOT EXISTS $table_user_chat_messages (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            conversation_id bigint(20) NOT NULL,
            sender_id bigint(20) NOT NULL,
            message longtext NOT NULL,
            is_read tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY conversation_id (conversation_id),
            KEY sender_id (sender_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Eksekusi query pembuatan tabel
        dbDelta($sql_ai_conversations);
        dbDelta($sql_ai_messages);
        dbDelta($sql_chat_rooms);
        dbDelta($sql_chat_room_users);
        dbDelta($sql_chat_room_messages);
        dbDelta($sql_user_chats);
        dbDelta($sql_user_chat_messages);
    }
    
    /**
     * Ambil data dari tabel dengan berbagai filter.
     */
    public function get_data($table, $args = array(), $single = false) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . $table;
        $where = '';
        $order_by = '';
        $limit = '';
        
        // Build WHERE clause
        if (!empty($args['where'])) {
            $where = 'WHERE ';
            $conditions = array();
            
            foreach ($args['where'] as $field => $value) {
                if (is_numeric($value)) {
                    $conditions[] = "$field = $value";
                } else {
                    $conditions[] = "$field = '$value'";
                }
            }
            
            $where .= implode(' AND ', $conditions);
        }
        
        // Build ORDER BY clause
        if (!empty($args['orderby'])) {
            $order = !empty($args['order']) ? $args['order'] : 'ASC';
            $order_by = "ORDER BY {$args['orderby']} $order";
        }
        
        // Build LIMIT clause
        if (!empty($args['limit'])) {
            $offset = !empty($args['offset']) ? $args['offset'] : 0;
            $limit = "LIMIT $offset, {$args['limit']}";
        }
        
        // Combine query
        $query = "SELECT * FROM $table_name $where $order_by $limit";
        
        // Return result
        if ($single) {
            return $wpdb->get_row($query, ARRAY_A);
        } else {
            return $wpdb->get_results($query, ARRAY_A);
        }
    }
    
    /**
     * Masukkan data ke tabel.
     */
    public function insert_data($table, $data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . $table;
        
        $wpdb->insert($table_name, $data);
        
        return $wpdb->insert_id;
    }
    
    /**
     * Update data di tabel.
     */
    public function update_data($table, $data, $where) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . $table;
        
        return $wpdb->update($table_name, $data, $where);
    }
    
    /**
     * Hapus data dari tabel.
     */
    public function delete_data($table, $where) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . $table;
        
        return $wpdb->delete($table_name, $where);
    }
}