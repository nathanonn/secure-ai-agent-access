<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

$settings = get_option( 'saia_settings' );

global $wpdb;
$links_table = $wpdb->prefix . 'saia_magic_links';
$sessions_table = $wpdb->prefix . 'saia_sessions';

$data_stats = array(
    'links' => $wpdb->get_var( "SELECT COUNT(*) FROM $links_table" ),
    'sessions' => $wpdb->get_var( "SELECT COUNT(*) FROM $sessions_table" ),
);
?>

<div class="wrap">
    <h1><?php esc_html_e( 'AI Agent Access Settings', 'secure-ai-agent-access' ); ?></h1>
    
    <form method="post" action="options.php">
        <?php settings_fields( 'saia_settings_group' ); ?>
        
        <div class="saia-settings-section">
            <h3><?php esc_html_e( 'Security Timeouts', 'secure-ai-agent-access' ); ?></h3>
            
            <div class="saia-setting-row">
                <label><?php esc_html_e( 'Maximum Session Duration', 'secure-ai-agent-access' ); ?></label>
                <div class="saia-slider-container">
                    <input type="range" class="saia-slider" data-unit="seconds" 
                           min="900" max="604800" step="900" 
                           value="<?php echo esc_attr( $settings['max_session_duration'] ); ?>">
                    <span class="saia-slider-display saia-slider-value"></span>
                    <input type="hidden" name="saia_settings[max_session_duration]" 
                           value="<?php echo esc_attr( $settings['max_session_duration'] ); ?>">
                </div>
                <p class="saia-setting-description">
                    <?php esc_html_e( '(15 minutes - 7 days)', 'secure-ai-agent-access' ); ?>
                </p>
            </div>
            
            <div class="saia-setting-row">
                <label><?php esc_html_e( 'Inactivity Timeout', 'secure-ai-agent-access' ); ?></label>
                <div class="saia-slider-container">
                    <input type="range" class="saia-slider" data-unit="seconds" 
                           min="300" max="86400" step="300" 
                           value="<?php echo esc_attr( $settings['inactivity_timeout'] ); ?>">
                    <span class="saia-slider-display saia-slider-value"></span>
                    <input type="hidden" name="saia_settings[inactivity_timeout]" 
                           value="<?php echo esc_attr( $settings['inactivity_timeout'] ); ?>">
                </div>
                <p class="saia-setting-description">
                    <?php esc_html_e( '(5 minutes - 24 hours)', 'secure-ai-agent-access' ); ?>
                </p>
            </div>
            
            <div class="saia-setting-row">
                <label><?php esc_html_e( 'Unused Link Expiration', 'secure-ai-agent-access' ); ?></label>
                <div class="saia-slider-container">
                    <input type="range" class="saia-slider" data-unit="seconds" 
                           min="3600" max="2592000" step="3600" 
                           value="<?php echo esc_attr( $settings['unused_link_expiration'] ); ?>">
                    <span class="saia-slider-display saia-slider-value"></span>
                    <input type="hidden" name="saia_settings[unused_link_expiration]" 
                           value="<?php echo esc_attr( $settings['unused_link_expiration'] ); ?>">
                </div>
                <p class="saia-setting-description">
                    <?php esc_html_e( '(1 hour - 30 days)', 'secure-ai-agent-access' ); ?>
                </p>
            </div>
        </div>
        
        <div class="saia-settings-section">
            <h3><?php esc_html_e( 'Emergency Features', 'secure-ai-agent-access' ); ?></h3>
            
            <div class="saia-setting-row">
                <label>
                    <input type="checkbox" name="saia_settings[enable_kill_switch]" value="1" 
                           <?php checked( ! empty( $settings['enable_kill_switch'] ) ); ?>>
                    <?php esc_html_e( 'Enable Emergency Kill Switch', 'secure-ai-agent-access' ); ?>
                </label>
                <p class="saia-setting-description">
                    <?php esc_html_e( 'Shows "Kill All Sessions" button in admin bar', 'secure-ai-agent-access' ); ?>
                </p>
            </div>
            
            <div class="saia-setting-row">
                <label><?php esc_html_e( 'Kill Switch Placement:', 'secure-ai-agent-access' ); ?></label>
                <label>
                    <input type="radio" name="saia_settings[kill_switch_placement]" value="admin_bar" 
                           <?php checked( $settings['kill_switch_placement'], 'admin_bar' ); ?>>
                    <?php esc_html_e( 'Admin Bar (Always Visible)', 'secure-ai-agent-access' ); ?>
                </label><br>
                <label>
                    <input type="radio" name="saia_settings[kill_switch_placement]" value="dashboard_only" 
                           <?php checked( $settings['kill_switch_placement'], 'dashboard_only' ); ?>>
                    <?php esc_html_e( 'Dashboard Only', 'secure-ai-agent-access' ); ?>
                </label>
            </div>
            
            <div class="saia-setting-row">
                <label><?php esc_html_e( 'Visual Style:', 'secure-ai-agent-access' ); ?></label>
                <label>
                    <input type="radio" name="saia_settings[kill_switch_style]" value="high_prominence" 
                           <?php checked( $settings['kill_switch_style'], 'high_prominence' ); ?>>
                    <?php esc_html_e( 'High Prominence (Red Button)', 'secure-ai-agent-access' ); ?>
                </label><br>
                <label>
                    <input type="radio" name="saia_settings[kill_switch_style]" value="standard" 
                           <?php checked( $settings['kill_switch_style'], 'standard' ); ?>>
                    <?php esc_html_e( 'Standard Button', 'secure-ai-agent-access' ); ?>
                </label>
            </div>
        </div>
        
        <div class="saia-settings-section">
            <h3><?php esc_html_e( 'Link Generation Controls', 'secure-ai-agent-access' ); ?></h3>
            
            <div class="saia-setting-row">
                <label>
                    <input type="checkbox" name="saia_settings[enable_rate_limiting]" value="1" 
                           data-toggle="rate-limit-settings"
                           <?php checked( ! empty( $settings['enable_rate_limiting'] ) ); ?>>
                    <?php esc_html_e( 'Enable Rate Limiting', 'secure-ai-agent-access' ); ?>
                </label>
                <div class="rate-limit-settings" style="margin-top: 10px;">
                    <label><?php esc_html_e( 'Maximum links per hour:', 'secure-ai-agent-access' ); ?></label>
                    <input type="number" name="saia_settings[rate_limit_per_hour]" 
                           value="<?php echo esc_attr( $settings['rate_limit_per_hour'] ); ?>" 
                           min="1" max="1000" style="width: 100px;">
                </div>
            </div>
            
            <div class="saia-setting-row">
                <label>
                    <input type="checkbox" name="saia_settings[enable_max_active_links]" value="1" 
                           data-toggle="max-links-settings"
                           <?php checked( ! empty( $settings['enable_max_active_links'] ) ); ?>>
                    <?php esc_html_e( 'Limit Active Links', 'secure-ai-agent-access' ); ?>
                </label>
                <div class="max-links-settings" style="margin-top: 10px;">
                    <label><?php esc_html_e( 'Maximum active links:', 'secure-ai-agent-access' ); ?></label>
                    <input type="number" name="saia_settings[max_active_links]" 
                           value="<?php echo esc_attr( $settings['max_active_links'] ); ?>" 
                           min="1" max="100" style="width: 100px;">
                </div>
            </div>
            
            <div class="saia-setting-row">
                <label>
                    <input type="checkbox" name="saia_settings[enable_ip_restrictions]" value="1" 
                           data-toggle="ip-settings"
                           <?php checked( ! empty( $settings['enable_ip_restrictions'] ) ); ?>>
                    <?php esc_html_e( 'Enable IP Restrictions', 'secure-ai-agent-access' ); ?>
                </label>
                <div class="ip-settings" style="margin-top: 10px;">
                    <label><?php esc_html_e( 'Allowed IPs (one per line):', 'secure-ai-agent-access' ); ?></label><br>
                    <textarea name="saia_settings[allowed_ips]" rows="5" cols="50"><?php echo esc_textarea( $settings['allowed_ips'] ); ?></textarea>
                </div>
            </div>
        </div>
        
        <div class="saia-settings-section">
            <h3><?php esc_html_e( 'Data Management', 'secure-ai-agent-access' ); ?></h3>
            
            <h4><?php esc_html_e( 'Data Retention Settings', 'secure-ai-agent-access' ); ?></h4>
            
            <div class="saia-setting-row">
                <label><?php esc_html_e( 'Used Links:', 'secure-ai-agent-access' ); ?></label>
                <input type="number" name="saia_settings[data_retention_used_links]" 
                       value="<?php echo esc_attr( $settings['data_retention_used_links'] ); ?>" 
                       min="1" max="365" style="width: 80px;"> <?php esc_html_e( 'days', 'secure-ai-agent-access' ); ?>
            </div>
            
            <div class="saia-setting-row">
                <label><?php esc_html_e( 'Expired Links:', 'secure-ai-agent-access' ); ?></label>
                <input type="number" name="saia_settings[data_retention_expired_links]" 
                       value="<?php echo esc_attr( $settings['data_retention_expired_links'] ); ?>" 
                       min="1" max="365" style="width: 80px;"> <?php esc_html_e( 'days', 'secure-ai-agent-access' ); ?>
            </div>
            
            <div class="saia-setting-row">
                <label><?php esc_html_e( 'Session Logs:', 'secure-ai-agent-access' ); ?></label>
                <input type="number" name="saia_settings[data_retention_sessions]" 
                       value="<?php echo esc_attr( $settings['data_retention_sessions'] ); ?>" 
                       min="1" max="365" style="width: 80px;"> <?php esc_html_e( 'days', 'secure-ai-agent-access' ); ?>
            </div>
            
            <div class="saia-setting-row">
                <label>
                    <input type="checkbox" name="saia_settings[enable_auto_cleanup]" value="1" 
                           <?php checked( ! empty( $settings['enable_auto_cleanup'] ) ); ?>>
                    <?php esc_html_e( 'Automatic Cleanup: Enabled (runs daily at 3 AM)', 'secure-ai-agent-access' ); ?>
                </label>
            </div>
            
            <h4><?php esc_html_e( 'Manual Cleanup', 'secure-ai-agent-access' ); ?></h4>
            <div class="saia-data-stats">
                <p><?php echo esc_html( sprintf( __( 'Current Data: %d links, %d sessions', 'secure-ai-agent-access' ), $data_stats['links'], $data_stats['sessions'] ) ); ?></p>
                <button type="button" id="saia-cleanup-now" class="button">
                    <?php esc_html_e( 'Clean Old Data Now', 'secure-ai-agent-access' ); ?>
                </button>
            </div>
        </div>
        
        <p class="submit">
            <input type="submit" class="button-primary" value="<?php esc_attr_e( 'Save Settings', 'secure-ai-agent-access' ); ?>">
            <button type="button" class="button" onclick="if(confirm('<?php esc_attr_e( 'Are you sure you want to reset to defaults?', 'secure-ai-agent-access' ); ?>')) { window.location.href='<?php echo esc_url( admin_url( 'admin.php?page=saia-settings&reset=true' ) ); ?>'; }">
                <?php esc_html_e( 'Reset to Defaults', 'secure-ai-agent-access' ); ?>
            </button>
        </p>
    </form>
</div>