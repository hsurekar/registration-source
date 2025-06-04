<?php

namespace RegistrationSource\Admin;

use RegistrationSource\Core\Plugin;

class Admin {
    private $plugin;
    
    public function __construct(Plugin $plugin) {
        $this->plugin = $plugin;
        $this->init();
    }
    
    private function init() {
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_menu', [$this, 'add_menu_pages']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_dashboard_setup', [$this, 'add_dashboard_widget']);
        
        // Users list columns
        add_filter('manage_users_columns', [$this, 'add_registration_source_column']);
        add_filter('manage_users_custom_column', [$this, 'display_registration_source_column'], 10, 3);
        
        // Bulk actions
        add_filter('bulk_actions-users', [$this, 'add_bulk_actions']);
        add_filter('handle_bulk_actions-users', [$this, 'handle_bulk_actions'], 10, 3);
    }
    
    public function enqueue_assets($hook) {
        if (!in_array($hook, ['users.php', 'index.php', 'settings_page_registration-source-settings'])) {
            return;
        }
        
        wp_enqueue_style(
            'registration-source-admin',
            plugins_url('assets/css/admin.css', REGISTRATION_SOURCE_PLUGIN_FILE),
            [],
            $this->plugin->get_version()
        );
        
        wp_enqueue_script(
            'registration-source-admin',
            plugins_url('assets/js/dist/admin.bundle.js', REGISTRATION_SOURCE_PLUGIN_FILE),
            ['jquery', 'wp-api'],
            $this->plugin->get_version(),
            true
        );
        
        wp_localize_script('registration-source-admin', 'registrationSourceAdmin', [
            'nonce' => wp_create_nonce('registration_source_admin'),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'strings' => [
                'confirmBulkDelete' => __('Are you sure you want to delete registration source data for selected users?', 'registration-source'),
                'error' => __('An error occurred. Please try again.', 'registration-source'),
            ],
        ]);
    }
    
    public function add_menu_pages() {
        add_options_page(
            __('Registration Source Settings', 'registration-source'),
            __('Registration Source', 'registration-source'),
            'manage_options',
            'registration-source-settings',
            [$this, 'render_settings_page']
        );
    }
    
    public function register_settings() {
        register_setting(
            'registration_source_options',
            'registration_source_settings',
            [
                'type' => 'array',
                'sanitize_callback' => [$this, 'sanitize_settings'],
            ]
        );
        
        add_settings_section(
            'registration_source_general',
            __('General Settings', 'registration-source'),
            [$this, 'render_general_section'],
            'registration-source-settings'
        );
        
        add_settings_field(
            'track_registration_time',
            __('Track Registration Time', 'registration-source'),
            [$this, 'render_checkbox_field'],
            'registration-source-settings',
            'registration_source_general',
            [
                'label_for' => 'track_registration_time',
                'description' => __('Track the date and time of user registrations', 'registration-source'),
            ]
        );
        
        add_settings_field(
            'enable_statistics',
            __('Enable Statistics', 'registration-source'),
            [$this, 'render_checkbox_field'],
            'registration-source-settings',
            'registration_source_general',
            [
                'label_for' => 'enable_statistics',
                'description' => __('Collect and display registration statistics', 'registration-source'),
            ]
        );
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
            
            <?php if ($this->get_option('enable_statistics')): ?>
            <div class="registration-source-stats">
                <h2><?php _e('Registration Statistics', 'registration-source'); ?></h2>
                <div id="registration-source-chart"></div>
                <?php $this->render_statistics_table(); ?>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    public function render_general_section() {
        echo '<p>' . esc_html__('Configure how the Registration Source plugin behaves.', 'registration-source') . '</p>';
    }
    
    public function render_checkbox_field($args) {
        $option_name = $args['label_for'];
        $description = $args['description'];
        $value = $this->get_option($option_name);
        
        ?>
        <label>
            <input type="checkbox"
                   id="<?php echo esc_attr($option_name); ?>"
                   name="registration_source_settings[<?php echo esc_attr($option_name); ?>]"
                   value="1"
                   <?php checked($value, true); ?>>
            <?php echo esc_html($description); ?>
        </label>
        <?php
    }
    
    public function sanitize_settings($input) {
        $sanitized = [];
        
        $sanitized['track_registration_time'] = isset($input['track_registration_time']);
        $sanitized['enable_statistics'] = isset($input['enable_statistics']);
        
        return $sanitized;
    }
    
    public function add_dashboard_widget() {
        if (!$this->get_option('enable_statistics')) {
            return;
        }
        
        wp_add_dashboard_widget(
            'registration_source_stats_widget',
            __('Registration Sources', 'registration-source'),
            [$this, 'render_dashboard_widget']
        );
    }
    
    public function render_dashboard_widget() {
        $stats = $this->get_registration_stats();
        if (empty($stats)) {
            echo '<p>' . esc_html__('No registration data available.', 'registration-source') . '</p>';
            return;
        }
        
        ?>
        <div class="registration-source-widget">
            <div id="registration-source-widget-chart"></div>
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php _e('Source', 'registration-source'); ?></th>
                        <th><?php _e('Count', 'registration-source'); ?></th>
                        <th><?php _e('Last Registration', 'registration-source'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stats as $source => $data): ?>
                    <tr>
                        <td><?php echo esc_html($this->get_source_label($source)); ?></td>
                        <td><?php echo esc_html($data['count']); ?></td>
                        <td><?php echo $data['last_registration'] ? esc_html(date_i18n(get_option('date_format'), strtotime($data['last_registration']))) : '—'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    private function get_registration_stats() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'registration_source_stats';
        $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY count DESC", ARRAY_A);
        
        if (!$results) {
            return [];
        }
        
        $stats = [];
        foreach ($results as $row) {
            $stats[$row['source']] = [
                'count' => (int) $row['count'],
                'last_registration' => $row['last_registration'],
            ];
        }
        
        return $stats;
    }
    
    private function get_source_label($source) {
        $sources = $this->plugin->get_allowed_sources();
        return isset($sources[$source]) ? $sources[$source] : $source;
    }
    
    private function get_option($key) {
        $options = get_option('registration_source_settings', []);
        return isset($options[$key]) ? $options[$key] : false;
    }
    
    public function add_registration_source_column($columns) {
        if (current_user_can('manage_options')) {
            $columns['registration_source'] = __('Registration Source', 'registration-source');
        }
        return $columns;
    }
    
    public function display_registration_source_column($value, $column_name, $user_id) {
        if ($column_name === 'registration_source' && current_user_can('manage_options')) {
            $source = $this->plugin->get_registration_source($user_id);
            return esc_html($this->get_source_label($source));
        }
        return $value;
    }
    
    public function add_bulk_actions($actions) {
        $actions['delete_registration_source'] = __('Delete Registration Source', 'registration-source');
        return $actions;
    }
    
    public function handle_bulk_actions($redirect_to, $doaction, $user_ids) {
        if ($doaction !== 'delete_registration_source') {
            return $redirect_to;
        }
        
        $processed = 0;
        foreach ($user_ids as $user_id) {
            if (current_user_can('edit_user', $user_id)) {
                delete_user_meta($user_id, 'registration_source');
                $processed++;
            }
        }
        
        return add_query_arg('deleted_registration_sources', $processed, $redirect_to);
    }
} 