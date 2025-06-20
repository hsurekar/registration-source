<?php

namespace RegistrationSource\Api;

use RegistrationSource\Core\Plugin;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

class RestApi {
    private $plugin;
    private $namespace = 'registration-source/v1';
    
    public function __construct(Plugin $plugin) {
        $this->plugin = $plugin;
        $this->init();
    }
    
    private function init() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }
    
    public function register_routes() {
        // Register new user
        register_rest_route($this->namespace, '/register', [
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => [$this, 'register_user'],
            'permission_callback' => '__return_true',
            'args' => [
                'username' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_user',
                ],
                'email' => [
                    'required' => true,
                    'type' => 'string',
                    'format' => 'email',
                    'sanitize_callback' => 'sanitize_email',
                ],
                'password' => [
                    'required' => true,
                    'type' => 'string',
                ],
                'first_name' => [
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'last_name' => [
                    'required' => false,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);
        
        // Get registration statistics
        register_rest_route($this->namespace, '/statistics', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_statistics'],
            'permission_callback' => [$this, 'check_admin_permissions'],
        ]);
        
        // Get registration sources for specific users
        register_rest_route($this->namespace, '/users/(?P<id>[\d]+)/source', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_user_source'],
            'permission_callback' => [$this, 'check_admin_permissions'],
            'args' => [
                'id' => [
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    },
                ],
            ],
        ]);
        
        // Update registration source for a user
        register_rest_route($this->namespace, '/users/(?P<id>[\d]+)/source', [
            'methods' => WP_REST_Server::EDITABLE,
            'callback' => [$this, 'update_user_source'],
            'permission_callback' => [$this, 'check_admin_permissions'],
            'args' => [
                'id' => [
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    },
                ],
                'source' => [
                    'required' => true,
                    'type' => 'string',
                    'validate_callback' => [$this, 'validate_source'],
                ],
            ],
        ]);
        
        // Export registration data
        register_rest_route($this->namespace, '/export', [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'export_data'],
            'permission_callback' => [$this, 'check_admin_permissions'],
        ]);
    }
    
    public function check_admin_permissions() {
        return current_user_can('manage_options');
    }
    
    public function get_statistics(WP_REST_Request $request) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'registration_source_stats';
        $query = $wpdb->prepare("SELECT * FROM {$table_name} ORDER BY count DESC");
        $results = $wpdb->get_results($query, ARRAY_A);
        
        if (!$results) {
            return new WP_REST_Response([
                'statistics' => [],
                'total' => 0,
            ]);
        }
        
        $stats = [];
        $total = 0;
        
        foreach ($results as $row) {
            $source = $row['source'];
            $count = (int) $row['count'];
            $total += $count;
            
            $stats[] = [
                'source' => $source,
                'label' => isset($this->plugin->get_allowed_sources()[$source]) ? $this->plugin->get_allowed_sources()[$source] : $source,
                'count' => $count,
                'percentage' => 0, // Will be calculated below
                'last_registration' => $row['last_registration'],
            ];
        }
        
        // Calculate percentages
        if ($total > 0) {
            foreach ($stats as &$stat) {
                $stat['percentage'] = round(($stat['count'] / $total) * 100, 2);
            }
        }
        
        return new WP_REST_Response([
            'statistics' => $stats,
            'total' => $total,
        ]);
    }
    
    public function get_user_source(WP_REST_Request $request) {
        $user_id = $request->get_param('id');
        $source = $this->plugin->get_registration_source($user_id);
        
        if (!$source) {
            return new WP_Error(
                'registration_source_not_found',
                __('Registration source not found for this user.', 'registration-source'),
                ['status' => 404]
            );
        }
        
        return new WP_REST_Response([
            'source' => $source,
            'label' => isset($this->plugin->get_allowed_sources()[$source]) ? $this->plugin->get_allowed_sources()[$source] : $source,
        ]);
    }
    
    public function update_user_source(WP_REST_Request $request) {
        $user_id = $request->get_param('id');
        $source = $request->get_param('source');
        
        $this->plugin->save_registration_source($user_id, $source);
        
        return new WP_REST_Response([
            'source' => $source,
            'label' => isset($this->plugin->get_allowed_sources()[$source]) ? $this->plugin->get_allowed_sources()[$source] : $source,
        ]);
    }
    
    public function validate_source($param) {
        return array_key_exists($param, $this->plugin->get_allowed_sources());
    }
    
    public function export_data(WP_REST_Request $request) {
        global $wpdb;
        
        $data = [];
        $sources = $this->plugin->get_allowed_sources();
        
        // Get all users with their registration sources
        $users = get_users([
            'fields' => ['ID', 'user_email', 'user_registered'],
        ]);
        
        foreach ($users as $user) {
            $source = $this->plugin->get_registration_source($user->ID);
            $data[] = [
                'user_id' => $user->ID,
                'email' => $user->user_email,
                'registered_date' => $user->user_registered,
                'source' => $source,
                'source_label' => isset($sources[$source]) ? $sources[$source] : $source,
            ];
        }
        
        return new WP_REST_Response([
            'data' => $data,
            'generated_at' => current_time('mysql'),
        ]);
    }
    
    public function register_user(WP_REST_Request $request) {
        $username = $request->get_param('username');
        $email = $request->get_param('email');
        $password = $request->get_param('password');
        $first_name = $request->get_param('first_name');
        $last_name = $request->get_param('last_name');

        // Check if username exists
        if (username_exists($username)) {
            return new WP_Error(
                'registration_failed',
                __('Username already exists.', 'registration-source'),
                ['status' => 400]
            );
        }

        // Check if email exists
        if (email_exists($email)) {
            return new WP_Error(
                'registration_failed',
                __('Email already exists.', 'registration-source'),
                ['status' => 400]
            );
        }

        // Create the user
        $user_id = wp_create_user($username, $password, $email);

        if (is_wp_error($user_id)) {
            return $user_id;
        }

        // Update user meta
        if ($first_name) {
            update_user_meta($user_id, 'first_name', $first_name);
        }
        if ($last_name) {
            update_user_meta($user_id, 'last_name', $last_name);
        }

        // Set registration source
        $this->plugin->save_registration_source($user_id, 'rest-api');

        // Get the created user
        $user = get_user_by('id', $user_id);

        return new WP_REST_Response([
            'id' => $user_id,
            'username' => $user->user_login,
            'email' => $user->user_email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'registration_source' => $this->plugin->get_registration_source($user_id),
        ], 201);
    }
} 