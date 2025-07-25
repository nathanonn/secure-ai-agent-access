<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Secure_AI_Agent_Access_Sessions {

    public function check_session_validity() {
        if ( ! is_user_logged_in() ) {
            return;
        }
        
        $user_id = get_current_user_id();
        $session_key = get_transient( 'saia_session_' . $user_id );
        
        if ( ! $session_key ) {
            return;
        }
        
        $session = $this->get_session_by_key( $session_key );
        
        if ( ! $session ) {
            delete_transient( 'saia_session_' . $user_id );
            return;
        }
        
        if ( $session->status !== 'active' ) {
            $this->end_session( $user_id );
            return;
        }
        
        $now = time();
        $expires_at = strtotime( $session->expires_at );
        $last_activity = strtotime( $session->last_activity );
        $settings = get_option( 'saia_settings' );
        
        if ( $now > $expires_at ) {
            $this->expire_session( $session->id );
            $this->end_session( $user_id );
            return;
        }
        
        if ( ( $now - $last_activity ) > $settings['inactivity_timeout'] ) {
            $this->expire_session( $session->id, 'inactive' );
            $this->end_session( $user_id );
            return;
        }
        
        $this->update_last_activity( $session->id );
    }

    public function get_active_sessions() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'saia_sessions';
        
        return $wpdb->get_results(
            "SELECT s.*, u.user_login, u.display_name 
             FROM $table_name s 
             LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID 
             WHERE s.status = 'active' 
             ORDER BY s.started_at DESC"
        );
    }

    public function get_session_by_key( $session_key ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'saia_sessions';
        
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $table_name WHERE session_key = %s",
            $session_key
        ) );
    }

    public function get_session_by_id( $session_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'saia_sessions';
        
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT s.*, u.user_login, u.display_name 
             FROM $table_name s 
             LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID 
             WHERE s.id = %d",
            $session_id
        ) );
    }

    public function terminate_session( $session_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'saia_sessions';
        
        $session = $this->get_session_by_id( $session_id );
        
        if ( ! $session || $session->status !== 'active' ) {
            return false;
        }
        
        $result = $wpdb->update(
            $table_name,
            array( 'status' => 'terminated' ),
            array( 'id' => $session_id ),
            array( '%s' ),
            array( '%d' )
        );
        
        if ( $result ) {
            delete_transient( 'saia_session_' . $session->user_id );
            delete_user_meta( $session->user_id, 'saia_magic_link_session' );
            delete_transient( 'saia_active_sessions_count' );
            
            $this->force_logout_user( $session->user_id );
            
            return true;
        }
        
        return false;
    }

    public function terminate_all_sessions() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'saia_sessions';
        
        $active_sessions = $this->get_active_sessions();
        
        $count = $wpdb->update(
            $table_name,
            array( 'status' => 'terminated' ),
            array( 'status' => 'active' ),
            array( '%s' ),
            array( '%s' )
        );
        
        foreach ( $active_sessions as $session ) {
            delete_transient( 'saia_session_' . $session->user_id );
            delete_user_meta( $session->user_id, 'saia_magic_link_session' );
            $this->force_logout_user( $session->user_id );
        }
        
        delete_transient( 'saia_active_sessions_count' );
        
        return $count;
    }

    private function expire_session( $session_id, $reason = 'expired' ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'saia_sessions';
        
        $wpdb->update(
            $table_name,
            array( 'status' => $reason ),
            array( 'id' => $session_id ),
            array( '%s' ),
            array( '%d' )
        );
        
        delete_transient( 'saia_active_sessions_count' );
    }

    private function update_last_activity( $session_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'saia_sessions';
        
        $wpdb->update(
            $table_name,
            array( 'last_activity' => current_time( 'mysql' ) ),
            array( 'id' => $session_id ),
            array( '%s' ),
            array( '%d' )
        );
    }

    private function end_session( $user_id ) {
        delete_transient( 'saia_session_' . $user_id );
        delete_user_meta( $user_id, 'saia_magic_link_session' );
        wp_logout();
        wp_redirect( wp_login_url() );
        exit;
    }

    private function force_logout_user( $user_id ) {
        $sessions = WP_Session_Tokens::get_instance( $user_id );
        $sessions->destroy_all();
    }

    public function cleanup_expired_sessions() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'saia_sessions';
        
        $wpdb->update(
            $table_name,
            array( 'status' => 'expired' ),
            array( 'status' => 'active', 'expires_at <' => current_time( 'mysql' ) ),
            array( '%s' ),
            array( '%s', '%s' )
        );
    }

    public function get_session_stats() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'saia_sessions';
        
        $stats = array(
            'active' => $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE status = 'active'" ),
            'total_today' => $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE DATE(started_at) = CURDATE()" ),
            'total_week' => $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE started_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)" ),
        );
        
        return $stats;
    }
    
    /**
     * Cleanup user session on logout
     *
     * @param int $user_id The user ID
     */
    public function cleanup_user_session( $user_id = null ) {
        if ( ! $user_id ) {
            $user_id = get_current_user_id();
        }
        
        if ( $user_id ) {
            delete_transient( 'saia_session_' . $user_id );
            delete_user_meta( $user_id, 'saia_magic_link_session' );
        }
    }
}