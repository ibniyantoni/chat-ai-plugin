<?php
/**
 * Mengelola notifikasi plugin.
 *
 * @package AI_Chat_Assistant
 * @subpackage AI_Chat_Assistant/includes
 */
class AI_Chat_Notification {

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
     * Kirim notifikasi ke user.
     *
     * @param int $user_id User ID.
     * @param string $message Notification message.
     * @param string $type Notification type.
     * @param array $data Additional data.
     * @return int|WP_Error Notification ID or error.
     */
    public function send_notification($user_id, $message, $type, $data = array()) {
        if (empty($user_id) || empty($message) || empty($type)) {
            return new WP_Error('missing_params', __('Missing required parameters', 'ai-chat-assistant'));
        }
        
        // Insert notification into database
        $notification_id = $this->database->insert_data('ai_chat_notifications', array(
            'user_id' => $user_id,
            'message' => $message,
            'type' => $type,
            'data' => maybe_serialize($data),
            'is_read' => 0
        ));
        
        if (!$notification_id) {
            return new WP_Error('db_error', __('Failed to save notification', 'ai-chat-assistant'));
        }
        
        // Trigger action so other plugins can hook into it
        do_action('ai_chat_notification_sent', $notification_id, $user_id, $message, $type, $data);
        
        return $notification_id;
    }
    
    /**
     * Ambil notifikasi user.
     *
     * @param int $user_id User ID.
     * @param int $limit Optional. Number of notifications to retrieve. Default 20.
     * @param int $offset Optional. Offset for pagination. Default 0.
     * @return array List of notifications.
     */
    public function get_user_notifications($user_id, $limit = 20, $offset = 0) {
        $notifications = $this->database->get_data('ai_chat_notifications', array(
            'where' => array('user_id' => $user_id),
            'orderby' => 'created_at',
            'order' => 'DESC',
            'limit' => $limit,
            'offset' => $offset
        ));
        
        // Unserialize data
        foreach ($notifications as &$notification) {
            $notification['data'] = maybe_unserialize($notification['data']);
        }
        
        return $notifications;
    }
    
    /**
     * Tandai notifikasi sebagai sudah dibaca.
     *
     * @param int $notification_id Notification ID.
     * @param int $user_id User ID to verify ownership.
     * @return bool|WP_Error True on success or error.
     */
    public function mark_as_read($notification_id, $user_id) {
        // Check ownership
        $notification = $this->database->get_data('ai_chat_notifications', array(
            'where' => array(
                'id' => $notification_id,
                'user_id' => $user_id
            )
        ), true);
        
        if (!$notification) {
            return new WP_Error('not_found', __('Notification not found or not owned by user', 'ai-chat-assistant'));
        }
        
        // Update read status
        $result = $this->database->update_data('ai_chat_notifications', array(
            'is_read' => 1
        ), array('id' => $notification_id));
        
        if (false === $result) {
            return new WP_Error('db_error', __('Failed to update notification', 'ai-chat-assistant'));
        }
        
        return true;
    }
    
    /**
     * Ambil jumlah notifikasi belum dibaca untuk user.
     *
     * @param int $user_id User ID.
     * @return int Count of unread notifications.
     */
    public function get_unread_count($user_id) {
        $notifications = $this->database->get_data('ai_chat_notifications', array(
            'where' => array(
                'user_id' => $user_id,
                'is_read' => 0
            )
        ));
        
        return count($notifications);
    }
    
    /**
     * Hapus notifikasi.
     *
     * @param int $notification_id Notification ID.
     * @param int $user_id User ID to verify ownership.
     * @return bool|WP_Error True on success or error.
     */
    public function delete_notification($notification_id, $user_id) {
        // Check ownership
        $notification = $this->database->get_data('ai_chat_notifications', array(
            'where' => array(
                'id' => $notification_id,
                'user_id' => $user_id
            )
        ), true);
        
        if (!$notification) {
            return new WP_Error('not_found', __('Notification not found or not owned by user', 'ai-chat-assistant'));
        }
        
        // Delete notification
        $result = $this->database->delete_data('ai_chat_notifications', array('id' => $notification_id));
        
        if (false === $result) {
            return new WP_Error('db_error', __('Failed to delete notification', 'ai-chat-assistant'));
        }
        
        return true;
    }
}