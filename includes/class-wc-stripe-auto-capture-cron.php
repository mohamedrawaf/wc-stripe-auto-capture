<?php
class WC_Stripe_Auto_Capture_Cron {

    private static $instance;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('wc_capture_stripe_authorized_orders', array($this, 'run_capture_process'));
    }

    public function schedule_cron() {
        if (!wp_next_scheduled('wc_capture_stripe_authorized_orders')) {
            $schedule = apply_filters('wc_stripe_auto_capture_cron_schedule', 'daily');
            wp_schedule_event(time(), $schedule, 'wc_capture_stripe_authorized_orders');
        }
    }

    public function clear_cron() {
        wp_clear_scheduled_hook('wc_capture_stripe_authorized_orders');
    }

    public function run_capture_process() {
        WC_Stripe_Auto_Capture_Core::get_instance()->capture_authorized_orders();
    }
}