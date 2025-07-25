<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Secure_AI_Agent_Access {

    protected $loader;
    protected $plugin_name;
    protected $version;

    public function __construct() {
        $this->version = SAIA_VERSION;
        $this->plugin_name = 'secure-ai-agent-access';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies() {
        require_once SAIA_PLUGIN_DIR . 'includes/class-secure-ai-agent-access-loader.php';
        require_once SAIA_PLUGIN_DIR . 'includes/class-secure-ai-agent-access-i18n.php';
        require_once SAIA_PLUGIN_DIR . 'includes/class-secure-ai-agent-access-activator.php';
        require_once SAIA_PLUGIN_DIR . 'includes/class-secure-ai-agent-access-deactivator.php';
        require_once SAIA_PLUGIN_DIR . 'admin/class-secure-ai-agent-access-admin.php';
        require_once SAIA_PLUGIN_DIR . 'includes/class-secure-ai-agent-access-magic-links.php';
        require_once SAIA_PLUGIN_DIR . 'includes/class-secure-ai-agent-access-sessions.php';

        $this->loader = new Secure_AI_Agent_Access_Loader();
    }

    private function set_locale() {
        $plugin_i18n = new Secure_AI_Agent_Access_i18n();
        $this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );
    }

    private function define_admin_hooks() {
        $plugin_admin = new Secure_AI_Agent_Access_Admin( $this->get_plugin_name(), $this->get_version() );

        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
        $this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_admin_menu' );
        $this->loader->add_action( 'network_admin_menu', $plugin_admin, 'add_network_admin_menu' );
        $this->loader->add_action( 'admin_init', $plugin_admin, 'register_settings' );
        $this->loader->add_action( 'admin_init', $plugin_admin, 'restrict_magic_link_admin_access' );
        $this->loader->add_action( 'admin_bar_menu', $plugin_admin, 'add_admin_bar_items', 100 );
        
        $this->loader->add_action( 'wp_ajax_saia_generate_link', $plugin_admin, 'ajax_generate_link' );
        $this->loader->add_action( 'wp_ajax_saia_terminate_session', $plugin_admin, 'ajax_terminate_session' );
        $this->loader->add_action( 'wp_ajax_saia_kill_all_sessions', $plugin_admin, 'ajax_kill_all_sessions' );
        $this->loader->add_action( 'wp_ajax_saia_manual_cleanup', $plugin_admin, 'ajax_manual_cleanup' );
        $this->loader->add_action( 'wp_ajax_saia_delete_link', $plugin_admin, 'ajax_delete_link' );
        $this->loader->add_action( 'wp_ajax_saia_revoke_link', $plugin_admin, 'ajax_revoke_link' );
        $this->loader->add_action( 'wp_ajax_saia_bulk_link_action', $plugin_admin, 'ajax_bulk_link_action' );
        
        $this->loader->add_action( 'saia_cleanup_cron', $plugin_admin, 'cleanup_expired_data' );
    }

    private function define_public_hooks() {
        $magic_links = new Secure_AI_Agent_Access_Magic_Links();
        $sessions = new Secure_AI_Agent_Access_Sessions();
        
        $this->loader->add_action( 'init', $magic_links, 'handle_magic_link_authentication' );
        $this->loader->add_action( 'init', $sessions, 'check_session_validity' );
        $this->loader->add_filter( 'authenticate', $magic_links, 'authenticate_magic_link', 30, 3 );
        $this->loader->add_action( 'wp_logout', $sessions, 'cleanup_user_session' );
    }

    public function run() {
        $this->loader->run();
    }

    public function get_plugin_name() {
        return $this->plugin_name;
    }

    public function get_loader() {
        return $this->loader;
    }

    public function get_version() {
        return $this->version;
    }
}