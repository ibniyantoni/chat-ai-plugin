/**
 * JavaScript untuk User-to-User Chat functionality.
 *
 * @package AI_Chat_Assistant
 * @subpackage AI_Chat_Assistant/public/js
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Elements
        const chatContainer = $('.user-chat-container');
        const chatMessages = $('.ai-chat-messages');
        const chatInput = $('.ai-chat-input');
        const sendBtn = $('.ai-chat-send-btn');
        const typingIndicator = $('.ai-chat-typing');
        const contactItems = $('.user-chat-contact-item');
        const searchInput = $('.user-chat-search input');
        const newChatBtn = $('.user-chat-new-btn');
        const newChatModal = $('#user-chat-new-modal');
        const newChatForm = $('#user-chat-new-form');
        const modalClose = $('.user-chat-modal-close');
        
        // Variables
        let currentConversationId = chatContainer.data('conversation-id') || 0;
        let currentRecipientId = chatContainer.data('recipient-id') || 0;
        let isProcessing = false;
        let lastMessageTime = null;
        let messagePollingInterval = null;
        let conversations = [];
        
        // Initialize
        initializeChat();
        
        // Load user conversations
        loadConversations();
        
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
        
        // Select conversation
        $(document).on('click', '.user-chat-contact-item', function() {
            const conversationId = $(this).data('conversation-id');
            const recipientId = $(this).data('recipient-id');
            selectConversation(conversationId, recipientId);
        });
        
        // New chat button
        newChatBtn.on('click', function() {
            newChatModal.show();
            loadUsers();
        });
        
        // Close modal
        modalClose.on('click', function() {
            $(this).closest('.user-chat-modal').hide();
        });
        
        // Close modal when clicking outside
        $(window).on('click', function(e) {
            if ($(e.target).hasClass('user-chat-modal')) {
                $('.user-chat-modal').hide();
            }
        });
        
        // Search contacts
        searchInput.on('input', function() {
            const searchTerm = $(this).val().toLowerCase();
            
            $('.user-chat-contact-item').each(function() {
                const name = $(this).find('.user-chat-contact-name').text().toLowerCase();
                
                if (name.includes(searchTerm)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });
        
        // Search users in new chat modal
        $('#user_search').on('input', function() {
            const searchTerm = $(this).val().toLowerCase();
            
            if (searchTerm.length < 2) {
                return;
            }
            
            loadUsers(searchTerm);
        });
        
        // Select user from search results
        $(document).on('click', '.user-chat-search-result', function() {
            const userId = $(this).data('user-id');
            const userName = $(this).text();
            
            startNewConversation(userId);
            newChatModal.hide();
        });
        
        /**
         * Initialize chat
         */
        function initializeChat() {
            // Hide typing indicator
            typingIndicator.hide();
            
            if (currentRecipientId > 0) {
                // Load initial messages
                loadMessages();
                
                // Setup polling for new messages
                startMessagePolling();
                
                // Enable input
                chatInput.prop('disabled', false);
                sendBtn.prop('disabled', false);
            } else {
                // No conversation selected
                chatMessages.html('<div class="ai-chat-no-conversation">Select a conversation or start a new chat to begin messaging.</div>');
                
                // Disable input
                chatInput.prop('disabled', true);
                sendBtn.prop('disabled', true);
            }
            
            // Scroll to bottom
            scrollToBottom();
        }
        
        /**
         * Load user conversations
         */
        function loadConversations() {
            $.ajax({
                url: ai_chat_public.ajax_url,
                type: 'POST',
                data: {
                    action: 'user_chat_get_conversations',
                    nonce: ai_chat_public.nonce
                },
                success: function(response) {
                    if (response.success) {
                        conversations = response.data;
                        renderConversations();
                        
                        // Highlight current conversation
                        if (currentConversationId > 0) {
                            $(`.user-chat-contact-item[data-conversation-id="${currentConversationId}"]`).addClass('active');
                        }
                    }
                }
            });
        }
        
        /**
         * Render conversations list
         */
        function renderConversations() {
            const contactsList = $('.user-chat-contacts');
            contactsList.empty();
            
            if (conversations.length === 0) {
                contactsList.html('<div class="user-chat-no-contacts">No conversations yet.</div>');
                return;
            }
            
            conversations.forEach(function(conversation) {
                const html = `
                    <div class="user-chat-contact-item" data-conversation-id="${conversation.id}" data-recipient-id="${conversation.other_user_id}">
                        <div class="user-chat-contact-avatar">
                            <img src="${conversation.other_user_avatar}" alt="${conversation.other_user_name}">
                        </div>
                        <div class="user-chat-contact-info">
                            <div class="user-chat-contact-name">
                                ${conversation.other_user_name}
                                ${conversation.unread_count > 0 ? `<span class="user-chat-unread-badge">${conversation.unread_count}</span>` : ''}
                            </div>
                            <div class="user-chat-contact-status ${conversation.is_online ? 'online' : ''}">
                                ${conversation.is_online ? ai_chat_public.strings.online : ''}
                            </div>
                            <div class="user-chat-last-message">
                                ${conversation.last_message_is_mine ? 'You: ' : ''}${conversation.last_message}
                            </div>
                        </div>
                    </div>
                `;
                
                contactsList.append(html);
            });
        }
        
        /**
         * Select a conversation
         */
        function selectConversation(conversationId, recipientId) {
            // If same conversation, do nothing
            if (conversationId === currentConversationId) {
                return;
            }
            
            // Stop current polling
            if (messagePollingInterval) {
                clearInterval(messagePollingInterval);
            }
            
            // Update conversation ID
            currentConversationId = conversationId;
            currentRecipientId = recipientId;
            
            // Update URL
            if (window.history.pushState) {
                const newUrl = window.location.href.split('?')[0] + `?conversation_id=${conversationId}`;
                window.history.pushState({path: newUrl}, '', newUrl);
            }
            
            // Update UI
            $('.user-chat-contact-item').removeClass('active');
            $(`.user-chat-contact-item[data-conversation-id="${conversationId}"]`).addClass('active');
            
            // Clear unread badge
            $(`.user-chat-contact-item[data-conversation-id="${conversationId}"] .user-chat-unread-badge`).remove();
            
            // Mark as read
            $.ajax({
                url: ai_chat_public.ajax_url,
                type: 'POST',
                data: {
                    action: 'user_chat_mark_read',
                    nonce: ai_chat_public.nonce,
                    conversation_id: conversationId
                }
            });
            
            // Clear messages
            chatMessages.empty();
            
            // Update header info
            const recipientName = $(`.user-chat-contact-item[data-conversation-id="${conversationId}"]`).find('.user-chat-contact-name').text().trim();
            $('.ai-chat-title').text(recipientName);
            
            // Enable input
            chatInput.prop('disabled', false);
            sendBtn.prop('disabled', false);
            
            // Load messages
            loadMessages();
            
            // Start polling for new messages
            startMessagePolling();
        }
        
        /**
         * Load chat messages
         */
        function loadMessages() {
            if (currentConversationId <= 0) {
                return;
            }
            
            $.ajax({
                url: ai_chat_public.ajax_url,
                type: 'POST',
                data: {
                    action: 'user_chat_get_messages',
                    nonce: ai_chat_public.nonce,
                    conversation_id: currentConversationId
                },
                success: function(response) {
                    if (response.success) {
                        renderMessages(response.data);
                        
                        // Update last message time
                        if (response.data.length > 0) {
                            lastMessageTime = response.data[response.data.length - 1].created_at;
                        }
                        
                        // Scroll to bottom
                        scrollToBottom();
                    } else {
                        appendErrorMessage(response.data || 'Failed to load messages.');
                    }
                },
                error: function() {
                    appendErrorMessage('Failed to load messages. Please try again.');
                }
            });
        }
        
        /**
         * Start polling for new messages
         */
        function startMessagePolling() {
            messagePollingInterval = setInterval(function() {
                if (currentConversationId <= 0) {
                    return;
                }
                
                checkNewMessages();
            }, 5000); // Check every 5 seconds
        }
        
        /**
         * Check for new messages
         */
        function checkNewMessages() {
            $.ajax({
                url: ai_chat_public.ajax_url,
                type: 'POST',
                data: {
                    action: 'user_chat_get_new_messages',
                    nonce: ai_chat_public.nonce,
                    conversation_id: currentConversationId,
                    last_time: lastMessageTime
                },
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        appendNewMessages(response.data);
                        
                        // Update last message time
                        lastMessageTime = response.data[response.data.length - 1].created_at;
                        
                        // Mark as read
                        $.ajax({
                            url: ai_chat_public.ajax_url,
                            type: 'POST',
                            data: {
                                action: 'user_chat_mark_read',
                                nonce: ai_chat_public.nonce,
                                conversation_id: currentConversationId
                            }
                        });
                        
                        // Scroll to bottom if already at bottom
                        const chatBody = $('.ai-chat-body');
                        const isAtBottom = chatBody.scrollTop() + chatBody.innerHeight() >= chatBody[0].scrollHeight - 20;
                        
                        if (isAtBottom) {
                            scrollToBottom();
                        }
                    }
                }
            });
            
            // Also refresh conversations list to update unread counts
            loadConversations();
        }
        
        /**
         * Render all messages
         */
        function renderMessages(messages) {
            chatMessages.empty();
            
            if (messages.length === 0) {
                appendSystemMessage('No messages yet. Start the conversation!');
                return;
            }
            
            messages.forEach(function(message) {
                appendMessage(message);
            });
        }
        
        /**
         * Append new messages
         */
        function appendNewMessages(messages) {
            messages.forEach(function(message) {
                appendMessage(message);
            });
        }
        
        /**
         * Append a message to chat
         */
        function appendMessage(message) {
            const isCurrentUser = parseInt(message.sender_id) === ai_chat_public.current_user_id;
            const messageClass = isCurrentUser ? 'user-message' : 'ai-message';
            
            const html = `
                <div class="ai-chat-message ${messageClass}">
                    ${!isCurrentUser ? `
                        <div class="ai-chat-message-avatar">
                            <img src="${message.sender_avatar}" alt="${message.sender_name}">
                        </div>
                    ` : ''}
                    <div class="ai-chat-message-content">
                        <div class="ai-chat-message-text">${formatMessage(message.message)}</div>
                        <div class="ai-chat-message-time">${formatTime(message.created_at)}</div>
                    </div>
                    ${isCurrentUser ? `
                        <div class="ai-chat-message-avatar">
                            <img src="${message.sender_avatar}" alt="${message.sender_name}">
                        </div>
                    ` : ''}
                </div>
            `;
            
            chatMessages.append(html);
        }
        
        /**
         * Send message to recipient
         */
        function sendMessage() {
            const message = chatInput.val().trim();
            
            if (message === '' || isProcessing || (currentConversationId <= 0 && currentRecipientId <= 0)) {
                return;
            }
            
            // Disable input while processing
            isProcessing = true;
            sendBtn.prop('disabled', true);
            
            // Clear input
            chatInput.val('');
            
            // Send API request
            $.ajax({
                url: ai_chat_public.ajax_url,
                type: 'POST',
                data: {
                    action: 'user_chat_send_message',
                    nonce: ai_chat_public.nonce,
                    message: message,
                    conversation_id: currentConversationId,
                    recipient_id: currentRecipientId
                },
                success: function(response) {
                    if (response.success) {
                        // Update conversation ID if new
                        if (currentConversationId <= 0) {
                            currentConversationId = response.data.conversation_id;
                            
                            // Update URL
                            if (window.history.pushState) {
                                const newUrl = window.location.href.split('?')[0] + `?conversation_id=${currentConversationId}`;
                                window.history.pushState({path: newUrl}, '', newUrl);
                            }
                            
                            // Refresh conversations list
                            loadConversations();
                        }
                        
                        // Append the new message
                        const messageData = {
                            sender_id: response.data.sender_id,
                            sender_name: response.data.sender_name,
                            sender_avatar: response.data.sender_avatar,
                            message: response.data.message,
                            created_at: response.data.timestamp
                        };
                        
                        appendMessage(messageData);
                        
                        // Update last message time
                        lastMessageTime = response.data.timestamp;
                        
                        // Scroll to bottom
                        scrollToBottom();
                    } else {
                        // Show error
                        appendErrorMessage(response.data || ai_chat_public.strings.send_failed);
                    }
                },
                error: function() {
                    // Show error
                    appendErrorMessage(ai_chat_public.strings.send_failed);
                },
                complete: function() {
                    // Enable input
                    isProcessing = false;
                    sendBtn.prop('disabled', false);
                    
                    // Focus on input
                    chatInput.focus();
                }
            });
        }
        
        /**
         * Load users for new chat
         */
        function loadUsers(searchTerm = '') {
            $.ajax({
                url: ai_chat_public.ajax_url,
                type: 'POST',
                data: {
                    action: 'user_chat_get_users',
                    nonce: ai_chat_public.nonce,
                    search: searchTerm
                },
                success: function(response) {
                    if (response.success) {
                        renderUserSearchResults(response.data);
                    }
                }
            });
        }
        
        /**
         * Render user search results
         */
        function renderUserSearchResults(users) {
            const resultsContainer = $('#user_search_results');
            resultsContainer.empty();
            
            if (users.length === 0) {
                resultsContainer.html('<p>No users found.</p>');
                return;
            }
            
            users.forEach(function(user) {
                const html = `
                    <div class="user-chat-search-result" data-user-id="${user.id}">
                        <img src="${user.avatar}" alt="${user.name}" width="24" height="24" style="border-radius: 50%; margin-right: 5px;">
                        ${user.name}
                        ${user.is_online ? ' <span style="color: #38c172;">(online)</span>' : ''}
                    </div>
                `;
                
                resultsContainer.append(html);
            });
        }
        
        /**
         * Start a new conversation with a user
         */
        function startNewConversation(userId) {
            // Check if conversation already exists
            const existingConversation = conversations.find(conv => conv.other_user_id == userId);
            
            if (existingConversation) {
                selectConversation(existingConversation.id, userId);
                return;
            }
            
            // Clear current conversation
            chatMessages.empty();
            
            // Set recipient ID
            currentConversationId = 0;
            currentRecipientId = userId;
            
            // Get user info
            $.ajax({
                url: ai_chat_public.ajax_url,
                type: 'POST',
                data: {
                    action: 'user_chat_get_user_info',
                    nonce: ai_chat_public.nonce,
                    user_id: userId
                },
                success: function(response) {
                    if (response.success) {
                        // Update header info
                        $('.ai-chat-title').text(response.data.name);
                        
                        // Enable input
                        chatInput.prop('disabled', false);
                        sendBtn.prop('disabled', false);
                        
                        // Add system message
                        appendSystemMessage(`Start chatting with ${response.data.name}!`);
                        
                        // Focus on input
                        chatInput.focus();
                    }
                }
            });
        }
        
        /**
         * Append system message to chat
         */
        function appendSystemMessage(message) {
            const html = `
                <div class="ai-chat-message system-message">
                    <div class="ai-chat-message-content" style="background-color: #f0f0f0; color: #666;">
                        <div class="ai-chat-message-text">${formatMessage(message)}</div>
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
                    <div class="ai-chat-message-content" style="background-color: #ffebee; color: #c62828;">
                        <div class="ai-chat-message-text">${formatMessage(message)}</div>
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
         * Format time from database timestamp
         */
        function formatTime(timestamp) {
            const date = new Date(timestamp);
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            
            return `${hours}:${minutes}`;
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