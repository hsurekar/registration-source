<?php

namespace RegistrationSource\Core;

use RegistrationSource\Admin\Admin;
use RegistrationSource\Api\RestApi;

class Plugin {
    private static $instance = null;
    private $version = '1.1.0';
    private $default_source = 'native';
    private $admin;
    private $rest_api;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->init_components();
        $this->register_hooks();
    }
    
    private function init_components() {
        $this->admin = new Admin($this);
        $this->rest_api = new RestApi($this);
    }
    
    private function register_hooks() {
        add_action('init', [$this, 'init']);
        add_action('register_form', [$this, 'regsource_add_registration_source_field']);
        add_action('user_register', [$this, 'regsource_save_registration_source'], 10, 2);
        add_action('xmlrpc_call', [$this, 'handle_xmlrpc_registration']);
        
        // Register activation and deactivation hooks
        register_activation_hook(REGISTRATION_SOURCE_PLUGIN_FILE, [$this, 'activate']);
        register_deactivation_hook(REGISTRATION_SOURCE_PLUGIN_FILE, [$this, 'deactivate']);
    }
    
    public function init() {
        do_action('registration_source_init', $this);
    }
    
    public function regsource_add_registration_source_field() {
        wp_nonce_field('registration_source_nonce', 'registration_source_nonce');
        echo '<input type="hidden" name="registration_source" value="' . esc_attr($this->default_source) . '" />';
    }
    
    public function regsource_save_registration_source($user_id, $source = '') {
        if (!$this->validate_registration_request($user_id)) {
            return;
        }
        
        $source = $this->sanitize_source($source);
        if ($source) {
            update_user_meta($user_id, 'registration_source', $source);
            do_action('registration_source_saved', $user_id, $source);
        }
    }
    
    private function validate_registration_request($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }
        
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return true;
        }
        
        if (!isset($_POST['registration_source_nonce']) || 
            !wp_verify_nonce($_POST['registration_source_nonce'], 'registration_source_nonce')) {
            return false;
        }
        
        return true;
    }
    
    private function sanitize_source($source) {
        $source = sanitize_text_field($source ?: $this->default_source);
        $allowed_sources = $this->regsource_get_allowed_sources();
        
        return isset($allowed_sources[$source]) ? $source : null;
    }
    
    public function handle_xmlrpc_registration($method) {
        if ($method === 'wp.register') {
            $source = 'xml-rpc';
            add_action('user_register', function($user_id) use ($source) {
                $this->regsource_save_registration_source($user_id, $source);
            });
        }
    }
    
    public function regsource_get_allowed_sources() {
        $default_sources = [
            'native' => __('Native Registration', 'registration-source'),
            'xml-rpc' => __('XML-RPC', 'registration-source'),
            'rest-api' => __('REST API', 'registration-source'),
            'woocommerce' => __('WooCommerce', 'registration-source'),
            'membership' => __('Membership Plugin', 'registration-source'),
        ];
        
        return (array) apply_filters('registration_source_allowed_sources', $default_sources);
    }
    
    public function regsource_get_registration_source($user_id) {
        return get_user_meta($user_id, 'registration_source', true) ?: $this->default_source;
    }
    
    public function regsource_get_version() {
        return $this->version;
    }
    
    public function activate() {
        if (!get_option('registration_source_version')) {
            $this->create_tables();
            $this->set_default_options();
        }
        
        update_option('registration_source_version', $this->version);
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    private function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'registration_source_stats';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            source varchar(50) NOT NULL,
            count bigint(20) NOT NULL DEFAULT 0,
            last_registration datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY source (source)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    private function set_default_options() {
        $default_options = [
            'track_registration_time' => true,
            'enable_statistics' => true,
            'purge_data_on_uninstall' => false,
        ];
        
        update_option('registration_source_settings', $default_options);
    }
} 