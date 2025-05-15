/**
 * JavaScript untuk AI Chat functionality.
 *
 * @package AI_Chat_Assistant
 * @subpackage AI_Chat_Assistant/public/js
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Elements
        const chatContainer = $('.ai-chat-container');
        const chatMessages = $('.ai-chat-messages');
        const chatInput = $('.ai-chat-input');
        const sendBtn = $('.ai-chat-send-btn');
        const typingIndicator = $('.ai-chat-typing');
        const topicSelect = $('.ai-chat-topic-select');
        const historySelect = $('.ai-chat-history-select');
        const newChatBtn = $('.ai-chat-new-chat-btn');
        
        // Variables
        let conversationId = chatContainer.data('conversation-id') || 0;
        let topicId = chatContainer.data('topic-id') || 0;
        let isProcessing = false;
        
        // Initialize
        scrollToBottom();
        typingIndicator.hide();
        
        // Event handlers
        
        // Send message on button click
        sendBtn.on('click', function() {
            sendMessage();
        });
        
        // Send message on enter key
        chatInput.on('keypress', function(e) {
            if (e.which === 13 && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
        
        // Change topic
        topicSelect.on('change', function() {
            topicId = $(this).val();
            conversationId = 0; // Reset conversation when topic changes
            chatMessages.empty(); // Clear chat
            chatContainer.data('topic-id', topicId);
            chatContainer.data('conversation-id', 0);
            
            // Show initial message for selected topic
            if (topicId > 0) {
                const topicName = topicSelect.find('option:selected').text();
                appendSystemMessage(`You are now chatting about: ${topicName}. How can I help you with this topic?`);
            } else {
                appendSystemMessage(`Hello! How can I assist you today?`);
            }
        });
        
        // Load conversation from history
        historySelect.on('change', function() {
            const selectedId = $(this).val();
            
            if (selectedId > 0) {
                window.location.href = `?conversation_id=${selectedId}`;
            }
        });
        
        // New chat button
        newChatBtn.on('click', function(e) {
            e.preventDefault();
            conversationId = 0;
            chatMessages.empty();
            chatContainer.data('conversation-id', 0);
            
            // Show initial message
            if (topicId > 0) {
                const topicName = topicSelect.find('option:selected').text();
                appendSystemMessage(`You are now chatting about: ${topicName}. How can I help you with this topic?`);
            } else {
                appendSystemMessage(`Hello! How can I assist you today?`);
            }
            
            // Reset history select if available
            if (historySelect.length) {
                historySelect.val('');
            }
        });
        
        /**
         * Send message to AI
         */
        function sendMessage() {
            const message = chatInput.val().trim();
            
            if (message === '' || isProcessing) {
                return;
            }
            
            // Disable input while processing
            isProcessing = true;
            sendBtn.prop('disabled', true);
            
            // Append user message to chat
            appendUserMessage(message);
            
            // Clear input
            chatInput.val('');
            
            // Show typing indicator
            typingIndicator.show();
            
            // Scroll to bottom
            scrollToBottom();
            
            // Send API request
            $.ajax({
                url: ai_chat_public.ajax_url,
                type: 'POST',
                data: {
                    action: 'ai_chat_send_message',
                    nonce: ai_chat_public.nonce,
                    message: message,
                    conversation_id: conversationId,
                    topic_id: topicId
                },
                success: function(response) {
                    // Hide typing indicator
                    typingIndicator.hide();
                    
                    if (response.success) {
                        // Update conversation ID if new
                        if (conversationId === 0) {
                            conversationId = response.data.conversation_id;
                            chatContainer.data('conversation-id', conversationId);
                            
                            // Update URL if possible without page reload
                            if (window.history.pushState) {
                                const newUrl = window.location.href.split('?')[0] + `?conversation_id=${conversationId}`;
                                window.history.pushState({path: newUrl}, '', newUrl);
                            }
                        }
                        
                        // Append AI response
                        appendAIMessage(response.data.ai_response);
                        
                        // If history select exists, refresh it
                        if (historySelect.length && conversationId > 0) {
                            // This would typically require an additional AJAX call to get updated history
                            // For simplicity, we're just selecting the current conversation if it exists in the dropdown
                            const exists = historySelect.find(`option[value="${conversationId}"]`).length > 0;
                            
                            if (exists) {
                                historySelect.val(conversationId);
                            } else {
                                // Ideally, refresh the options via AJAX
                            }
                        }
                    } else {
                        // Show error
                        appendErrorMessage(response.data || ai_chat_public.strings.send_failed);
                    }
                    
                    // Enable input
                    isProcessing = false;
                    sendBtn.prop('disabled', false);
                    
                    // Focus on input
                    chatInput.focus();
                    
                    // Scroll to bottom
                    scrollToBottom();
                },
                error: function() {
                    // Hide typing indicator
                    typingIndicator.hide();
                    
                    // Show error
                    appendErrorMessage(ai_chat_public.strings.send_failed);
                    
                    // Enable input
                    isProcessing = false;
                    sendBtn.prop('disabled', false);
                    
                    // Scroll to bottom
                    scrollToBottom();
                }
            });
        }
        
        /**
         * Append user message to chat
         */
        function appendUserMessage(message) {
            const html = `
                <div class="ai-chat-message user-message">
                    <div class="ai-chat-message-content">
                        <div class="ai-chat-message-text">${formatMessage(message)}</div>
                        <div class="ai-chat-message-time">${getCurrentTime()}</div>
                    </div>
                    <div class="ai-chat-message-avatar">
                        <img src="${getUserAvatar()}" alt="User">
                    </div>
                </div>
            `;
            
            chatMessages.append(html);
        }
        
        /**
         * Append AI message to chat
         */
        function appendAIMessage(message) {
            const html = `
                <div class="ai-chat-message ai-message">
                    <div class="ai-chat-message-avatar">
                        <img src="${getAIAvatar()}" alt="AI">
                    </div>
                    <div class="ai-chat-message-content">
                        <div class="ai-chat-message-text">${formatMessage(message)}</div>
                        <div class="ai-chat-message-time">${getCurrentTime()}</div>
                    </div>
                </div>
            `;
            
            chatMessages.append(html);
        }
        
        /**
         * Append system message to chat
         */
        function appendSystemMessage(message) {
            const html = `
                <div class="ai-chat-message ai-message">
                    <div class="ai-chat-message-avatar">
                        <img src="${getAIAvatar()}" alt="AI">
                    </div>
                    <div class="ai-chat-message-content">
                        <div class="ai-chat-message-text">${formatMessage(message)}</div>
                        <div class="ai-chat-message-time">${getCurrentTime()}</div>
                    </div>
                </div>
            `;
            
            chatMessages.append(html);
        }
        
        /**
         * Append error message to chat
         */
        function appendErrorMessage(message) {
            const html = `
                <div class="ai-chat-message system-message">
                    <div class="ai-chat-message-content" style="background-color: #ffebee;">
                        <div class="ai-chat-message-text">${formatMessage(message)}</div>
                        <div class="ai-chat-message-time">${getCurrentTime()}</div>
                    </div>
                </div>
            `;
            
            chatMessages.append(html);
        }
        
        /**
         * Format message text (convert URLs to links, line breaks to <br>, etc.)
         */
        function formatMessage(text) {
            // Convert URLs to links
            const urlRegex = /(https?:\/\/[^\s]+)/g;
            text = text.replace(urlRegex, function(url) {
                return `<a href="${url}" target="_blank" rel="noopener noreferrer">${url}</a>`;
            });
            
            // Convert line breaks to <br>
            text = text.replace(/\n/g, '<br>');
            
            return text;
        }
        
        /**
         * Get current time formatted as HH:MM
         */
        function getCurrentTime() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            
            return `${hours}:${minutes}`;
        }
        
        /**
         * Get user avatar URL
         */
        function getUserAvatar() {
            // You can customize this to get the actual user avatar
            return 'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mp&f=y';
        }
        
        /**
         * Get AI avatar URL
         */
        function getAIAvatar() {
            // You can customize this to set your preferred AI avatar
            return 'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=identicon&f=y';
        }
        
        /**
         * Scroll chat to bottom
         */
        function scrollToBottom() {
            const chatBody = $('.ai-chat-body');
            chatBody.scrollTop(chatBody[0].scrollHeight);
        }
    });

})(jQuery);