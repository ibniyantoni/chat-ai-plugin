<?php
/**
 * Template untuk halaman manage chat rooms.
 *
 * @package AI_Chat_Assistant
 * @subpackage AI_Chat_Assistant/admin/views
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="notice notice-info">
        <p><?php _e('Manage chat rooms for group discussions. You can create, edit, and delete rooms, as well as view and manage room members.', 'ai-chat-assistant'); ?></p>
    </div>
    
    <div id="ai-chat-rooms-response" class="notice" style="display:none;"></div>
    
    <h2><?php _e('Chat Rooms', 'ai-chat-assistant'); ?></h2>
    
    <div class="tablenav top">
        <div class="alignleft actions">
            <a href="#" class="button button-primary" id="create-room-btn"><?php _e('Create New Chat Room', 'ai-chat-assistant'); ?></a>
        </div>
        <br class="clear">
    </div>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th scope="col" class="column-name"><?php _e('Name', 'ai-chat-assistant'); ?></th>
                <th scope="col" class="column-description"><?php _e('Description', 'ai-chat-assistant'); ?></th>
                <th scope="col" class="column-creator"><?php _e('Created By', 'ai-chat-assistant'); ?></th>
                <th scope="col" class="column-visibility"><?php _e('Visibility', 'ai-chat-assistant'); ?></th>
                <th scope="col" class="column-members"><?php _e('Members', 'ai-chat-assistant'); ?></th>
                <th scope="col" class="column-messages"><?php _e('Messages', 'ai-chat-assistant'); ?></th>
                <th scope="col" class="column-created"><?php _e('Created', 'ai-chat-assistant'); ?></th>
                <th scope="col" class="column-actions"><?php _e('Actions', 'ai-chat-assistant'); ?></th>
            </tr>
        </thead>
        <tbody id="the-list">
            <?php if (empty($rooms)) : ?>
                <tr>
                    <td colspan="8"><?php _e('No chat rooms found.', 'ai-chat-assistant'); ?></td>
                </tr>
            <?php else : ?>
                <?php foreach ($rooms as $room) : ?>
                    <tr>
                        <td class="column-name">
                            <strong><?php echo esc_html($room['name']); ?></strong>
                        </td>
                        <td class="column-description">
                            <?php echo !empty($room['description']) ? esc_html($room['description']) : '&mdash;'; ?>
                        </td>
                        <td class="column-creator">
                            <?php echo esc_html($room['creator_name']); ?>
                        </td>
                        <td class="column-visibility">
                            <?php echo $room['is_private'] ? __('Private', 'ai-chat-assistant') : __('Public', 'ai-chat-assistant'); ?>
                        </td>
                        <td class="column-members">
                            <?php echo intval($room['member_count']); ?>
                        </td>
                        <td class="column-messages">
                            <?php echo intval($room['message_count']); ?>
                        </td>
                        <td class="column-created">
                            <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($room['created_at'])); ?>
                        </td>
                        <td class="column-actions">
                            <a href="#" class="view-members-btn" data-room-id="<?php echo intval($room['id']); ?>"><?php _e('View Members', 'ai-chat-assistant'); ?></a> | 
                            <a href="#" class="edit-room-btn" data-room-id="<?php echo intval($room['id']); ?>"><?php _e('Edit', 'ai-chat-assistant'); ?></a> | 
                            <a href="#" class="delete-room-btn" data-room-id="<?php echo intval($room['id']); ?>"><?php _e('Delete', 'ai-chat-assistant'); ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Create/Edit Room Modal -->
<div id="room-modal" class="ai-chat-modal" style="display:none;">
    <div class="ai-chat-modal-content">
        <span class="ai-chat-modal-close">&times;</span>
        <h3 id="room-modal-title"><?php _e('Create New Chat Room', 'ai-chat-assistant'); ?></h3>
        
        <form id="room-form">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="room_name"><?php _e('Room Name', 'ai-chat-assistant'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="room_name" id="room_name" class="regular-text" required>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="room_description"><?php _e('Description', 'ai-chat-assistant'); ?></label>
                    </th>
                    <td>
                        <textarea name="room_description" id="room_description" rows="4" class="regular-text"></textarea>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="room_private"><?php _e('Visibility', 'ai-chat-assistant'); ?></label>
                    </th>
                    <td>
                        <select name="room_private" id="room_private">
                            <option value="0"><?php _e('Public', 'ai-chat-assistant'); ?></option>
                            <option value="1"><?php _e('Private', 'ai-chat-assistant'); ?></option>
                        </select>
                        <p class="description"><?php _e('Public rooms are visible to all users, while private rooms require an invitation.', 'ai-chat-assistant'); ?></p>
                    </td>
                </tr>
            </table>
            
            <input type="hidden" name="room_id" id="room_id" value="0">
            <input type="hidden" name="action" value="ai_chat_manage_room">
            <input type="hidden" name="room_action" id="room_action" value="create">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('ai_chat_admin_nonce'); ?>">
            
            <p class="submit">
                <input type="submit" name="submit" id="room-submit" class="button button-primary" value="<?php _e('Create Room', 'ai-chat-assistant'); ?>">
            </p>
        </form>
    </div>
</div>

<!-- View Members Modal -->
<div id="members-modal" class="ai-chat-modal" style="display:none;">
    <div class="ai-chat-modal-content">
        <span class="ai-chat-modal-close">&times;</span>
        <h3 id="members-modal-title"><?php _e('Room Members', 'ai-chat-assistant'); ?>: <span id="room-name"></span></h3>
        
        <div class="tablenav top">
            <div class="alignleft actions">
                <a href="#" class="button" id="invite-member-btn"><?php _e('Invite New Member', 'ai-chat-assistant'); ?></a>
            </div>
            <br class="clear">
        </div>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col"><?php _e('User', 'ai-chat-assistant'); ?></th>
                    <th scope="col"><?php _e('Email', 'ai-chat-assistant'); ?></th>
                    <th scope="col"><?php _e('Role', 'ai-chat-assistant'); ?></th>
                    <th scope="col"><?php _e('Joined', 'ai-chat-assistant'); ?></th>
                    <th scope="col"><?php _e('Actions', 'ai-chat-assistant'); ?></th>
                </tr>
            </thead>
            <tbody id="members-list">
                <!-- Filled by JavaScript -->
            </tbody>
        </table>
    </div>
</div>

<!-- Invite Member Modal -->
<div id="invite-modal" class="ai-chat-modal" style="display:none;">
    <div class="ai-chat-modal-content">
        <span class="ai-chat-modal-close">&times;</span>
        <h3><?php _e('Invite User to Chat Room', 'ai-chat-assistant'); ?></h3>
        
        <form id="invite-form">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="invite_email"><?php _e('Email Address', 'ai-chat-assistant'); ?></label>
                    </th>
                    <td>
                        <input type="email" name="invite_email" id="invite_email" class="regular-text" required>
                        <p class="description"><?php _e('Enter the email address of the user you want to invite.', 'ai-chat-assistant'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="invite_role"><?php _e('Role', 'ai-chat-assistant'); ?></label>
                    </th>
                    <td>
                        <select name="invite_role" id="invite_role">
                            <option value="member"><?php _e('Member', 'ai-chat-assistant'); ?></option>
                            <option value="moderator"><?php _e('Moderator', 'ai-chat-assistant'); ?></option>
                        </select>
                    </td>
                </tr>
            </table>
            
            <input type="hidden" name="room_id" id="invite_room_id" value="0">
            <input type="hidden" name="action" value="ai_chat_invite_member">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('ai_chat_admin_nonce'); ?>">
            
            <p class="submit">
                <input type="submit" name="submit" id="invite-submit" class="button button-primary" value="<?php _e('Send Invitation', 'ai-chat-assistant'); ?>">
            </p>
        </form>
    </div>
</div>

<style>
    .ai-chat-modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.4);
    }
    
    .ai-chat-modal-content {
        background-color: #fefefe;
        margin: 10% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 60%;
        max-width: 700px;
        border-radius: 5px;
        box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2);
    }
    
    .ai-chat-modal-close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }
    
    .ai-chat-modal-close:hover,
    .ai-chat-modal-close:focus {
        color: black;
        text-decoration: none;
    }
    
    .column-name {
        width: 20%;
    }
    
    .column-description {
        width: 25%;
    }
    
    .column-creator,
    .column-visibility,
    .column-members,
    .column-messages,
    .column-created {
        width: 10%;
    }
    
    .column-actions {
        width: 15%;
    }
</style>

<script>
    jQuery(document).ready(function($) {
        // Modal handling
        $('.ai-chat-modal-close').on('click', function() {
            $(this).closest('.ai-chat-modal').hide();
        });
        
        $(window).on('click', function(e) {
            if ($(e.target).hasClass('ai-chat-modal')) {
                $('.ai-chat-modal').hide();
            }
        });
        
        // Create Room button
        $('#create-room-btn').on('click', function(e) {
            e.preventDefault();
            
            // Reset form
            $('#room-form')[0].reset();
            $('#room_id').val(0);
            $('#room_action').val('create');
            $('#room-modal-title').text('<?php _e('Create New Chat Room', 'ai-chat-assistant'); ?>');
            $('#room-submit').val('<?php _e('Create Room', 'ai-chat-assistant'); ?>');
            
            $('#room-modal').show();
        });
        
        // Edit Room button
        $('.edit-room-btn').on('click', function(e) {
            e.preventDefault();
            
            var roomId = $(this).data('room-id');
            var roomName = $(this).closest('tr').find('.column-name strong').text();
            var roomDescription = $(this).closest('tr').find('.column-description').text().trim();
            var isPrivate = $(this).closest('tr').find('.column-visibility').text().trim() === '<?php _e('Private', 'ai-chat-assistant'); ?>' ? 1 : 0;
            
            $('#room_id').val(roomId);
            $('#room_name').val(roomName);
            $('#room_description').val(roomDescription === '—' ? '' : roomDescription);
            $('#room_private').val(isPrivate);
            
            $('#room_action').val('update');
            $('#room-modal-title').text('<?php _e('Edit Chat Room', 'ai-chat-assistant'); ?>');
            $('#room-submit').val('<?php _e('Update Room', 'ai-chat-assistant'); ?>');
            
            $('#room-modal').show();
        });
        
        // Delete Room button
        $('.delete-room-btn').on('click', function(e) {
            e.preventDefault();
            
            if (!confirm('<?php _e('Are you sure you want to delete this chat room? This will permanently delete all messages and cannot be undone.', 'ai-chat-assistant'); ?>')) {
                return;
            }
            
            var roomId = $(this).data('room-id');
            var row = $(this).closest('tr');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ai_chat_manage_room',
                    room_action: 'delete',
                    room_id: roomId,
                    nonce: '<?php echo wp_create_nonce('ai_chat_admin_nonce'); ?>'
                },
                beforeSend: function() {
                    row.css('opacity', '0.5');
                },
                success: function(response) {
                    if (response.success) {
                        row.fadeOut(400, function() {
                            row.remove();
                            
                            // Show empty message if no rooms left
                            if ($('#the-list tr').length === 0) {
                                $('#the-list').html('<tr><td colspan="8"><?php _e('No chat rooms found.', 'ai-chat-assistant'); ?></td></tr>');
                            }
                        });
                        
                        $('#ai-chat-rooms-response').removeClass('notice-error').addClass('notice-success').html('<p>' + response.data + '</p>').show();
                    } else {
                        row.css('opacity', '1');
                        $('#ai-chat-rooms-response').removeClass('notice-success').addClass('notice-error').html('<p>' + response.data + '</p>').show();
                    }
                    
                    // Hide message after 4 seconds
                    setTimeout(function() {
                        $('#ai-chat-rooms-response').fadeOut();
                    }, 4000);
                },
                error: function() {
                    row.css('opacity', '1');
                    $('#ai-chat-rooms-response').removeClass('notice-success').addClass('notice-error').html('<p><?php _e('An error occurred. Please try again.', 'ai-chat-assistant'); ?></p>').show();
                    
                    setTimeout(function() {
                        $('#ai-chat-rooms-response').fadeOut();
                    }, 4000);
                }
            });
        });
        
        // View Members button
        $('.view-members-btn').on('click', function(e) {
            e.preventDefault();
            
            var roomId = $(this).data('room-id');
            var roomName = $(this).closest('tr').find('.column-name strong').text();
            
            $('#room-name').text(roomName);
            $('#invite_room_id').val(roomId);
            
            // Load members
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ai_chat_manage_room',
                    room_action: 'view_members',
                    room_id: roomId,
                    nonce: '<?php echo wp_create_nonce('ai_chat_admin_nonce'); ?>'
                },
                beforeSend: function() {
                    $('#members-list').html('<tr><td colspan="5"><?php _e('Loading members...', 'ai-chat-assistant'); ?></td></tr>');
                },
                success: function(response) {
                    if (response.success) {
                        var members = response.data.members;
                        var html = '';
                        
                        if (members.length === 0) {
                            html = '<tr><td colspan="5"><?php _e('No members found.', 'ai-chat-assistant'); ?></td></tr>';
                        } else {
                            $.each(members, function(i, member) {
                                html += '<tr>';
                                html += '<td>' + member.user_name + '</td>';
                                html += '<td>' + member.user_email + '</td>';
                                html += '<td>';
                                
                                if (member.is_creator) {
                                    html += '<?php _e('Creator', 'ai-chat-assistant'); ?>';
                                } else if (member.is_moderator == 1) {
                                    html += '<?php _e('Moderator', 'ai-chat-assistant'); ?>';
                                } else {
                                    html += '<?php _e('Member', 'ai-chat-assistant'); ?>';
                                }
                                
                                html += '</td>';
                                html += '<td>' + member.joined_at + '</td>';
                                html += '<td>';
                                
                                if (!member.is_creator) {
                                    if (member.is_moderator == 1) {
                                        html += '<a href="#" class="change-role-btn" data-room-id="' + roomId + '" data-user-id="' + member.user_id + '" data-role="member"><?php _e('Make Member', 'ai-chat-assistant'); ?></a> | ';
                                    } else {
                                        html += '<a href="#" class="change-role-btn" data-room-id="' + roomId + '" data-user-id="' + member.user_id + '" data-role="moderator"><?php _e('Make Moderator', 'ai-chat-assistant'); ?></a> | ';
                                    }
                                    
                                    html += '<a href="#" class="remove-member-btn" data-room-id="' + roomId + '" data-user-id="' + member.user_id + '"><?php _e('Remove', 'ai-chat-assistant'); ?></a>';
                                } else {
                                    html += '&mdash;';
                                }
                                
                                html += '</td>';
                                html += '</tr>';
                            });
                        }
                        
                        $('#members-list').html(html);
                    } else {
                        $('#members-list').html('<tr><td colspan="5">' + response.data + '</td></tr>');
                    }
                },
                error: function() {
                    $('#members-list').html('<tr><td colspan="5"><?php _e('An error occurred. Please try again.', 'ai-chat-assistant'); ?></td></tr>');
                }
            });
            
            $('#members-modal').show();
        });
        
        // Invite Member button
        $('#invite-member-btn').on('click', function(e) {
            e.preventDefault();
            
            $('#invite-form')[0].reset();
            $('#invite-modal').show();
        });
        
        // Room form submission
        $('#room-form').on('submit', function(e) {
            e.preventDefault();
            
            var form = $(this);
            var submitBtn = $('#room-submit');
            
            submitBtn.prop('disabled', true);
            submitBtn.val('<?php _e('Processing...', 'ai-chat-assistant'); ?>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: form.serialize(),
                success: function(response) {
                    if (response.success) {
                        $('#room-modal').hide();
                        location.reload(); // Reload to show changes
                    } else {
                        alert(response.data);
                    }
                },
                error: function() {
                    alert('<?php _e('An error occurred. Please try again.', 'ai-chat-assistant'); ?>');
                },
                complete: function() {
                    submitBtn.prop('disabled', false);
                    submitBtn.val($('#room_action').val() === 'create' ? '<?php _e('Create Room', 'ai-chat-assistant'); ?>' : '<?php _e('Update Room', 'ai-chat-assistant'); ?>');
                }
            });
        });
        
        // Invite form submission
        $('#invite-form').on('submit', function(e) {
            e.preventDefault();
            
            var form = $(this);
            var submitBtn = $('#invite-submit');
            
            submitBtn.prop('disabled', true);
            submitBtn.val('<?php _e('Sending...', 'ai-chat-assistant'); ?>');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: form.serialize(),
                success: function(response) {
                    if (response.success) {
                        $('#invite-modal').hide();
                        alert(response.data);
                        
                        // Refresh member list
                        $('.view-members-btn[data-room-id="' + $('#invite_room_id').val() + '"]').trigger('click');
                    } else {
                        alert(response.data);
                    }
                },
                error: function() {
                    alert('<?php _e('An error occurred. Please try again.', 'ai-chat-assistant'); ?>');
                },
                complete: function() {
                    submitBtn.prop('disabled', false);
                    submitBtn.val('<?php _e('Send Invitation', 'ai-chat-assistant'); ?>');
                }
            });
        });
        
        // Change role and remove member handlers (delegated)
        $(document).on('click', '.change-role-btn', function(e) {
            e.preventDefault();
            
            var roomId = $(this).data('room-id');
            var userId = $(this).data('user-id');
            var newRole = $(this).data('role');
            var row = $(this).closest('tr');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ai_chat_manage_member',
                    room_action: 'change_role',
                    room_id: roomId,
                    user_id: userId,
                    role: newRole,
                    nonce: '<?php echo wp_create_nonce('ai_chat_admin_nonce'); ?>'
                },
                beforeSend: function() {
                    row.css('opacity', '0.5');
                },
                success: function(response) {
                    if (response.success) {
                        // Refresh member list
                        $('.view-members-btn[data-room-id="' + roomId + '"]').trigger('click');
                    } else {
                        row.css('opacity', '1');
                        alert(response.data);
                    }
                },
                error: function() {
                    row.css('opacity', '1');
                    alert('<?php _e('An error occurred. Please try again.', 'ai-chat-assistant'); ?>');
                }
            });
        });
        
        $(document).on('click', '.remove-member-btn', function(e) {
            e.preventDefault();
            
            if (!confirm('<?php _e('Are you sure you want to remove this member from the chat room?', 'ai-chat-assistant'); ?>')) {
                return;
            }
            
            var roomId = $(this).data('room-id');
            var userId = $(this).data('user-id');
            var row = $(this).closest('tr');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'ai_chat_manage_member',
                    room_action: 'remove_member',
                    room_id: roomId,
                    user_id: userId,
                    nonce: '<?php echo wp_create_nonce('ai_chat_admin_nonce'); ?>'
                },
                beforeSend: function() {
                    row.css('opacity', '0.5');
                },
                success: function(response) {
                    if (response.success) {
                        row.fadeOut(400, function() {
                            row.remove();
                            
                            // Show empty message if no members left
                            if ($('#members-list tr').length === 0) {
                                $('#members-list').html('<tr><td colspan="5"><?php _e('No members found.', 'ai-chat-assistant'); ?></td></tr>');
                            }
                        });
                    } else {
                        row.css('opacity', '1');
                        alert(response.data);
                    }
                },
                error: function() {
                    row.css('opacity', '1');
                    alert('<?php _e('An error occurred. Please try again.', 'ai-chat-assistant'); ?>');
                }
            });
        });
    });
</script>