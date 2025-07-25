<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Secure_AI_Agent_Access_Magic_Links {

    public function generate_magic_link( $user_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'saia_magic_links';
        
        $token = $this->generate_secure_token();
        $settings = get_option( 'saia_settings' );
        $expires_at = date( 'Y-m-d H:i:s', time() + $settings['unused_link_expiration'] );
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'token' => $token,
                'user_id' => $user_id,
                'status' => 'unused',
                'expires_at' => $expires_at,
                'ip_address' => $this->get_client_ip(),
            ),
            array( '%s', '%d', '%s', '%s', '%s' )
        );
        
        if ( $result ) {
            return $this->build_magic_link_url( $token );
        }
        
        return false;
    }

    public function handle_magic_link_authentication() {
        if ( ! isset( $_GET['ai_token'] ) ) {
            return;
        }
        
        $token = sanitize_text_field( $_GET['ai_token'] );
        
        if ( is_user_logged_in() ) {
            wp_logout();
        }
        
        $link_data = $this->validate_magic_link( $token );
        
        if ( ! $link_data ) {
            wp_die( __( 'Invalid or expired magic link.', 'secure-ai-agent-access' ) );
        }
        
        $user = get_user_by( 'id', $link_data->user_id );
        
        if ( ! $user ) {
            wp_die( __( 'User not found.', 'secure-ai-agent-access' ) );
        }
        
        // Allow all users including administrators to use magic links
        // Removed administrator check to allow magic links for all users
        
        $this->mark_link_as_used( $link_data->id );
        
        $session_key = $this->create_ai_session( $user->ID, $link_data->id );
        
        if ( ! $session_key ) {
            wp_die( __( 'Failed to create session.', 'secure-ai-agent-access' ) );
        }
        
        wp_set_current_user( $user->ID );
        wp_set_auth_cookie( $user->ID );
        
        // Set user meta to indicate this is a magic link session
        update_user_meta( $user->ID, 'saia_magic_link_session', $session_key );
        
        set_transient( 'saia_session_' . $user->ID, $session_key, 3600 );
        
        do_action( 'wp_login', $user->user_login, $user );
        
        $redirect_to = admin_url();
        if ( isset( $_GET['redirect_to'] ) ) {
            $redirect_to = esc_url_raw( $_GET['redirect_to'] );
        }
        
        wp_safe_redirect( $redirect_to );
        exit;
    }

    public function authenticate_magic_link( $user, $username, $password ) {
        if ( $user instanceof WP_User ) {
            return $user;
        }
        
        if ( ! isset( $_GET['ai_token'] ) ) {
            return $user;
        }
        
        return null;
    }

    private function validate_magic_link( $token ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'saia_magic_links';
        
        $link = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $table_name WHERE token = %s AND status = 'unused' AND expires_at > NOW()",
            $token
        ) );
        
        if ( ! $link ) {
            return false;
        }
        
        $settings = get_option( 'saia_settings' );
        
        if ( ! empty( $settings['enable_ip_restrictions'] ) ) {
            $allowed_ips = array_map( 'trim', explode( "\n", $settings['allowed_ips'] ) );
            $client_ip = $this->get_client_ip();
            
            if ( ! empty( $allowed_ips ) && ! in_array( $client_ip, $allowed_ips, true ) ) {
                return false;
            }
        }
        
        return $link;
    }

    private function mark_link_as_used( $link_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'saia_magic_links';
        
        $wpdb->update(
            $table_name,
            array(
                'status' => 'used',
                'used_at' => current_time( 'mysql' ),
            ),
            array( 'id' => $link_id ),
            array( '%s', '%s' ),
            array( '%d' )
        );
    }

    private function create_ai_session( $user_id, $link_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'saia_sessions';
        
        $session_key = $this->generate_secure_token();
        $settings = get_option( 'saia_settings' );
        $expires_at = date( 'Y-m-d H:i:s', time() + $settings['max_session_duration'] );
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'session_key' => $session_key,
                'user_id' => $user_id,
                'link_id' => $link_id,
                'status' => 'active',
                'expires_at' => $expires_at,
                'ip_address' => $this->get_client_ip(),
                'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ) : '',
            ),
            array( '%s', '%d', '%d', '%s', '%s', '%s', '%s' )
        );
        
        if ( $result ) {
            delete_transient( 'saia_active_sessions_count' );
            return $session_key;
        }
        
        return false;
    }

    private function generate_secure_token() {
        return bin2hex( random_bytes( 32 ) );
    }

    private function build_magic_link_url( $token ) {
        return add_query_arg( 'ai_token', $token, wp_login_url() );
    }

    private function get_client_ip() {
        $ip_keys = array( 'HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' );
        
        foreach ( $ip_keys as $key ) {
            if ( array_key_exists( $key, $_SERVER ) === true ) {
                foreach ( explode( ',', $_SERVER[$key] ) as $ip ) {
                    $ip = trim( $ip );
                    
                    if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) !== false ) {
                        return $ip;
                    }
                }
            }
        }
        
        return isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }
    
    public function cleanup_expired_links() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'saia_magic_links';
        
        $wpdb->update(
            $table_name,
            array( 'status' => 'expired' ),
            array( 'status' => 'unused', 'expires_at <' => current_time( 'mysql' ) ),
            array( '%s' ),
            array( '%s', '%s' )
        );
    }
}