<?php

namespace RegistrationSource\Tests\Core;

use RegistrationSource\Core\Plugin;
use WP_UnitTestCase;

class PluginTest extends WP_UnitTestCase {
    private $plugin;
    
    public function setUp() {
        parent::setUp();
        $this->plugin = Plugin::get_instance();
    }
    
    public function test_singleton_instance() {
        $instance1 = Plugin::get_instance();
        $instance2 = Plugin::get_instance();
        
        $this->assertSame($instance1, $instance2);
    }
    
    public function test_get_allowed_sources() {
        $sources = $this->plugin->get_allowed_sources();
        
        $this->assertIsArray($sources);
        $this->assertArrayHasKey('native', $sources);
        $this->assertArrayHasKey('xml-rpc', $sources);
        $this->assertArrayHasKey('rest-api', $sources);
    }
    
    public function test_save_registration_source() {
        $user_id = $this->factory->user->create();
        
        $this->plugin->save_registration_source($user_id, 'native');
        
        $source = get_user_meta($user_id, 'registration_source', true);
        $this->assertEquals('native', $source);
    }
    
    public function test_save_registration_source_with_invalid_source() {
        $user_id = $this->factory->user->create();
        
        $this->plugin->save_registration_source($user_id, 'invalid_source');
        
        $source = get_user_meta($user_id, 'registration_source', true);
        $this->assertEmpty($source);
    }
    
    public function test_get_registration_source() {
        $user_id = $this->factory->user->create();
        update_user_meta($user_id, 'registration_source', 'native');
        
        $source = $this->plugin->get_registration_source($user_id);
        
        $this->assertEquals('native', $source);
    }
    
    public function test_get_registration_source_default() {
        $user_id = $this->factory->user->create();
        
        $source = $this->plugin->get_registration_source($user_id);
        
        $this->assertEquals('native', $source);
    }
    
    public function test_activation() {
        global $wpdb;
        
        $this->plugin->activate();
        
        // Check if the stats table exists
        $table_name = $wpdb->prefix . 'registration_source_stats';
        $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name;
        
        $this->assertTrue($table_exists);
        
        // Check if version option is set
        $version = get_option('registration_source_version');
        $this->assertNotEmpty($version);
        
        // Check if settings are initialized
        $settings = get_option('registration_source_settings');
        $this->assertIsArray($settings);
        $this->assertArrayHasKey('track_registration_time', $settings);
        $this->assertArrayHasKey('enable_statistics', $settings);
    }
    
    public function test_filter_allowed_sources() {
        add_filter('registration_source_allowed_sources', function($sources) {
            $sources['custom'] = 'Custom Source';
            return $sources;
        });
        
        $sources = $this->plugin->get_allowed_sources();
        
        $this->assertArrayHasKey('custom', $sources);
        $this->assertEquals('Custom Source', $sources['custom']);
    }
    
    public function test_action_registration_source_saved() {
        $user_id = $this->factory->user->create();
        $source = 'native';
        $called = false;
        
        add_action('registration_source_saved', function($saved_user_id, $saved_source) use ($user_id, $source, &$called) {
            $this->assertEquals($user_id, $saved_user_id);
            $this->assertEquals($source, $saved_source);
            $called = true;
        }, 10, 2);
        
        $this->plugin->save_registration_source($user_id, $source);
        
        $this->assertTrue($called);
    }
    
    public function tearDown() {
        parent::tearDown();
    }
} 