<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Secure_AI_Agent_Access_Activator {

    public static function activate( $network_wide = false ) {
        if ( is_multisite() && $network_wide ) {
            $sites = get_sites();
            foreach ( $sites as $site ) {
                switch_to_blog( $site->blog_id );
                self::single_activate();
                restore_current_blog();
            }
        } else {
            self::single_activate();
        }
    }

    private static function single_activate() {
        self::create_tables();
        self::set_default_options();
        
        if ( ! wp_next_scheduled( 'saia_cleanup_cron' ) ) {
            wp_schedule_event( time(), 'daily', 'saia_cleanup_cron' );
        }
        
        flush_rewrite_rules();
    }

    private static function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $magic_links_table = $wpdb->prefix . 'saia_magic_links';
        $sessions_table = $wpdb->prefix . 'saia_sessions';
        
        $sql_magic_links = "CREATE TABLE $magic_links_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            token varchar(64) NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'unused',
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            expires_at datetime NOT NULL,
            used_at datetime DEFAULT NULL,
            ip_address varchar(45) DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY token (token),
            KEY user_id (user_id),
            KEY status (status),
            KEY expires_at (expires_at)
        ) $charset_collate;";
        
        $sql_sessions = "CREATE TABLE $sessions_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            session_key varchar(64) NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            link_id mediumint(9) DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            started_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            last_activity datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            expires_at datetime NOT NULL,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY session_key (session_key),
            KEY user_id (user_id),
            KEY status (status),
            KEY expires_at (expires_at)
        ) $charset_collate;";
        
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql_magic_links );
        dbDelta( $sql_sessions );
        
        add_option( 'saia_db_version', '1.0' );
    }

    private static function set_default_options() {
        $default_settings = array(
            'max_session_duration' => 3600, // 1 hour in seconds
            'inactivity_timeout' => 900, // 15 minutes in seconds
            'unused_link_expiration' => 86400, // 24 hours in seconds
            'enable_kill_switch' => true,
            'kill_switch_placement' => 'admin_bar',
            'kill_switch_style' => 'high_prominence',
            'enable_rate_limiting' => false,
            'rate_limit_per_hour' => 10,
            'enable_max_active_links' => false,
            'max_active_links' => 20,
            'enable_ip_restrictions' => false,
            'allowed_ips' => '',
            'data_retention_used_links' => 30,
            'data_retention_expired_links' => 7,
            'data_retention_sessions' => 30,
            'enable_auto_cleanup' => true,
        );
        
        add_option( 'saia_settings', $default_settings );
        add_option( 'saia_version', SAIA_VERSION );
    }
}