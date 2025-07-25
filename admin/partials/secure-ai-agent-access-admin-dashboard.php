<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

$sessions = new Secure_AI_Agent_Access_Sessions();
$active_sessions = $sessions->get_active_sessions();
$session_stats = $sessions->get_session_stats();

$magic_links = new Secure_AI_Agent_Access_Magic_Links();

global $wpdb;
$links_table = $wpdb->prefix . 'saia_magic_links';
$recent_links = $wpdb->get_results(
    "SELECT l.*, u.user_login 
     FROM $links_table l 
     LEFT JOIN {$wpdb->users} u ON l.user_id = u.ID 
     ORDER BY l.created_at DESC 
     LIMIT 5"
);

$settings = get_option( 'saia_settings' );
?>

<div class="wrap saia-dashboard-wrap">
    <h1><?php esc_html_e( 'AI Agent Access', 'secure-ai-agent-access' ); ?></h1>
    
    <?php if ( ! empty( $settings['enable_kill_switch'] ) && $settings['kill_switch_placement'] === 'dashboard_only' ) : ?>
        <div style="margin: 20px 0;">
            <?php if ( $settings['kill_switch_style'] === 'high_prominence' ) : ?>
                <button type="button" id="saia-kill-all-sessions" class="button button-primary saia-kill-switch-high" style="background: #d63638; border-color: #d63638;">
                    <?php esc_html_e( 'KILL ALL SESSIONS', 'secure-ai-agent-access' ); ?>
                </button>
            <?php else : ?>
                <button type="button" id="saia-kill-all-sessions" class="button saia-kill-switch-normal">
                    <?php esc_html_e( 'KILL ALL SESSIONS', 'secure-ai-agent-access' ); ?>
                </button>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <div class="saia-card">
        <h2><?php esc_html_e( 'Quick Actions', 'secure-ai-agent-access' ); ?></h2>
        <div class="saia-quick-actions">
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=saia-generate-link' ) ); ?>" class="button button-primary">
                <?php esc_html_e( '+ Generate New Link', 'secure-ai-agent-access' ); ?>
            </a>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=saia-manage-links' ) ); ?>" class="button">
                <?php esc_html_e( 'View All Links', 'secure-ai-agent-access' ); ?>
            </a>
        </div>
    </div>
    
    <div class="saia-card">
        <h2><?php esc_html_e( 'Active AI Agent Sessions', 'secure-ai-agent-access' ); ?></h2>
        <p><?php echo esc_html( sprintf( __( 'Status: %d Active Sessions', 'secure-ai-agent-access' ), $session_stats['active'] ) ); ?></p>
        
        <div class="saia-active-sessions">
            <?php if ( ! empty( $active_sessions ) ) : ?>
                <?php foreach ( $active_sessions as $session ) : 
                    $time_remaining = strtotime( $session->expires_at ) - time();
                    $minutes_remaining = round( $time_remaining / 60 );
                ?>
                    <div class="saia-session-card active">
                        <div class="saia-session-status active">
                            <?php esc_html_e( 'ACTIVE', 'secure-ai-agent-access' ); ?>
                            <span style="float: right;"><?php echo esc_html( sprintf( __( 'Session #%d', 'secure-ai-agent-access' ), $session->id ) ); ?></span>
                        </div>
                        <div class="saia-session-details">
                            <p><strong><?php esc_html_e( 'User:', 'secure-ai-agent-access' ); ?></strong> <?php echo esc_html( $session->user_login ); ?></p>
                            <p><strong><?php esc_html_e( 'Started:', 'secure-ai-agent-access' ); ?></strong> <?php echo esc_html( date_i18n( 'g:i A', strtotime( $session->started_at ) ) ); ?></p>
                            <p><strong><?php esc_html_e( 'Time Remaining:', 'secure-ai-agent-access' ); ?></strong> <?php echo esc_html( sprintf( _n( '%d minute', '%d minutes', $minutes_remaining, 'secure-ai-agent-access' ), $minutes_remaining ) ); ?></p>
                        </div>
                        <div class="saia-session-actions">
                            <button type="button" class="button saia-terminate-session" data-session-id="<?php echo esc_attr( $session->id ); ?>">
                                <?php esc_html_e( 'Terminate Session', 'secure-ai-agent-access' ); ?>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else : ?>
                <p><?php esc_html_e( 'No active sessions.', 'secure-ai-agent-access' ); ?></p>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="saia-card">
        <h2><?php esc_html_e( 'Recent Links', 'secure-ai-agent-access' ); ?></h2>
        <ul class="saia-recent-links">
            <?php foreach ( $recent_links as $link ) : 
                $masked_url = substr( site_url(), 0, -10 ) . '...' . substr( $link->token, -6 );
                $status_text = '';
                $status_class = '';
                
                switch ( $link->status ) {
                    case 'used':
                        $status_text = sprintf( __( 'Used - %s', 'secure-ai-agent-access' ), date_i18n( 'g:i A', strtotime( $link->used_at ) ) );
                        $status_class = 'used';
                        break;
                    case 'unused':
                        $expires_in = human_time_diff( time(), strtotime( $link->expires_at ) );
                        $status_text = sprintf( __( 'Unused - Expires in %s', 'secure-ai-agent-access' ), $expires_in );
                        $status_class = 'unused';
                        break;
                    case 'expired':
                        $status_text = __( 'Expired', 'secure-ai-agent-access' );
                        $status_class = 'expired';
                        break;
                }
            ?>
                <li>
                    <span class="saia-link-status <?php echo esc_attr( $status_class ); ?>"></span>
                    <span><?php echo esc_html( $masked_url ); ?></span>
                    <span>(<?php echo esc_html( $status_text ); ?>)</span>
                </li>
            <?php endforeach; ?>
        </ul>
        <div style="text-align: right;">
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=saia-manage-links' ) ); ?>">
                <?php esc_html_e( 'View All Links →', 'secure-ai-agent-access' ); ?>
            </a>
        </div>
    </div>
</div>