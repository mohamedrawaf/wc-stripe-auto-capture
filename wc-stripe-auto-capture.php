<?php
/**
 * Plugin Name: WooCommerce Stripe Auto Capture
 * Description: Automatically captures authorized Stripe payments before they expire. Works with WooCommerce when Stripe is set to manual capture mode.
 * Version: 1.2.0
 * Author: abdelrawaf
 * Author URI: https://www.upwork.com/freelancers/~01e0ebea64e80eb1de
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.6
 * Requires PHP: 7.2
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

defined('ABSPATH') || exit;

// Define plugin constants
define('WC_STRIPE_AUTO_CAPTURE_VERSION', '1.2.0');
define('WC_STRIPE_AUTO_CAPTURE_PLUGIN_FILE', __FILE__);
define('WC_STRIPE_AUTO_CAPTURE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WC_STRIPE_AUTO_CAPTURE_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once WC_STRIPE_AUTO_CAPTURE_PLUGIN_DIR . 'includes/class-wc-stripe-auto-capture-core.php';
require_once WC_STRIPE_AUTO_CAPTURE_PLUGIN_DIR . 'includes/class-wc-stripe-auto-capture-admin.php';
require_once WC_STRIPE_AUTO_CAPTURE_PLUGIN_DIR . 'includes/class-wc-stripe-auto-capture-cron.php';
require_once WC_STRIPE_AUTO_CAPTURE_PLUGIN_DIR . 'includes/class-wc-stripe-auto-capture-emails.php';

/**
 * Initialize the plugin
 */
function wc_stripe_auto_capture_init() {
    WC_Stripe_Auto_Capture_Core::get_instance();
    WC_Stripe_Auto_Capture_Admin::get_instance();
    WC_Stripe_Auto_Capture_Cron::get_instance();
    WC_Stripe_Auto_Capture_Emails::get_instance();
}
add_action('plugins_loaded', 'wc_stripe_auto_capture_init');

/**
 * Plugin activation
 */
function wc_stripe_auto_capture_activate() {
    // Schedule cron on activation
    WC_Stripe_Auto_Capture_Cron::get_instance()->schedule_cron();
}
register_activation_hook(WC_STRIPE_AUTO_CAPTURE_PLUGIN_FILE, 'wc_stripe_auto_capture_activate');

/**
 * Plugin deactivation
 */
function wc_stripe_auto_capture_deactivate() {
    // Clear cron on deactivation
    WC_Stripe_Auto_Capture_Cron::get_instance()->clear_cron();
}
register_deactivation_hook(WC_STRIPE_AUTO_CAPTURE_PLUGIN_FILE, 'wc_stripe_auto_capture_deactivate');