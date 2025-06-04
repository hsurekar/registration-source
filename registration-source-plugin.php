<?php
/*
Plugin Name: Registration Source Plugin
Description: Captures and displays the registration source for users.
Version: 1.1
Author: Your Name
Text Domain: registration-source
Domain Path: /languages
*/

namespace RegistrationSource;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class RegistrationSourcePlugin {
    private static $instance = null;
    private $default_source = 'native';
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', [$this, 'init']);
        add_action('plugins_loaded', [$this, 'load_textdomain']);
    }
    
    public function init() {
        // Registration hooks
        add_action('register_form', [$this, 'add_registration_source_field']);
        add_action('user_register', [$this, 'save_registration_source'], 10, 2);
        add_action('xmlrpc_call', [$this, 'handle_xmlrpc_registration']);
        add_filter('rest_insert_user', [$this, 'handle_rest_api_registration'], 10, 3);
        
        // Admin hooks
        if (is_admin()) {
            add_filter('manage_users_columns', [$this, 'add_registration_source_column']);
            add_filter('manage_users_custom_column', [$this, 'display_registration_source_column'], 10, 3);
            add_action('admin_menu', [$this, 'add_settings_page']);
            add_action('admin_init', [$this, 'register_settings']);
        }
        
        // Add support for third-party registration sources
        do_action('registration_source_init', $this);
    }
    
    public function load_textdomain() {
        load_plugin_textdomain('registration-source', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    public function add_registration_source_field() {
        wp_nonce_field('registration_source_nonce', 'registration_source_nonce');
        echo '<input type="hidden" name="registration_source" value="' . esc_attr($this->default_source) . '" />';
    }
    
    public function save_registration_source($user_id, $source = '') {
        if (!current_user_can('edit_user', $user_id) && !isset($_POST['registration_source_nonce']) || !wp_verify_nonce($_POST['registration_source_nonce'], 'registration_source_nonce')) {
            return;
        }
        
        $source = sanitize_text_field($source ?: $this->default_source);
        $allowed_sources = $this->get_allowed_sources();
        
        if (in_array($source, $allowed_sources, true)) {
            update_user_meta($user_id, 'registration_source', $source);
        }
    }
    
    public function handle_xmlrpc_registration($method) {
        if ($method === 'wp.register') {
            $source = 'xml-rpc';
            add_action('user_register', function($user_id) use ($source) {
                $this->save_registration_source($user_id, $source);
            });
        }
    }
    
    public function handle_rest_api_registration($user, $request, $creating) {
        if ($creating) {
            $this->save_registration_source($user->ID, 'rest-api');
        }
        return $user;
    }
    
    public function add_registration_source_column($columns) {
        if (current_user_can('manage_options')) {
            $columns['registration_source'] = __('Registration Source', 'registration-source');
        }
        return $columns;
    }
    
    public function display_registration_source_column($value, $column_name, $user_id) {
        if ($column_name === 'registration_source' && current_user_can('manage_options')) {
            $source = $this->get_registration_source($user_id);
            return esc_html($source);
        }
        return $value;
    }
    
    public function get_registration_source($user_id) {
        return get_user_meta($user_id, 'registration_source', true) ?: $this->default_source;
    }
    
    public function get_allowed_sources() {
        $default_sources = [
            'native' => __('Native Registration', 'registration-source'),
            'xml-rpc' => __('XML-RPC', 'registration-source'),
            'rest-api' => __('REST API', 'registration-source'),
        ];
        
        return apply_filters('registration_source_allowed_sources', $default_sources);
    }
    
    public function add_settings_page() {
        add_options_page(
            __('Registration Source Settings', 'registration-source'),
            __('Registration Source', 'registration-source'),
            'manage_options',
            'registration-source-settings',
            [$this, 'render_settings_page']
        );
    }
    
    public function register_settings() {
        register_setting('registration_source_options', 'registration_source_settings');
        
        add_settings_section(
            'registration_source_main',
            __('Main Settings', 'registration-source'),
            [$this, 'settings_section_callback'],
            'registration-source-settings'
        );
    }
    
    public function settings_section_callback() {
        echo '<p>' . esc_html__('Configure the Registration Source Plugin settings below.', 'registration-source') . '</p>';
    }
    
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('registration_source_options');
                do_settings_sections('registration-source-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
}

// Initialize the plugin
add_action('plugins_loaded', function() {
    RegistrationSourcePlugin::get_instance();
});

// Activation hook
register_activation_hook(__FILE__, function() {
    // Add activation tasks here
    flush_rewrite_rules();
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    // Add cleanup tasks here
    flush_rewrite_rules();
});
