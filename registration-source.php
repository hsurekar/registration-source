<?php
/*
Plugin Name: Registration Source
Plugin URI: https://github.com/hsurekar/registration-source
Description: Track and analyze user registration sources in WordPress
Version: 1.1.0
Requires at least: 5.0
Requires PHP: 7.0
Author: hsurekar
Author URI: https://github.com/hsurekar/
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: registration-source
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('REGISTRATION_SOURCE_VERSION', '1.1.0');
define('REGISTRATION_SOURCE_PLUGIN_FILE', __FILE__);
define('REGISTRATION_SOURCE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('REGISTRATION_SOURCE_PLUGIN_URL', plugin_dir_url(__FILE__));

// Composer autoloader
if (file_exists(REGISTRATION_SOURCE_PLUGIN_DIR . 'vendor/autoload.php')) {
    require_once REGISTRATION_SOURCE_PLUGIN_DIR . 'vendor/autoload.php';
}

// Manual autoloader fallback
spl_autoload_register(function ($class) {
    $prefix = 'RegistrationSource\\';
    $base_dir = REGISTRATION_SOURCE_PLUGIN_DIR . 'src/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Initialize the plugin
add_action('plugins_loaded', function() {
    \RegistrationSource\Core\Plugin::get_instance();
});

// Register activation hook
register_activation_hook(__FILE__, function() {
    if (!version_compare(PHP_VERSION, '7.0', '>=')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(
            esc_html__('Registration Source requires PHP 7.0 or higher.', 'registration-source'),
            'Plugin Activation Error',
            ['back_link' => true]
        );
    }
    
    // Create database tables and set default options
    \RegistrationSource\Core\Plugin::get_instance()->activate();
});

// Register deactivation hook
register_deactivation_hook(__FILE__, function() {
    \RegistrationSource\Core\Plugin::get_instance()->deactivate();
});

// Register uninstall hook
register_uninstall_hook(__FILE__, function() {
    // Only delete data if the setting is enabled
    $settings = get_option('registration_source_settings', []);
    if (!empty($settings['purge_data_on_uninstall'])) {
        global $wpdb;
        
        // Delete plugin options
        delete_option('registration_source_version');
        delete_option('registration_source_settings');
        
        // Delete user meta
        delete_metadata('user', 0, 'registration_source', '', true);
        
        // Drop custom tables
        $table = $wpdb->prefix . 'registration_source_stats';
        $wpdb->query( "DROP TABLE IF EXISTS `{$table}`" );
    }
}); 