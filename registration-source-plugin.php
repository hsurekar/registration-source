<?php
/*
Plugin Name: Registration Source Plugin
Description: Captures and displays the registration source for users.
Version: 1.0
Author: Your Name
*/

// Add registration source field in registration form
function add_registration_source_field() {
    // No need to add hidden field in the registration form
}
add_action('register_form', 'add_registration_source_field');

// Save registration source
function save_registration_source($user_id, $source = '') {
    if (empty($source)) {
        $source = 'native'; // Default to native registration form
    }

    update_user_meta($user_id, 'registration_source', $source);
}
add_action('user_register', 'save_registration_source', 10, 2);

// Handle XML-RPC registration
function handle_xmlrpc_registration($method) {
    if ($method === 'wp.register') {
        $source = 'xml-rpc';
        add_action('user_register', function($user_id) use ($source) {
            save_registration_source($user_id, $source);
        });
    }
}
add_action('xmlrpc_call', 'handle_xmlrpc_registration');

// Handle REST API registration
function handle_rest_api_registration($user, $request, $creating) {
    if ($creating) {
        $source = 'rest-api';
        save_registration_source($user->ID, $source);
    }
    return $user;
}
add_filter('rest_insert_user', 'handle_rest_api_registration', 10, 3);

// Display registration source in user list
function add_registration_source_column($columns) {
    $columns['registration_source'] = 'Registration Source';
    return $columns;
}
add_filter('manage_users_columns', 'add_registration_source_column');

function display_registration_source_column($value, $column_name, $user_id) {
    if ($column_name === 'registration_source') {
        return get_registration_source($user_id);
    }
    return $value;
}
add_filter('manage_users_custom_column', 'display_registration_source_column', 10, 3);

// Get registration source
function get_registration_source($user_id) {
    return get_user_meta($user_id, 'registration_source', true);
}
