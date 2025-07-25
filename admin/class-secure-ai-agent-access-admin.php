<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Secure_AI_Agent_Access_Admin {

    private $plugin_name;
    private $version;

    public function __construct( $plugin_name, $version ) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function enqueue_styles() {
        wp_enqueue_style( $this->plugin_name, SAIA_PLUGIN_URL . 'admin/css/secure-ai-agent-access-admin.css', array(), $this->version, 'all' );
    }

    public function enqueue_scripts() {
        wp_enqueue_script( $this->plugin_name, SAIA_PLUGIN_URL . 'admin/js/secure-ai-agent-access-admin.js', array( 'jquery' ), $this->version, false );
        
        wp_localize_script( $this->plugin_name, 'saia_ajax', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'saia_ajax_nonce' ),
            'confirm_kill_all' => __( 'Are you sure you want to terminate all AI agent sessions?', 'secure-ai-agent-access' ),
            'confirm_terminate' => __( 'Are you sure you want to terminate this session?', 'secure-ai-agent-access' ),
            'link_copied' => __( 'Link copied to clipboard!', 'secure-ai-agent-access' ),
        ) );
    }

    /**
     * Check if the current user logged in via magic link
     *
     * @return bool True if logged in via magic link, false otherwise
     */
    public function is_magic_link_session() {
        if ( ! is_user_logged_in() ) {
            return false;
        }
        
        $user_id = get_current_user_id();
        
        // Check user meta for magic link session indicator
        $magic_link_session = get_user_meta( $user_id, 'saia_magic_link_session', true );
        
        if ( ! $magic_link_session ) {
            return false;
        }
        
        // Verify the session is still active
        global $wpdb;
        $sessions_table = $wpdb->prefix . 'saia_sessions';
        
        $active_session = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM $sessions_table WHERE user_id = %d AND status = 'active' AND session_key = %s",
            $user_id,
            $magic_link_session
        ) );
        
        // Debug logging when WP_DEBUG is enabled
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            if ( $wpdb->last_error ) {
                error_log( 'SAIA Magic Link Session Check Error: ' . $wpdb->last_error );
            }
        }
        
        return ! empty( $active_session );
    }

    /**
     * Restrict magic link users from accessing plugin admin pages
     *
     * @since 1.0.0
     */
    public function restrict_magic_link_admin_access() {
        // Only check on admin pages
        if ( ! is_admin() ) {
            return;
        }
        
        // Check if user logged in via magic link
        if ( ! $this->is_magic_link_session() ) {
            return;
        }
        
        // Get current page
        $current_page = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';
        
        // List of restricted pages
        $restricted_pages = array(
            'saia-dashboard',
            'saia-generate-link',
            'saia-manage-links',
            'saia-settings',
            'saia-network-dashboard',
        );
        
        // Redirect if trying to access restricted pages
        if ( in_array( $current_page, $restricted_pages, true ) ) {
            wp_safe_redirect( admin_url() );
            exit;
        }
    }

    public function add_plugin_admin_menu() {
        // Prevent magic link sessions from accessing admin menus
        if ( $this->is_magic_link_session() ) {
            return;
        }
        
        add_menu_page(
            __( 'AI Agent Access', 'secure-ai-agent-access' ),
            __( 'AI Agent Access', 'secure-ai-agent-access' ),
            'manage_options',
            'saia-dashboard',
            array( $this, 'display_dashboard_page' ),
            'dashicons-shield',
            100
        );
        
        add_submenu_page(
            'saia-dashboard',
            __( 'Dashboard', 'secure-ai-agent-access' ),
            __( 'Dashboard', 'secure-ai-agent-access' ),
            'manage_options',
            'saia-dashboard',
            array( $this, 'display_dashboard_page' )
        );
        
        add_submenu_page(
            'saia-dashboard',
            __( 'Generate Link', 'secure-ai-agent-access' ),
            __( 'Generate Link', 'secure-ai-agent-access' ),
            'manage_options',
            'saia-generate-link',
            array( $this, 'display_generate_link_page' )
        );
        
        add_submenu_page(
            'saia-dashboard',
            __( 'Manage Links', 'secure-ai-agent-access' ),
            __( 'Manage Links', 'secure-ai-agent-access' ),
            'manage_options',
            'saia-manage-links',
            array( $this, 'display_manage_links_page' )
        );
        
        add_submenu_page(
            'saia-dashboard',
            __( 'Settings', 'secure-ai-agent-access' ),
            __( 'Settings', 'secure-ai-agent-access' ),
            'manage_options',
            'saia-settings',
            array( $this, 'display_settings_page' )
        );
    }

    public function add_network_admin_menu() {
        if ( ! is_multisite() ) {
            return;
        }
        
        // Prevent magic link sessions from accessing network admin menus
        if ( $this->is_magic_link_session() ) {
            return;
        }
        
        add_menu_page(
            __( 'AI Agent Access', 'secure-ai-agent-access' ),
            __( 'AI Agent Access', 'secure-ai-agent-access' ),
            'manage_network',
            'saia-network-dashboard',
            array( $this, 'display_network_dashboard_page' ),
            'dashicons-shield',
            100
        );
    }

    public function add_admin_bar_items( $wp_admin_bar ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        
        // Prevent magic link sessions from accessing admin bar items
        if ( $this->is_magic_link_session() ) {
            return;
        }
        
        $settings = get_option( 'saia_settings' );
        
        $wp_admin_bar->add_node( array(
            'id'    => 'saia-menu',
            'title' => '<span class="ab-icon dashicons dashicons-shield"></span>' . __( 'AI Access', 'secure-ai-agent-access' ),
            'href'  => admin_url( 'admin.php?page=saia-dashboard' ),
        ) );
        
        $wp_admin_bar->add_node( array(
            'parent' => 'saia-menu',
            'id'     => 'saia-dashboard',
            'title'  => __( 'Dashboard', 'secure-ai-agent-access' ),
            'href'   => admin_url( 'admin.php?page=saia-dashboard' ),
        ) );
        
        $wp_admin_bar->add_node( array(
            'parent' => 'saia-menu',
            'id'     => 'saia-generate-link',
            'title'  => __( 'Generate Link', 'secure-ai-agent-access' ),
            'href'   => admin_url( 'admin.php?page=saia-generate-link' ),
        ) );
        
        $active_sessions = $this->get_active_sessions_count();
        $wp_admin_bar->add_node( array(
            'parent' => 'saia-menu',
            'id'     => 'saia-active-sessions',
            'title'  => sprintf( __( 'Active Sessions (%d)', 'secure-ai-agent-access' ), $active_sessions ),
            'href'   => admin_url( 'admin.php?page=saia-dashboard' ),
        ) );
        
        $wp_admin_bar->add_node( array(
            'parent' => 'saia-menu',
            'id'     => 'saia-settings',
            'title'  => __( 'Settings', 'secure-ai-agent-access' ),
            'href'   => admin_url( 'admin.php?page=saia-settings' ),
        ) );
        
        if ( ! empty( $settings['enable_kill_switch'] ) && $settings['kill_switch_placement'] === 'admin_bar' ) {
            $kill_switch_class = $settings['kill_switch_style'] === 'high_prominence' ? 'saia-kill-switch-high' : 'saia-kill-switch-normal';
            $admin_bar_class = $settings['kill_switch_style'] === 'high_prominence' ? '' : 'saia-standard-style';
            $wp_admin_bar->add_node( array(
                'id'    => 'saia-kill-switch',
                'title' => '<span class="' . $kill_switch_class . '">' . __( 'KILL ALL SESSIONS', 'secure-ai-agent-access' ) . '</span>',
                'href'  => '#',
                'meta'  => array(
                    'onclick' => 'saiaKillAllSessions(); return false;',
                    'class' => $admin_bar_class,
                ),
            ) );
        }
    }

    public function display_dashboard_page() {
        include_once SAIA_PLUGIN_DIR . 'admin/partials/secure-ai-agent-access-admin-dashboard.php';
    }

    public function display_generate_link_page() {
        include_once SAIA_PLUGIN_DIR . 'admin/partials/secure-ai-agent-access-admin-generate-link.php';
    }

    public function display_manage_links_page() {
        include_once SAIA_PLUGIN_DIR . 'admin/partials/secure-ai-agent-access-admin-manage-links.php';
    }

    public function display_settings_page() {
        include_once SAIA_PLUGIN_DIR . 'admin/partials/secure-ai-agent-access-admin-settings.php';
    }

    public function display_network_dashboard_page() {
        include_once SAIA_PLUGIN_DIR . 'admin/partials/secure-ai-agent-access-admin-network-dashboard.php';
    }

    public function register_settings() {
        register_setting( 'saia_settings_group', 'saia_settings', array(
            'sanitize_callback' => array( $this, 'sanitize_settings' ),
        ) );
    }

    public function sanitize_settings( $input ) {
        $sanitized = array();
        
        $sanitized['max_session_duration'] = absint( $input['max_session_duration'] );
        $sanitized['max_session_duration'] = max( 900, min( 604800, $sanitized['max_session_duration'] ) ); // 15 min to 7 days
        
        $sanitized['inactivity_timeout'] = absint( $input['inactivity_timeout'] );
        $sanitized['inactivity_timeout'] = max( 300, min( 86400, $sanitized['inactivity_timeout'] ) ); // 5 min to 24 hours
        
        $sanitized['unused_link_expiration'] = absint( $input['unused_link_expiration'] );
        $sanitized['unused_link_expiration'] = max( 3600, min( 2592000, $sanitized['unused_link_expiration'] ) ); // 1 hour to 30 days
        
        $sanitized['enable_kill_switch'] = ! empty( $input['enable_kill_switch'] );
        $sanitized['kill_switch_placement'] = in_array( $input['kill_switch_placement'], array( 'admin_bar', 'dashboard_only' ), true ) ? $input['kill_switch_placement'] : 'admin_bar';
        $sanitized['kill_switch_style'] = in_array( $input['kill_switch_style'], array( 'high_prominence', 'standard' ), true ) ? $input['kill_switch_style'] : 'high_prominence';
        
        $sanitized['enable_rate_limiting'] = ! empty( $input['enable_rate_limiting'] );
        $sanitized['rate_limit_per_hour'] = absint( $input['rate_limit_per_hour'] );
        $sanitized['rate_limit_per_hour'] = max( 1, min( 1000, $sanitized['rate_limit_per_hour'] ) );
        
        $sanitized['enable_max_active_links'] = ! empty( $input['enable_max_active_links'] );
        $sanitized['max_active_links'] = absint( $input['max_active_links'] );
        $sanitized['max_active_links'] = max( 1, min( 100, $sanitized['max_active_links'] ) );
        
        $sanitized['enable_ip_restrictions'] = ! empty( $input['enable_ip_restrictions'] );
        $sanitized['allowed_ips'] = sanitize_textarea_field( $input['allowed_ips'] );
        
        $sanitized['data_retention_used_links'] = absint( $input['data_retention_used_links'] );
        $sanitized['data_retention_used_links'] = max( 1, min( 365, $sanitized['data_retention_used_links'] ) );
        
        $sanitized['data_retention_expired_links'] = absint( $input['data_retention_expired_links'] );
        $sanitized['data_retention_expired_links'] = max( 1, min( 365, $sanitized['data_retention_expired_links'] ) );
        
        $sanitized['data_retention_sessions'] = absint( $input['data_retention_sessions'] );
        $sanitized['data_retention_sessions'] = max( 1, min( 365, $sanitized['data_retention_sessions'] ) );
        
        $sanitized['enable_auto_cleanup'] = ! empty( $input['enable_auto_cleanup'] );
        
        return $sanitized;
    }

    public function ajax_generate_link() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'saia_ajax_nonce' ) ) {
            wp_die( __( 'Security check failed', 'secure-ai-agent-access' ) );
        }
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'secure-ai-agent-access' ) ) );
        }
        
        $user_id = absint( $_POST['user_id'] );
        $user = get_user_by( 'id', $user_id );
        
        if ( ! $user ) {
            wp_send_json_error( array( 'message' => __( 'Invalid user selected', 'secure-ai-agent-access' ) ) );
        }
        
        // Allow all users including administrators
        // Removed administrator check to allow magic links for all users
        
        $settings = get_option( 'saia_settings' );
        
        if ( ! empty( $settings['enable_rate_limiting'] ) ) {
            if ( ! $this->check_rate_limit() ) {
                wp_send_json_error( array( 'message' => __( 'Rate limit exceeded. Please try again later.', 'secure-ai-agent-access' ) ) );
            }
        }
        
        if ( ! empty( $settings['enable_max_active_links'] ) ) {
            if ( ! $this->check_max_active_links() ) {
                wp_send_json_error( array( 'message' => __( 'Maximum active links limit reached.', 'secure-ai-agent-access' ) ) );
            }
        }
        
        $magic_links = new Secure_AI_Agent_Access_Magic_Links();
        $link = $magic_links->generate_magic_link( $user_id );
        
        if ( $link ) {
            wp_send_json_success( array(
                'link' => $link,
                'masked_link' => $this->mask_link( $link ),
            ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to generate magic link', 'secure-ai-agent-access' ) ) );
        }
    }

    public function ajax_terminate_session() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'saia_ajax_nonce' ) ) {
            wp_die( __( 'Security check failed', 'secure-ai-agent-access' ) );
        }
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'secure-ai-agent-access' ) ) );
        }
        
        $session_id = absint( $_POST['session_id'] );
        $sessions = new Secure_AI_Agent_Access_Sessions();
        
        if ( $sessions->terminate_session( $session_id ) ) {
            wp_send_json_success( array( 'message' => __( 'Session terminated successfully', 'secure-ai-agent-access' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to terminate session', 'secure-ai-agent-access' ) ) );
        }
    }

    public function ajax_kill_all_sessions() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'saia_ajax_nonce' ) ) {
            wp_die( __( 'Security check failed', 'secure-ai-agent-access' ) );
        }
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'secure-ai-agent-access' ) ) );
        }
        
        $sessions = new Secure_AI_Agent_Access_Sessions();
        $count = $sessions->terminate_all_sessions();
        
        wp_send_json_success( array(
            'message' => sprintf(
                _n(
                    '%d active AI agent session has been terminated successfully.',
                    '%d active AI agent sessions have been terminated successfully.',
                    $count,
                    'secure-ai-agent-access'
                ),
                $count
            ),
            'count' => $count,
        ) );
    }

    public function ajax_manual_cleanup() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'saia_ajax_nonce' ) ) {
            wp_die( __( 'Security check failed', 'secure-ai-agent-access' ) );
        }
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'secure-ai-agent-access' ) ) );
        }
        
        $this->cleanup_expired_data();
        
        wp_send_json_success( array( 'message' => __( 'Cleanup completed successfully', 'secure-ai-agent-access' ) ) );
    }

    public function cleanup_expired_data() {
        $settings = get_option( 'saia_settings' );
        
        if ( empty( $settings['enable_auto_cleanup'] ) ) {
            return;
        }
        
        global $wpdb;
        $magic_links_table = $wpdb->prefix . 'saia_magic_links';
        $sessions_table = $wpdb->prefix . 'saia_sessions';
        
        $used_links_days = $settings['data_retention_used_links'];
        $expired_links_days = $settings['data_retention_expired_links'];
        $sessions_days = $settings['data_retention_sessions'];
        
        $wpdb->query( $wpdb->prepare(
            "DELETE FROM $magic_links_table WHERE status = 'used' AND used_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $used_links_days
        ) );
        
        $wpdb->query( $wpdb->prepare(
            "DELETE FROM $magic_links_table WHERE status = 'expired' AND expires_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $expired_links_days
        ) );
        
        $wpdb->query( $wpdb->prepare(
            "DELETE FROM $sessions_table WHERE status IN ('terminated', 'expired') AND started_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $sessions_days
        ) );
    }

    private function get_active_sessions_count() {
        global $wpdb;
        $sessions_table = $wpdb->prefix . 'saia_sessions';
        
        $count = get_transient( 'saia_active_sessions_count' );
        
        if ( false === $count ) {
            $count = $wpdb->get_var(
                "SELECT COUNT(*) FROM $sessions_table WHERE status = 'active'"
            );
            set_transient( 'saia_active_sessions_count', $count, 60 );
        }
        
        return absint( $count );
    }

    private function check_rate_limit() {
        $settings = get_option( 'saia_settings' );
        $limit = $settings['rate_limit_per_hour'];
        
        global $wpdb;
        $magic_links_table = $wpdb->prefix . 'saia_magic_links';
        
        $count = $wpdb->get_var(
            "SELECT COUNT(*) FROM $magic_links_table WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)"
        );
        
        return $count < $limit;
    }

    private function check_max_active_links() {
        $settings = get_option( 'saia_settings' );
        $limit = $settings['max_active_links'];
        
        global $wpdb;
        $magic_links_table = $wpdb->prefix . 'saia_magic_links';
        
        $count = $wpdb->get_var(
            "SELECT COUNT(*) FROM $magic_links_table WHERE status = 'unused' AND expires_at > NOW()"
        );
        
        return $count < $limit;
    }

    private function mask_link( $link ) {
        $parsed = parse_url( $link );
        $domain = $parsed['scheme'] . '://' . $parsed['host'];
        $token = substr( $parsed['query'], -6 );
        return $domain . '/...' . $token;
    }

    public function ajax_delete_link() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'saia_ajax_nonce' ) ) {
            wp_die( __( 'Security check failed', 'secure-ai-agent-access' ) );
        }
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'secure-ai-agent-access' ) ) );
        }
        
        $link_id = absint( $_POST['link_id'] );
        
        global $wpdb;
        $links_table = $wpdb->prefix . 'saia_magic_links';
        
        $result = $wpdb->delete( $links_table, array( 'id' => $link_id ), array( '%d' ) );
        
        if ( $result ) {
            wp_send_json_success( array( 'message' => __( 'Link deleted successfully', 'secure-ai-agent-access' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to delete link', 'secure-ai-agent-access' ) ) );
        }
    }

    public function ajax_revoke_link() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'saia_ajax_nonce' ) ) {
            wp_die( __( 'Security check failed', 'secure-ai-agent-access' ) );
        }
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'secure-ai-agent-access' ) ) );
        }
        
        $link_id = absint( $_POST['link_id'] );
        
        global $wpdb;
        $links_table = $wpdb->prefix . 'saia_magic_links';
        
        $result = $wpdb->update(
            $links_table,
            array( 'status' => 'expired', 'expires_at' => current_time( 'mysql' ) ),
            array( 'id' => $link_id ),
            array( '%s', '%s' ),
            array( '%d' )
        );
        
        if ( $result !== false ) {
            wp_send_json_success( array( 'message' => __( 'Link revoked successfully', 'secure-ai-agent-access' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to revoke link', 'secure-ai-agent-access' ) ) );
        }
    }

    public function ajax_bulk_link_action() {
        if ( ! wp_verify_nonce( $_POST['nonce'], 'saia_ajax_nonce' ) ) {
            wp_die( __( 'Security check failed', 'secure-ai-agent-access' ) );
        }
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'secure-ai-agent-access' ) ) );
        }
        
        $action = sanitize_text_field( $_POST['bulk_action'] );
        $link_ids = array_map( 'absint', $_POST['link_ids'] );
        
        if ( empty( $link_ids ) ) {
            wp_send_json_error( array( 'message' => __( 'No links selected', 'secure-ai-agent-access' ) ) );
        }
        
        global $wpdb;
        $links_table = $wpdb->prefix . 'saia_magic_links';
        
        $count = 0;
        
        if ( $action === 'delete' ) {
            foreach ( $link_ids as $link_id ) {
                $result = $wpdb->delete( $links_table, array( 'id' => $link_id ), array( '%d' ) );
                if ( $result ) {
                    $count++;
                }
            }
            $message = sprintf( _n( '%d link deleted', '%d links deleted', $count, 'secure-ai-agent-access' ), $count );
        } elseif ( $action === 'revoke' ) {
            foreach ( $link_ids as $link_id ) {
                $result = $wpdb->update(
                    $links_table,
                    array( 'status' => 'expired', 'expires_at' => current_time( 'mysql' ) ),
                    array( 'id' => $link_id, 'status' => 'unused' ),
                    array( '%s', '%s' ),
                    array( '%d', '%s' )
                );
                if ( $result ) {
                    $count++;
                }
            }
            $message = sprintf( _n( '%d link revoked', '%d links revoked', $count, 'secure-ai-agent-access' ), $count );
        } else {
            wp_send_json_error( array( 'message' => __( 'Invalid action', 'secure-ai-agent-access' ) ) );
        }
        
        if ( $count > 0 ) {
            wp_send_json_success( array( 'message' => $message ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'No links were affected', 'secure-ai-agent-access' ) ) );
        }
    }
}