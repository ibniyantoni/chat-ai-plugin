<?php
/**
 * Register custom post types dan taxonomies.
 *
 * @package AI_Chat_Assistant
 * @subpackage AI_Chat_Assistant/includes
 */
class AI_Chat_Post_Types {

    /**
     * Register custom post types.
     */
    public function register_post_types() {
        // Register 'ai_chat_topic' post type for Main Topics
        $labels = array(
            'name'                  => _x('AI Chat Topics', 'Post Type General Name', 'ai-chat-assistant'),
            'singular_name'         => _x('AI Chat Topic', 'Post Type Singular Name', 'ai-chat-assistant'),
            'menu_name'             => __('AI Chat Topics', 'ai-chat-assistant'),
            'name_admin_bar'        => __('AI Chat Topic', 'ai-chat-assistant'),
            'archives'              => __('Topic Archives', 'ai-chat-assistant'),
            'attributes'            => __('Topic Attributes', 'ai-chat-assistant'),
            'parent_item_colon'     => __('Parent Topic:', 'ai-chat-assistant'),
            'all_items'             => __('All Topics', 'ai-chat-assistant'),
            'add_new_item'          => __('Add New Topic', 'ai-chat-assistant'),
            'add_new'               => __('Add New', 'ai-chat-assistant'),
            'new_item'              => __('New Topic', 'ai-chat-assistant'),
            'edit_item'             => __('Edit Topic', 'ai-chat-assistant'),
            'update_item'           => __('Update Topic', 'ai-chat-assistant'),
            'view_item'             => __('View Topic', 'ai-chat-assistant'),
            'view_items'            => __('View Topics', 'ai-chat-assistant'),
            'search_items'          => __('Search Topic', 'ai-chat-assistant'),
            'not_found'             => __('Not found', 'ai-chat-assistant'),
            'not_found_in_trash'    => __('Not found in Trash', 'ai-chat-assistant'),
            'featured_image'        => __('Featured Image', 'ai-chat-assistant'),
            'set_featured_image'    => __('Set featured image', 'ai-chat-assistant'),
            'remove_featured_image' => __('Remove featured image', 'ai-chat-assistant'),
            'use_featured_image'    => __('Use as featured image', 'ai-chat-assistant'),
            'insert_into_item'      => __('Insert into topic', 'ai-chat-assistant'),
            'uploaded_to_this_item' => __('Uploaded to this topic', 'ai-chat-assistant'),
            'items_list'            => __('Topics list', 'ai-chat-assistant'),
            'items_list_navigation' => __('Topics list navigation', 'ai-chat-assistant'),
            'filter_items_list'     => __('Filter topics list', 'ai-chat-assistant'),
        );
        
        $args = array(
            'label'                 => __('AI Chat Topic', 'ai-chat-assistant'),
            'description'           => __('AI Chat Topics for predefined conversations', 'ai-chat-assistant'),
            'labels'                => $labels,
            'supports'              => array('title', 'editor', 'thumbnail', 'custom-fields'),
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => 'ai-chat-assistant',
            'menu_position'         => 5,
            'menu_icon'             => 'dashicons-format-chat',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'post',
            'show_in_rest'          => true,
        );
        
        register_post_type('ai_chat_topic', $args);
        
        // Register 'ai_chat_prompt' post type for Prompt Basic
        $labels = array(
            'name'                  => _x('AI Chat Prompts', 'Post Type General Name', 'ai-chat-assistant'),
            'singular_name'         => _x('AI Chat Prompt', 'Post Type Singular Name', 'ai-chat-assistant'),
            'menu_name'             => __('AI Chat Prompts', 'ai-chat-assistant'),
            'name_admin_bar'        => __('AI Chat Prompt', 'ai-chat-assistant'),
            'archives'              => __('Prompt Archives', 'ai-chat-assistant'),
            'attributes'            => __('Prompt Attributes', 'ai-chat-assistant'),
            'parent_item_colon'     => __('Parent Prompt:', 'ai-chat-assistant'),
            'all_items'             => __('All Prompts', 'ai-chat-assistant'),
            'add_new_item'          => __('Add New Prompt', 'ai-chat-assistant'),
            'add_new'               => __('Add New', 'ai-chat-assistant'),
            'new_item'              => __('New Prompt', 'ai-chat-assistant'),
            'edit_item'             => __('Edit Prompt', 'ai-chat-assistant'),
            'update_item'           => __('Update Prompt', 'ai-chat-assistant'),
            'view_item'             => __('View Prompt', 'ai-chat-assistant'),
            'view_items'            => __('View Prompts', 'ai-chat-assistant'),
            'search_items'          => __('Search Prompt', 'ai-chat-assistant'),
            'not_found'             => __('Not found', 'ai-chat-assistant'),
            'not_found_in_trash'    => __('Not found in Trash', 'ai-chat-assistant'),
            'featured_image'        => __('Featured Image', 'ai-chat-assistant'),
            'set_featured_image'    => __('Set featured image', 'ai-chat-assistant'),
            'remove_featured_image' => __('Remove featured image', 'ai-chat-assistant'),
            'use_featured_image'    => __('Use as featured image', 'ai-chat-assistant'),
            'insert_into_item'      => __('Insert into prompt', 'ai-chat-assistant'),
            'uploaded_to_this_item' => __('Uploaded to this prompt', 'ai-chat-assistant'),
            'items_list'            => __('Prompts list', 'ai-chat-assistant'),
            'items_list_navigation' => __('Prompts list navigation', 'ai-chat-assistant'),
            'filter_items_list'     => __('Filter prompts list', 'ai-chat-assistant'),
        );
        
        $args = array(
            'label'                 => __('AI Chat Prompt', 'ai-chat-assistant'),
            'description'           => __('AI Chat Prompts for templates', 'ai-chat-assistant'),
            'labels'                => $labels,
            'supports'              => array('title', 'editor', 'custom-fields'),
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => 'ai-chat-assistant',
            'menu_position'         => 6,
            'menu_icon'             => 'dashicons-editor-help',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'post',
            'show_in_rest'          => true,
        );
        
        register_post_type('ai_chat_prompt', $args);
    }

    /**
     * Register custom taxonomies.
     */
    public function register_taxonomies() {
        // Register Taxonomy for AI Chat Topic Categories
        $labels = array(
            'name'                       => _x('Topic Categories', 'Taxonomy General Name', 'ai-chat-assistant'),
            'singular_name'              => _x('Topic Category', 'Taxonomy Singular Name', 'ai-chat-assistant'),
            'menu_name'                  => __('Topic Categories', 'ai-chat-assistant'),
            'all_items'                  => __('All Topic Categories', 'ai-chat-assistant'),
            'parent_item'                => __('Parent Topic Category', 'ai-chat-assistant'),
            'parent_item_colon'          => __('Parent Topic Category:', 'ai-chat-assistant'),
            'new_item_name'              => __('New Topic Category Name', 'ai-chat-assistant'),
            'add_new_item'               => __('Add New Topic Category', 'ai-chat-assistant'),
            'edit_item'                  => __('Edit Topic Category', 'ai-chat-assistant'),
            'update_item'                => __('Update Topic Category', 'ai-chat-assistant'),
            'view_item'                  => __('View Topic Category', 'ai-chat-assistant'),
            'separate_items_with_commas' => __('Separate categories with commas', 'ai-chat-assistant'),
            'add_or_remove_items'        => __('Add or remove categories', 'ai-chat-assistant'),
            'choose_from_most_used'      => __('Choose from the most used', 'ai-chat-assistant'),
            'popular_items'              => __('Popular Topic Categories', 'ai-chat-assistant'),
            'search_items'               => __('Search Topic Categories', 'ai-chat-assistant'),
            'not_found'                  => __('Not Found', 'ai-chat-assistant'),
            'no_terms'                   => __('No categories', 'ai-chat-assistant'),
            'items_list'                 => __('Topic Categories list', 'ai-chat-assistant'),
            'items_list_navigation'      => __('Topic Categories list navigation', 'ai-chat-assistant'),
        );
        
        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => true,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => true,
            'show_tagcloud'              => true,
            'show_in_rest'               => true,
        );
        
        register_taxonomy('ai_chat_topic_category', array('ai_chat_topic'), $args);
    }
}