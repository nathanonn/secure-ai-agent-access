<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package Secure_AI_Agent_Access
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

delete_option( 'saia_version' );
delete_option( 'saia_settings' );
delete_option( 'saia_db_version' );

delete_metadata( 'user', 0, 'saia_preferences', '', true );

global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}saia_magic_links" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}saia_sessions" );

wp_clear_scheduled_hook( 'saia_cleanup_cron' );

if ( is_multisite() ) {
    delete_site_option( 'saia_network_settings' );
    
    $sites = get_sites();
    foreach ( $sites as $site ) {
        switch_to_blog( $site->blog_id );
        
        delete_option( 'saia_version' );
        delete_option( 'saia_settings' );
        delete_option( 'saia_db_version' );
        
        $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}saia_magic_links" );
        $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}saia_sessions" );
        
        wp_clear_scheduled_hook( 'saia_cleanup_cron' );
        
        restore_current_blog();
    }
}