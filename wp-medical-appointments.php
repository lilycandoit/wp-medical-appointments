<?php
/**
 * Plugin Name: WP Medical Appointments
 * Description: Manage doctors and patient appointments for clinics.
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: wp-medical-appointments
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants
define( 'WPMA_VERSION', '1.0.0' );
define( 'WPMA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPMA_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Load plugin components
require_once WPMA_PLUGIN_DIR . 'includes/post-types.php';
require_once WPMA_PLUGIN_DIR . 'includes/admin-pages.php';
require_once WPMA_PLUGIN_DIR . 'includes/shortcodes.php';
require_once WPMA_PLUGIN_DIR . 'includes/form-handler.php';

/**
 * Activation hook - flush rewrite rules for custom post types
 */
function wpma_activate() {
    wpma_register_post_types();
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'wpma_activate' );

/**
 * Deactivation hook - clean up rewrite rules
 */
function wpma_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'wpma_deactivate' );
