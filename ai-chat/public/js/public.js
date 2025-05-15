/**
 * Public JavaScript untuk plugin AI Chat Assistant.
 *
 * @package AI_Chat_Assistant
 * @subpackage AI_Chat_Assistant/public
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Common functionality for all chat types
        
        // Handle notification updates if available
        if (typeof updateNotifications === 'function') {
            // Check for new notifications every 30 seconds
            setInterval(updateNotifications, 30000);
        }
        
        // Handle user activity tracking if available
        if (typeof updateUserActivity === 'function') {
            // Update user activity every minute
            setInterval(updateUserActivity, 60000);
            
            // Also update on page load
            updateUserActivity();
        }
        
        // Auto resize textarea
        $(document).on('input', '.ai-chat-input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    });

})(jQuery);