<?php
/**
 * Plugin Name: Secure AI Agent Access
 * Plugin URI: https://github.com/nathanonn/secure-ai-agent-access
 * Description: Provides administrators with a secure method to grant AI agents temporary, controlled access to their WordPress sites using single-use magic links.
 * Version: 1.0.0
 * Author: Nathan Onn
 * Author URI: https://www.nathanonn.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: secure-ai-agent-access
 * Domain Path: /languages
 * Network: true
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

define( 'SAIA_VERSION', '1.0.0' );
define( 'SAIA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SAIA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SAIA_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once SAIA_PLUGIN_DIR . 'includes/class-secure-ai-agent-access.php';
require_once SAIA_PLUGIN_DIR . 'includes/class-secure-ai-agent-access-activator.php';
require_once SAIA_PLUGIN_DIR . 'includes/class-secure-ai-agent-access-deactivator.php';

register_activation_hook( __FILE__, array( 'Secure_AI_Agent_Access_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Secure_AI_Agent_Access_Deactivator', 'deactivate' ) );

function saia_run_plugin() {
    $plugin = new Secure_AI_Agent_Access();
    $plugin->run();
}
saia_run_plugin();