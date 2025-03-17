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

// Add User Tags Filter to Users Page
function add_user_tags_filter() {
    $screen = get_current_screen();
    if ($screen->id !== 'users') return; // Ensure it's only on the Users page

    $tags = get_terms(array('taxonomy' => 'user_tag', 'hide_empty' => false));
    if (!$tags || is_wp_error($tags)) return;

    $selected_tag = isset($_GET['user_tag_filter']) ? sanitize_text_field($_GET['user_tag_filter']) : '';
    echo '<div style="display: inline-block; margin-left: 10px;">';
    echo '<form method="GET" action="' . esc_url(admin_url('users.php')) . '">';
    echo '<input type="hidden" name="s" value="">'; // Preserve search functionality
    //echo '<label for="user_tag_filter">Filter by User Tags</label>';
    echo '<select name="user_tag_filter" id="user_tag_filter">';
    echo '<option value="">Filter by User Tags...</option>';

    foreach ($tags as $tag) {
        echo '<option value="' . esc_attr($tag->slug) . '" ' . selected($selected_tag, $tag->slug, false) . '>' . esc_html($tag->name) . '</option>';
    }

    echo '</select>';
    echo '<input type="submit" class="button" value="Filter">';
    echo '</form>';
    echo '</div>';
}

add_action('restrict_manage_users', 'add_user_tags_filter');
// Add User Tags Filter Dropdown to Users Page
/*
function add_user_tags_filter() {
    $screen = get_current_screen();
    if ($screen->id !== 'users') return; // Ensure it's only on the Users page

    $tags = get_terms(array('taxonomy' => 'user_tag', 'hide_empty' => false));
    if (!$tags || is_wp_error($tags)) {
        return;
    }

    $selected_tag = isset($_GET['user_tag_filter']) ? sanitize_text_field($_GET['user_tag_filter']) : '';

    echo '<div style="display: inline-block; margin-left: 10px;">';
    echo '<label for="user_tag_filter" class="screen-reader-text">Filter by User Tags</label>';
    echo '<select name="user_tag_filter" id="user_tag_filter">';
    echo '<option value="">Filter by User Tags...</option>';

    foreach ($tags as $tag) {
        echo '<option value="' . esc_attr($tag->slug) . '" ' . selected($selected_tag, $tag->slug, false) . '>' . esc_html($tag->name) . '</option>';
    }

    echo '</select>';
    echo '<input type="submit" class="button" value="Filter">';
    echo '</div>';
}

add_action('restrict_manage_users', 'add_user_tags_filter'); */

/*

function add_user_tags_filter() {
    $tags = get_terms(array('taxonomy' => 'user_tag', 'hide_empty' => false));
    if (!$tags || is_wp_error($tags)) {
        return;
    }
    $selected_tag = isset($_GET['user_tag_filter']) ? sanitize_text_field($_GET['user_tag_filter']) : '';
    echo '<label for="user_tag_filter" class="screen-reader-text">Filter by user tags</label>';
    echo '<select name="user_tag_filter" id="user_tag_filter">
            <option value="">Filter by user tags...</option>';
    foreach ($tags as $tag) {
        echo '<option value="' . esc_attr($tag->slug) . '" ' . selected($selected_tag, $tag->slug, false) . '>' . esc_html($tag->name) . '</option>';
    }
    echo '</select>';
    echo '<input type="submit" class="button" value="Filter">';
}
add_action('restrict_manage_users', 'add_user_tags_filter');*/

// Filter Users by Tag

// Filter Users by Selected User Tag
function filter_users_by_tag($query) {
    global $pagenow;

    if (is_admin() && $pagenow === 'users.php' && !empty($_GET['user_tag_filter'])) {
        $tag_slug = sanitize_text_field($_GET['user_tag_filter']);

        // Get the term ID from the slug
        $tag = get_term_by('slug', $tag_slug, 'user_tag');
        if ($tag) {
            $user_ids = get_objects_in_term($tag->term_id, 'user_tag');

            if (!empty($user_ids)) {
                $query->set('include', $user_ids);
            } else {
                $query->set('include', array(0)); // Show no users if no matches
            }
        }
    }
}
add_filter('pre_get_users', 'filter_users_by_tag');





// Add User Tags to User Profile
function add_user_tags_field($user) {
    $user_tags = is_object($user) ? wp_get_object_terms($user->ID, 'user_tag', array('fields' => 'names')) : [];
    ?>
    <h3>User Tags</h3>
    <table class="form-table">
        <tr>
            <th><label for="user_tags">Tags</label></th>
            <td>
                <select name="user_tags[]" id="user_tags" multiple="multiple" style="width: 100%;">
                    <?php
                    $tags = get_terms(array('taxonomy' => 'user_tag', 'hide_empty' => false));
                    foreach ($tags as $tag) {
                        $selected = in_array($tag->name, $user_tags) ? 'selected' : '';
                        echo '<option value="' . esc_attr($tag->name) . '" ' . $selected . '>' . esc_html($tag->name) . '</option>';
                    }
                    ?>
                </select>
                <p class="description">Select or add new user tags</p>
            </td>
        </tr>
    </table>
    <?php
}
add_action('show_user_profile', 'add_user_tags_field');
add_action('edit_user_profile', 'add_user_tags_field');
add_action('user_new_form', 'add_user_tags_field');

// Save User Tags on Profile Update
function save_user_tags($user_id) {
    if (!current_user_can('edit_user', $user_id)) return false;

    if (!empty($_POST['user_tags'])) {
        $tag_names = array_map('sanitize_text_field', $_POST['user_tags']);
        $tag_slugs = [];

        foreach ($tag_names as $tag_name) {
            $existing_term = get_term_by('name', $tag_name, 'user_tag');
            if ($existing_term) {
                $tag_slugs[] = $existing_term->term_id;
            } else {
                $new_term = wp_insert_term($tag_name, 'user_tag');
                if (!is_wp_error($new_term) && isset($new_term['term_id'])) {
                    $tag_slugs[] = $new_term['term_id'];
                }
            }
        }
        wp_set_object_terms($user_id, $tag_slugs, 'user_tag');
    } else {
        wp_set_object_terms($user_id, array(), 'user_tag');
    }
}
add_action('personal_options_update', 'save_user_tags');
add_action('edit_user_profile_update', 'save_user_tags');
add_action('user_register', 'save_user_tags');

// Enqueue Select2 for Better User Tag Selection UI
function enqueue_select2() {
    wp_enqueue_script('select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js', ['jquery'], null, true);
    wp_enqueue_style('select2-css', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css');
    ?>
    <script>
        jQuery(document).ready(function ($) {
            $('#user_tags').select2({
                tags: true,
                tokenSeparators: [','],
                placeholder: "Select or add tags",
                allowClear: true
            });
        });
    </script>
    <?php
}
add_action('admin_footer', 'enqueue_select2');
