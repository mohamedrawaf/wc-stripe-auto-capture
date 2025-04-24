=== WooCommerce Stripe Auto Capture ===
Contributors: abdelrawaf
Donate link: https://www.upwork.com/freelancers/~01e0ebea64e80eb1de
Tags: woocommerce, stripe, payment, capture, automatic
Requires at least: 5.6
Tested up to: 6.3
Stable tag: 1.2.0
Requires PHP: 7.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically captures authorized Stripe payments before they expire. Works with WooCommerce when Stripe is set to manual capture mode.

== Description ==

This plugin helps WooCommerce stores using Stripe in manual capture mode avoid losing revenue from uncaptured payments.
Normally, Stripe cancels these payments after 7 days if they’re not manually captured.

The plugin solves this by automatically capturing eligible payments before they expire—by default on the 6th day, but this timing can be adjusted.
It uses a daily cron job to check for "on-hold" orders paid via Stripe, and securely captures them through the Stripe API.

It updates the order status, logs the outcome, and can send optional email notifications.
Built with performance and reliability in mind, it handles large volumes of orders efficiently, avoids duplicate captures, and keeps everything running smoothly without manual effort.

= Key Features =
- Automatically captures payments before they expire
- Configurable number of days before expiration to capture
- Email notifications for successful captures
- Works with WooCommerce Stripe Gateway in manual capture mode
- Extensive filter system for customization
- Clean, object-oriented codebase

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/wc-stripe-auto-capture` directory
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Configure settings under WooCommerce > Stripe Auto Capture

== Available Hooks ==

= Filters =
1. `wc_stripe_auto_capture_days_before` - Adjust days before capture (default: 6)
2. `wc_stripe_auto_capture_order_args` - Modify order query arguments
3. `wc_stripe_auto_capture_skip_order` - Skip specific orders
4. `wc_stripe_auto_capture_payment_intent_id` - Filter payment intent ID
5. `wc_stripe_auto_capture_pre_capture` - Modify capture request
6. `wc_stripe_auto_capture_response` - Filter capture response
7. `wc_stripe_auto_capture_sanitize_settings` - Filter settings before save
8. `wc_stripe_auto_capture_cron_schedule` - Change cron schedule
9. `wc_stripe_auto_capture_notification_email` - Filter notification email
10. `wc_stripe_auto_capture_from_name` - Filter from name
11. `wc_stripe_auto_capture_from_email` - Filter from email
12. `wc_stripe_auto_capture_email_subject` - Filter email subject
13. `wc_stripe_auto_capture_email_content` - Filter email content

= Actions =
1. `wc_stripe_auto_capture_success` - After successful capture
2. `wc_stripe_auto_capture_failed` - When capture fails

== Examples ==

= Change cron schedule =
add_filter('wc_stripe_auto_capture_cron_schedule', function() {
    return 'twicedaily';
});

= Modify email subject =
add_filter('wc_stripe_auto_capture_email_subject', function($subject, $order) {
    return 'Auto Capture: Order #' . $order->get_order_number();
}, 10, 2);

== Screenshots ==
1. Settings screen

== Changelog ==

= 1.2.0 =
- Restructured into multiple files
- Added filter/action system
- Improved settings validation
- Added email subject customization

= 1.1.0 =
- Added settings page
- Configurable notifications
- Code improvements

= 1.0.0 =
- Initial release