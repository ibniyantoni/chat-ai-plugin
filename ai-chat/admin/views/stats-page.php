<?php
/**
 * Template untuk halaman statistik user.
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
        <p><?php _e('View usage statistics for the AI Chat Assistant plugin.', 'ai-chat-assistant'); ?></p>
    </div>
    
    <div class="stats-container">
        <div class="stats-row">
            <div class="stats-box">
                <h2><?php _e('AI Chat Statistics', 'ai-chat-assistant'); ?></h2>
                <div class="stats-grid">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo intval($ai_stats['total_conversations']); ?></span>
                        <span class="stat-label"><?php _e('Total Conversations', 'ai-chat-assistant'); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo intval($ai_stats['total_messages']); ?></span>
                        <span class="stat-label"><?php _e('Total Messages', 'ai-chat-assistant'); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo intval($ai_stats['active_users']); ?></span>
                        <span class="stat-label"><?php _e('Active Users', 'ai-chat-assistant'); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo intval($ai_stats['recent_conversations']); ?></span>
                        <span class="stat-label"><?php _e('New Conversations (7 days)', 'ai-chat-assistant'); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="stats-box">
                <h2><?php _e('Group Chat Statistics', 'ai-chat-assistant'); ?></h2>
                <div class="stats-grid">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo intval($group_stats['total_rooms']); ?></span>
                        <span class="stat-label"><?php _e('Total Rooms', 'ai-chat-assistant'); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo intval($group_stats['total_messages']); ?></span>
                        <span class="stat-label"><?php _e('Total Messages', 'ai-chat-assistant'); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo intval($group_stats['active_users']); ?></span>
                        <span class="stat-label"><?php _e('Active Users', 'ai-chat-assistant'); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo intval($group_stats['recent_messages']); ?></span>
                        <span class="stat-label"><?php _e('New Messages (7 days)', 'ai-chat-assistant'); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="stats-row">
            <div class="stats-box">
                <h2><?php _e('User-to-User Chat Statistics', 'ai-chat-assistant'); ?></h2>
                <div class="stats-grid">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo intval($user_stats['total_conversations']); ?></span>
                        <span class="stat-label"><?php _e('Total Conversations', 'ai-chat-assistant'); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo intval($user_stats['total_messages']); ?></span>
                        <span class="stat-label"><?php _e('Total Messages', 'ai-chat-assistant'); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo intval($user_stats['active_users']); ?></span>
                        <span class="stat-label"><?php _e('Active Users (7 days)', 'ai-chat-assistant'); ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number"><?php echo intval($user_stats['recent_messages']); ?></span>
                        <span class="stat-label"><?php _e('New Messages (7 days)', 'ai-chat-assistant'); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="stats-box">
                <h2><?php _e('Weekly Activity', 'ai-chat-assistant'); ?></h2>
                <div id="weekly-chart"></div>
            </div>
        </div>
        
        <div class="stats-row">
            <div class="stats-box full-width">
                <h2><?php _e('Top AI Chat Users', 'ai-chat-assistant'); ?></h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th scope="col"><?php _e('User', 'ai-chat-assistant'); ?></th>
                            <th scope="col"><?php _e('Messages Sent', 'ai-chat-assistant'); ?></th>
                            <th scope="col"><?php _e('Conversations', 'ai-chat-assistant'); ?></th>
                            <th scope="col"><?php _e('Last Activity', 'ai-chat-assistant'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($top_ai_users)) : ?>
                            <tr>
                                <td colspan="4"><?php _e('No data available.', 'ai-chat-assistant'); ?></td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ($top_ai_users as $user) : ?>
                                <tr>
                                    <td><?php echo esc_html($user['display_name']); ?></td>
                                    <td><?php echo intval($user['message_count']); ?></td>
                                    <td>
                                        <?php 
                                        global $wpdb;
                                        $conversations_table = $wpdb->prefix . 'ai_chat_conversations';
                                        $count = $wpdb->get_var($wpdb->prepare(
                                            "SELECT COUNT(*) FROM $conversations_table WHERE user_id = %d",
                                            $user['user_id']
                                        ));
                                        echo intval($count);
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $messages_table = $wpdb->prefix . 'ai_chat_messages';
                                        $last_activity = $wpdb->get_var($wpdb->prepare(
                                            "SELECT created_at FROM $messages_table WHERE user_id = %d AND is_ai = 0 ORDER BY created_at DESC LIMIT 1",
                                            $user['user_id']
                                        ));
                                        
                                        echo $last_activity ? date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_activity)) : '&mdash;';
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    .stats-container {
        margin-top: 20px;
    }
    
    .stats-row {
        display: flex;
        margin-bottom: 20px;
        gap: 20px;
    }
    
    .stats-box {
        background: #fff;
        border: 1px solid #ccd0d4;
        box-shadow: 0 1px 1px rgba(0, 0, 0, 0.04);
        padding: 15px;
        border-radius: 3px;
        flex: 1;
    }
    
    .stats-box.full-width {
        width: 100%;
    }
    
    .stats-box h2 {
        margin-top: 0;
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
        margin-top: 15px;
    }
    
    .stat-item {
        text-align: center;
        padding: 15px;
        background: #f9f9f9;
        border-radius: 3px;
    }
    
    .stat-number {
        display: block;
        font-size: 24px;
        font-weight: bold;
        color: #2271b1;
        margin-bottom: 5px;
    }
    
    .stat-label {
        display: block;
        font-size: 14px;
        color: #50575e;
    }
    
    #weekly-chart {
        height: 250px;
        margin-top: 15px;
    }
    
    @media screen and (max-width: 782px) {
        .stats-row {
            flex-direction: column;
        }
        
        .stats-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<script>
    jQuery(document).ready(function($) {
        // Get data for weekly chart
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'ai_chat_get_weekly_stats',
                nonce: '<?php echo wp_create_nonce('ai_chat_admin_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    drawWeeklyChart(response.data);
                } else {
                    $('#weekly-chart').html('<p class="error">' + response.data + '</p>');
                }
            },
            error: function() {
                $('#weekly-chart').html('<p class="error"><?php _e('Failed to load chart data.', 'ai-chat-assistant'); ?></p>');
            }
        });
        
        function drawWeeklyChart(data) {
            // This is a placeholder. In a real implementation, you would use a charting library
            // like Chart.js to visualize the data.
            
            var html = '<div class="chart-placeholder">';
            html += '<p><?php _e('Weekly activity chart would be displayed here using a JavaScript charting library.', 'ai-chat-assistant'); ?></p>';
            html += '<p><?php _e('For implementation, add Chart.js or another charting library and use the data to populate the chart.', 'ai-chat-assistant'); ?></p>';
            html += '</div>';
            
            $('#weekly-chart').html(html);
        }
    });
</script>