<?php
/**
 * Plugin Name: User Tags
 * Description: Adds a "User Tags" taxonomy to users.
 * Version: 1.3
 * Author: Your Name
 */

// Prevent direct access
if (!defined('ABSPATH')) exit;

// Register 'User Tags' Taxonomy for Users
function register_user_tags_taxonomy() {
    register_taxonomy(
        'user_tag',
        'user',
        array(
            'public'            => true,
            'show_ui'           => true,
            'show_in_menu'      => false, // Hide default UI
            'show_admin_column' => true,
            'hierarchical'      => false,
            'labels'            => array(
                'name'          => 'User Tags',
                'singular_name' => 'User Tag',
                'menu_name'     => 'User Tags',
                'all_items'     => 'All User Tags',
                'edit_item'     => 'Edit User Tag',
                'view_item'     => 'View User Tag',
                'update_item'   => 'Update User Tag',
                'add_new_item'  => 'Add New User Tag',
                'new_item_name' => 'New User Tag Name',
                'search_items'  => 'Search User Tags',
            ),
            'rewrite'           => false,
            'capabilities'      => array(
                'manage_terms' => 'edit_users',
                'edit_terms'   => 'edit_users',
                'delete_terms' => 'edit_users',
                'assign_terms' => 'edit_users',
            ),
        )
    );
}
add_action('init', 'register_user_tags_taxonomy');

// Add 'User Tags' Admin Menu under Users
function add_user_tags_admin_menu() {
    add_users_page(
        'User Tags', 
        'User Tags', 
        'edit_users', 
        'edit-tags.php?taxonomy=user_tag'
    );
}
add_action('admin_menu', 'add_user_tags_admin_menu');

// Swap Position of Filter by User Tags Dropdown and Change Button
function add_user_tags_filter() {
    $screen = get_current_screen();
    if ($screen->id !== 'users') return; // Ensure it's only on the Users page

    $tags = get_terms(array('taxonomy' => 'user_tag', 'hide_empty' => false));
    if (!$tags || is_wp_error($tags)) {
        return;
    }

    $selected_tag = isset($_GET['user_tag_filter']) ? sanitize_text_field($_GET['user_tag_filter']) : '';

    echo '<input type="submit" class="button" value="Change">'; // Move Change button before dropdown
    echo '<label for="user_tag_filter" class="screen-reader-text">Filter by User Tags</label>';
    echo '<select name="user_tag_filter" id="user_tag_filter">';
    echo '<option value="">Filter by User Tags...</option>';

    foreach ($tags as $tag) {
        echo '<option value="' . esc_attr($tag->slug) . '" ' . selected($selected_tag, $tag->slug, false) . '>' . esc_html($tag->name) . '</option>';
    }

    echo '</select>';
}
add_action('restrict_manage_users', 'add_user_tags_filter');

// Filter Users by Selected User Tag
function filter_users_by_tag($query) {
    global $pagenow;

    if (is_admin() && $pagenow === 'users.php' && !empty($_GET['user_tag_filter'])) {
        $tag_slug = sanitize_text_field($_GET['user_tag_filter']);
        
        $tag = get_term_by('slug', $tag_slug, 'user_tag');
        if ($tag) {
            $user_ids = get_objects_in_term($tag->term_id, 'user_tag');
            
            if (!empty($user_ids)) {
                $query->set('include', $user_ids);
            } else {
                $query->set('include', array(0));
            }
        }
    }
}
add_filter('pre_get_users', 'filter_users_by_tag');
