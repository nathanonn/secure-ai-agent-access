<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Secure_AI_Agent_Access_Deactivator {

    public static function deactivate( $network_wide = false ) {
        if ( is_multisite() && $network_wide ) {
            $sites = get_sites();
            foreach ( $sites as $site ) {
                switch_to_blog( $site->blog_id );
                self::single_deactivate();
                restore_current_blog();
            }
        } else {
            self::single_deactivate();
        }
    }

    private static function single_deactivate() {
        wp_clear_scheduled_hook( 'saia_cleanup_cron' );
        
        self::terminate_all_sessions();
        
        delete_transient( 'saia_active_sessions_cache' );
        
        flush_rewrite_rules();
    }

    private static function terminate_all_sessions() {
        global $wpdb;
        $sessions_table = $wpdb->prefix . 'saia_sessions';
        
        $wpdb->update(
            $sessions_table,
            array( 'status' => 'terminated' ),
            array( 'status' => 'active' ),
            array( '%s' ),
            array( '%s' )
        );
        
        $session_keys = $wpdb->get_col( 
            "SELECT session_key FROM $sessions_table WHERE status = 'terminated'"
        );
        
        foreach ( $session_keys as $session_key ) {
            $sessions = WP_Session_Tokens::get_instance( get_current_user_id() );
            $sessions->destroy( $session_key );
        }
    }
}