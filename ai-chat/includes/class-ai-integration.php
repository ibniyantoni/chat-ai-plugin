<?php
/**
 * Menangani integrasi dengan AI API (OpenAI/Anthropic).
 *
 * @package AI_Chat_Assistant
 * @subpackage AI_Chat_Assistant/includes
 */
class AI_Chat_Integration {

    /**
     * Database instance.
     *
     * @var AI_Chat_Database
     */
    private $database;

    /**
     * API Key untuk layanan AI.
     *
     * @var string
     */
    private $api_key;

    /**
     * Provider AI yang digunakan (openai/anthropic).
     *
     * @var string
     */
    private $provider;

    /**
     * Model AI yang digunakan.
     *
     * @var string
     */
    private $model;

    /**
     * Initialize class.
     *
     * @param AI_Chat_Database $database Database instance.
     */
    public function __construct($database) {
        $this->database = $database;
        
        // Load settings
        $this->api_key = get_option('ai_chat_api_key', '');
        $this->provider = get_option('ai_chat_provider', 'openai');
        $this->model = get_option('ai_chat_model', 'gpt-3.5-turbo');
    }

    /**
     * Kirim pesan ke AI API dan dapatkan respons.
     *
     * @param array $messages Array of messages in the conversation.
     * @param int $topic_id Optional. The topic ID for context.
     * @return string|WP_Error Response from the AI provider or error.
     */
    public function get_ai_response($messages, $topic_id = 0) {
        if (empty($this->api_key)) {
            return new WP_Error('missing_api_key', __('API key is not configured', 'ai-chat-assistant'));
        }
        
        // Get prompt context if topic_id is provided
        $system_message = '';
        if ($topic_id > 0) {
            $topic = get_post($topic_id);
            if ($topic && 'ai_chat_topic' === $topic->post_type) {
                $system_message = $topic->post_content;
            }
        }
        
        // Choose API endpoint based on provider
        if ('openai' === $this->provider) {
            return $this->get_openai_response($messages, $system_message);
        } elseif ('anthropic' === $this->provider) {
            return $this->get_anthropic_response($messages, $system_message);
        }
        
        return new WP_Error('invalid_provider', __('Invalid AI provider selected', 'ai-chat-assistant'));
    }

    /**
     * Get response from OpenAI API.
     *
     * @param array $messages Array of messages in the conversation.
     * @param string $system_message Optional. System message for context.
     * @return string|WP_Error Response from OpenAI or error.
     */
    private function get_openai_response($messages, $system_message = '') {
        $api_url = 'https://api.openai.com/v1/chat/completions';
        
        // Format messages for OpenAI API
        $formatted_messages = array();
        
        // Add system message if provided
        if (!empty($system_message)) {
            $formatted_messages[] = array(
                'role' => 'system',
                'content' => $system_message
            );
        }
        
        // Add conversation messages
        foreach ($messages as $message) {
            $formatted_messages[] = array(
                'role' => $message['is_ai'] ? 'assistant' : 'user',
                'content' => $message['message']
            );
        }
        
        // Prepare request body
        $request_body = array(
            'model' => $this->model,
            'messages' => $formatted_messages,
            'temperature' => 0.7,
            'max_tokens' => 1000
        );
        
        // Send request to OpenAI API
        $response = wp_remote_post($api_url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key
            ),
            'body' => json_encode($request_body),
            'timeout' => 60
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            $error_message = wp_remote_retrieve_response_message($response);
            return new WP_Error('openai_api_error', $error_message);
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['choices'][0]['message']['content'])) {
            return $body['choices'][0]['message']['content'];
        }
        
        return new WP_Error('openai_response_error', __('Invalid response from OpenAI', 'ai-chat-assistant'));
    }

    /**
     * Get response from Anthropic API.
     *
     * @param array $messages Array of messages in the conversation.
     * @param string $system_message Optional. System message for context.
     * @return string|WP_Error Response from Anthropic or error.
     */
    private function get_anthropic_response($messages, $system_message = '') {
        $api_url = 'https://api.anthropic.com/v1/messages';
        
        // Format conversation for Anthropic's API
        $formatted_messages = array();
        
        // Add conversation messages
        foreach ($messages as $message) {
            $formatted_messages[] = array(
                'role' => $message['is_ai'] ? 'assistant' : 'user',
                'content' => $message['message']
            );
        }
        
        // Prepare request body
        $request_body = array(
            'model' => $this->model, // Default to claude-2 if not specified
            'messages' => $formatted_messages,
            'max_tokens' => 1000
        );
        
        // Add system if provided
        if (!empty($system_message)) {
            $request_body['system'] = $system_message;
        }
        
        // Send request to Anthropic API
        $response = wp_remote_post($api_url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'X-API-Key' => $this->api_key,
                'anthropic-version' => '2023-06-01'
            ),
            'body' => json_encode($request_body),
            'timeout' => 60
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            $error_message = wp_remote_retrieve_response_message($response);
            return new WP_Error('anthropic_api_error', $error_message);
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['content'][0]['text'])) {
            return $body['content'][0]['text'];
        }
        
        return new WP_Error('anthropic_response_error', __('Invalid response from Anthropic', 'ai-chat-assistant'));
    }

    /**
     * Simpan percakapan dengan AI.
     *
     * @param int $user_id User ID.
     * @param int $topic_id Topic ID.
     * @param string $title Conversation title.
     * @param array $messages Messages in the conversation.
     * @return int|WP_Error Conversation ID or error.
     */
    public function save_conversation($user_id, $topic_id, $title, $messages) {
        // Insert conversation
        $conversation_id = $this->database->insert_data('ai_chat_conversations', array(
            'user_id' => $user_id,
            'topic_id' => $topic_id,
            'title' => $title
        ));
        
        if (!$conversation_id) {
            return new WP_Error('db_error', __('Failed to save conversation', 'ai-chat-assistant'));
        }
        
        // Insert messages
        foreach ($messages as $message) {
            $result = $this->database->insert_data('ai_chat_messages', array(
                'conversation_id' => $conversation_id,
                'user_id' => $user_id,
                'message' => $message['message'],
                'is_ai' => $message['is_ai']
            ));
            
            if (!$result) {
                return new WP_Error('db_error', __('Failed to save messages', 'ai-chat-assistant'));
            }
        }
        
        return $conversation_id;
    }

    /**
     * Ambil percakapan yang tersimpan.
     *
     * @param int $conversation_id Conversation ID.
     * @return array|WP_Error Conversation data or error.
     */
    public function get_conversation($conversation_id) {
        // Get conversation
        $conversation = $this->database->get_data('ai_chat_conversations', array(
            'where' => array('id' => $conversation_id)
        ), true);
        
        if (!$conversation) {
            return new WP_Error('not_found', __('Conversation not found', 'ai-chat-assistant'));
        }
        
        // Get messages
        $messages = $this->database->get_data('ai_chat_messages', array(
            'where' => array('conversation_id' => $conversation_id),
            'orderby' => 'created_at',
            'order' => 'ASC'
        ));
        
        return array(
            'conversation' => $conversation,
            'messages' => $messages
        );
    }

    /**
     * Ambil daftar percakapan untuk user tertentu.
     *
     * @param int $user_id User ID.
     * @return array List of conversations.
     */
    public function get_user_conversations($user_id) {
        return $this->database->get_data('ai_chat_conversations', array(
            'where' => array('user_id' => $user_id),
            'orderby' => 'updated_at',
            'order' => 'DESC'
        ));
    }
}