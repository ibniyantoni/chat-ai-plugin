<?php
/**
 * Define the internationalization functionality.
 *
 * @package AI_Chat_Assistant
 * @subpackage AI_Chat_Assistant/includes
 */
class AI_Chat_i18n {

    /**
     * Load the plugin text domain untuk translation.
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'ai-chat-assistant',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }
}