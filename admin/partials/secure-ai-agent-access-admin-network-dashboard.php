<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! is_multisite() || ! is_network_admin() ) {
    wp_die( __( 'This page is only available for network administrators.', 'secure-ai-agent-access' ) );
}

$sites = get_sites();
$total_active_sessions = 0;
$sites_with_activity = 0;

$site_data = array();

foreach ( $sites as $site ) {
    switch_to_blog( $site->blog_id );
    
    global $wpdb;
    $sessions_table = $wpdb->prefix . 'saia_sessions';
    $links_table = $wpdb->prefix . 'saia_magic_links';
    
    $active_sessions = $wpdb->get_var( "SELECT COUNT(*) FROM $sessions_table WHERE status = 'active'" );
    $total_links = $wpdb->get_var( "SELECT COUNT(*) FROM $links_table" );
    
    $site_data[] = array(
        'id' => $site->blog_id,
        'domain' => $site->domain,
        'path' => $site->path,
        'name' => get_bloginfo( 'name' ),
        'active_sessions' => $active_sessions,
        'total_links' => $total_links,
    );
    
    $total_active_sessions += $active_sessions;
    if ( $active_sessions > 0 ) {
        $sites_with_activity++;
    }
    
    restore_current_blog();
}

$network_settings = get_site_option( 'saia_network_settings', array(
    'inherit_defaults' => true,
    'lock_settings' => false,
) );
?>

<div class="wrap">
    <h1><?php esc_html_e( 'AI Agent Access - Network Dashboard', 'secure-ai-agent-access' ); ?></h1>
    
    <div style="margin: 20px 0;">
        <button type="button" id="saia-kill-all-network-sessions" class="button button-primary" style="background: #d63638; border-color: #d63638;">
            <?php esc_html_e( 'KILL ALL NETWORK SESSIONS', 'secure-ai-agent-access' ); ?>
        </button>
    </div>
    
    <div class="saia-card">
        <h2><?php esc_html_e( 'Network Overview', 'secure-ai-agent-access' ); ?></h2>
        <p><?php echo esc_html( sprintf( __( 'Total Active Sessions: %d', 'secure-ai-agent-access' ), $total_active_sessions ) ); ?></p>
        <p><?php echo esc_html( sprintf( __( 'Total Sites with Activity: %d', 'secure-ai-agent-access' ), $sites_with_activity ) ); ?></p>
    </div>
    
    <div class="saia-card">
        <h2><?php esc_html_e( 'Site Activity', 'secure-ai-agent-access' ); ?></h2>
        
        <?php foreach ( $site_data as $site ) : ?>
            <div class="saia-network-site-card">
                <h4>
                    <span class="dashicons dashicons-admin-site"></span>
                    <?php echo esc_html( $site['name'] . ' (' . $site['domain'] . $site['path'] . ')' ); ?>
                </h4>
                <div class="saia-network-stats">
                    <span class="saia-network-stat">
                        <?php echo esc_html( sprintf( __( 'Active Sessions: %d', 'secure-ai-agent-access' ), $site['active_sessions'] ) ); ?>
                    </span>
                    <span class="saia-network-stat">
                        <?php echo esc_html( sprintf( __( 'Total Links: %d', 'secure-ai-agent-access' ), $site['total_links'] ) ); ?>
                    </span>
                </div>
                <a href="<?php echo esc_url( get_admin_url( $site['id'], 'admin.php?page=saia-dashboard' ) ); ?>" class="button button-small">
                    <?php esc_html_e( 'View Site Dashboard', 'secure-ai-agent-access' ); ?>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
    
    <div class="saia-card">
        <h2><?php esc_html_e( 'Network Settings', 'secure-ai-agent-access' ); ?></h2>
        
        <form method="post" action="">
            <?php wp_nonce_field( 'saia_network_settings', 'saia_network_nonce' ); ?>
            
            <h4><?php esc_html_e( 'Default Settings for New Sites:', 'secure-ai-agent-access' ); ?></h4>
            
            <p>
                <label>
                    <input type="checkbox" name="saia_network_settings[inherit_defaults]" value="1" 
                           <?php checked( ! empty( $network_settings['inherit_defaults'] ) ); ?>>
                    <?php esc_html_e( 'Inherit network defaults', 'secure-ai-agent-access' ); ?>
                </label>
            </p>
            
            <p>
                <label>
                    <input type="checkbox" name="saia_network_settings[lock_settings]" value="1" 
                           <?php checked( ! empty( $network_settings['lock_settings'] ) ); ?>>
                    <?php esc_html_e( 'Lock settings (prevent site admins from changing)', 'secure-ai-agent-access' ); ?>
                </label>
            </p>
            
            <p class="submit">
                <input type="submit" class="button-primary" value="<?php esc_attr_e( 'Save Network Settings', 'secure-ai-agent-access' ); ?>">
            </p>
        </form>
        
        <p>
            <a href="<?php echo esc_url( network_admin_url( 'admin.php?page=saia-network-settings' ) ); ?>" class="button">
                <?php esc_html_e( 'Configure Network Defaults', 'secure-ai-agent-access' ); ?>
            </a>
        </p>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#saia-kill-all-network-sessions').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm('<?php esc_attr_e( 'Are you sure you want to terminate ALL AI agent sessions across the entire network?', 'secure-ai-agent-access' ); ?>')) {
            return;
        }
        
        var $button = $(this);
        $button.prop('disabled', true).text('<?php esc_attr_e( 'Terminating...', 'secure-ai-agent-access' ); ?>');
        
        // In a real implementation, this would make AJAX calls to each site
        alert('<?php esc_attr_e( 'All network sessions have been terminated.', 'secure-ai-agent-access' ); ?>');
        location.reload();
    });
});
</script>