/**
 * JavaScript untuk Group Chat functionality.
 *
 * @package AI_Chat_Assistant
 * @subpackage AI_Chat_Assistant/public/js
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Elements
        const chatContainer = $('.group-chat-container');
        const chatMessages = $('.ai-chat-messages');
        const chatInput = $('.ai-chat-input');
        const sendBtn = $('.ai-chat-send-btn');
        const typingIndicator = $('.ai-chat-typing');
        const roomItems = $('.group-chat-room-item');
        const newRoomBtn = $('.group-chat-create-btn');
        const createRoomModal = $('#group-chat-create-modal');
        const createRoomForm = $('#group-chat-create-form');
        const modalClose = $('.group-chat-modal-close');
        
        // Variables
        let currentRoomId = chatContainer.data('room-id') || 0;
        let isProcessing = false;
        let lastMessageTime = null;
        let messagePollingInterval = null;
        
        // Initialize
        initializeChat();
        
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
        
        // Select chat room
        roomItems.on('click', function() {
            const roomId = $(this).data('room-id');
            selectRoom(roomId);
        });
        
        // Create new room button
        newRoomBtn.on('click', function() {
            createRoomModal.show();
        });
        
        // Close modal
        modalClose.on('click', function() {
            $(this).closest('.group-chat-modal').hide();
        });
        
        // Close modal when clicking outside
        $(window).on('click', function(e) {
            if ($(e.target).hasClass('group-chat-modal')) {
                $('.group-chat-modal').hide();
            }
        });
        
        // Create room form submission
        createRoomForm.on('submit', function(e) {
            e.preventDefault();
            
            const name = $('#room_name').val().trim();
            const description = $('#room_description').val().trim();
            const isPrivate = $('#room_private').is(':checked');
            
            if (name === '') {
                alert('Room name is required.');
                return;
            }
            
            const submitBtn = $('#room_submit');
            submitBtn.prop('disabled', true);
            submitBtn.val('Creating...');
            
            $.ajax({
                url: ai_chat_public.ajax_url,
                type: 'POST',
                data: {
                    action: 'group_chat_create_room',
                    nonce: ai_chat_public.nonce,
                    name: name,
                    description: description,
                    is_private: isPrivate ? 1 : 0
                },
                success: function(response) {
                    if (response.success) {
                        // Reload page to show new room
                        window.location.href = `?room_id=${response.data.room_id}`;
                    } else {
                        alert(response.data || 'Failed to create room.');
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                },
                complete: function() {
                    submitBtn.prop('disabled', false);
                    submitBtn.val('Create Room');
                }
            });
        });
        
        /**
         * Initialize chat
         */
        function initializeChat() {
            // Hide typing indicator
            typingIndicator.hide();
            
            // Highlight active room
            if (currentRoomId > 0) {
                $(`.group-chat-room-item[data-room-id="${currentRoomId}"]`).addClass('active');
                
                // Load initial messages
                loadMessages();
                
                // Setup polling for new messages
                startMessagePolling();
                
                // Enable input
                chatInput.prop('disabled', false);
                sendBtn.prop('disabled', false);
            } else {
                // No room selected
                chatMessages.html('<div class="ai-chat-no-room">Please select a chat room to start messaging.</div>');
                
                // Disable input
                chatInput.prop('disabled', true);
                sendBtn.prop('disabled', true);
            }
            
            // Scroll to bottom
            scrollToBottom();
        }
        
        /**
         * Select a chat room
         */
        function selectRoom(roomId) {
            // If same room, do nothing
            if (roomId === currentRoomId) {
                return;
            }
            
            // Stop current polling
            if (messagePollingInterval) {
                clearInterval(messagePollingInterval);
            }
            
            // Update room ID
            currentRoomId = roomId;
            
            // Update URL
            if (window.history.pushState) {
                const newUrl = window.location.href.split('?')[0] + `?room_id=${roomId}`;
                window.history.pushState({path: newUrl}, '', newUrl);
            }
            
            // Update UI
            roomItems.removeClass('active');
            $(`.group-chat-room-item[data-room-id="${roomId}"]`).addClass('active');
            
            // Clear messages
            chatMessages.empty();
            
            // Update room info
            const roomName = $(`.group-chat-room-item[data-room-id="${roomId}"]`).find('.group-chat-room-name').text();
            $('.group-chat-room-title').text(roomName);
            
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
            if (currentRoomId <= 0) {
                return;
            }
            
            $.ajax({
                url: ai_chat_public.ajax_url,
                type: 'POST',
                data: {
                    action: 'group_chat_get_messages',
                    nonce: ai_chat_public.nonce,
                    room_id: currentRoomId
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
                if (currentRoomId <= 0) {
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
                    action: 'group_chat_get_new_messages',
                    nonce: ai_chat_public.nonce,
                    room_id: currentRoomId,
                    last_time: lastMessageTime
                },
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        appendNewMessages(response.data);
                        
                        // Update last message time
                        lastMessageTime = response.data[response.data.length - 1].created_at;
                        
                        // Scroll to bottom if already at bottom
                        const chatBody = $('.ai-chat-body');
                        const isAtBottom = chatBody.scrollTop() + chatBody.innerHeight() >= chatBody[0].scrollHeight - 20;
                        
                        if (isAtBottom) {
                            scrollToBottom();
                        }
                    }
                }
            });
        }
        
        /**
         * Render all messages
         */
        function renderMessages(messages) {
            chatMessages.empty();
            
            if (messages.length === 0) {
                appendSystemMessage('No messages yet. Be the first to send a message!');
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
            const isCurrentUser = parseInt(message.user_id) === ai_chat_public.current_user_id;
            const messageClass = isCurrentUser ? 'user-message' : 'ai-message';
            
            const html = `
                <div class="ai-chat-message ${messageClass}">
                    ${!isCurrentUser ? `
                        <div class="ai-chat-message-avatar">
                            <img src="${message.user_avatar}" alt="${message.user_name}">
                        </div>
                    ` : ''}
                    <div class="ai-chat-message-content">
                        ${!isCurrentUser ? `<div class="ai-chat-message-sender">${message.user_name}</div>` : ''}
                        <div class="ai-chat-message-text">${formatMessage(message.message)}</div>
                        <div class="ai-chat-message-time">${formatTime(message.created_at)}</div>
                    </div>
                    ${isCurrentUser ? `
                        <div class="ai-chat-message-avatar">
                            <img src="${message.user_avatar}" alt="${message.user_name}">
                        </div>
                    ` : ''}
                </div>
            `;
            
            chatMessages.append(html);
        }
        
        /**
         * Send message to chat room
         */
        function sendMessage() {
            const message = chatInput.val().trim();
            
            if (message === '' || isProcessing || currentRoomId <= 0) {
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
                    action: 'group_chat_send_message',
                    nonce: ai_chat_public.nonce,
                    message: message,
                    room_id: currentRoomId
                },
                success: function(response) {
                    if (response.success) {
                        // Append the new message
                        const messageData = {
                            user_id: response.data.user_id,
                            user_name: response.data.user_name,
                            user_avatar: response.data.user_avatar,
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