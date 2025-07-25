<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Secure_AI_Agent_Access_i18n {

    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'secure-ai-agent-access',
            false,
            dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
        );
    }
}