<?php
class WC_Stripe_Auto_Capture_Core {

    private static $instance;
    protected $settings;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->settings = $this->get_settings();
    }

    public function get_settings() {
        $defaults = array(
            'days_before_capture' => 6,
            'enable_email_notification' => 'yes',
            'notification_email' => get_option('admin_email'),
            'from_email_name' => get_bloginfo('name') . ' Payments',
            'email_subject' => __('Stripe Payment Captured Automatically for Order #{order_number}', 'wc-stripe-auto-capture'),
        );

        $settings = get_option('wc_stripe_auto_capture_settings', array());
        return wp_parse_args($settings, $defaults);
    }

    public function capture_authorized_orders() {
        if (!class_exists('WC_Stripe_Helper') || !class_exists('WC_Stripe_API')) {
            error_log('Stripe classes not available');
            return;
        }

        $days_before = apply_filters('wc_stripe_auto_capture_days_before', $this->settings['days_before_capture']);

        // Get orders with applied filters
        $order_args = apply_filters('wc_stripe_auto_capture_order_args', array(
            'status' => 'on-hold',
            'limit' => -1,
            'date_query' => array(
                array(
                    'after' => gmdate('Y-m-d H:i:s', strtotime('-' . ($days_before + 2) . ' days')),
                    'before' => gmdate('Y-m-d H:i:s', strtotime('-' . $days_before . ' days')),
                    'inclusive' => true,
                ),
            ),
        ));

        $orders = wc_get_orders($order_args);

        foreach ($orders as $order) {
            if (apply_filters('wc_stripe_auto_capture_skip_order', false, $order)) {
                continue;
            }

            if ($order->get_payment_method() !== 'stripe') {
                continue;
            }

            $this->process_order_capture($order);
        }
    }

    protected function process_order_capture($order) {
        error_log('Processing order: ' . $order->get_id());

        $payment_intent_id = apply_filters('wc_stripe_auto_capture_payment_intent_id', 
            $order->get_meta('_stripe_intent_id') ?: 
            $order->get_meta('_stripe_source_id') ?: 
            $order->get_meta('_transaction_id'),
            $order
        );

        if (empty($payment_intent_id)) {
            $order->add_order_note(__('No Stripe payment intent found', 'wc-stripe-auto-capture'));
            $order->save();
            error_log('No payment intent found for order ' . $order->get_id());
            return;
        }

        try {
            $response = apply_filters('wc_stripe_auto_capture_pre_capture', null, $payment_intent_id, $order);
            
            if (null === $response) {
                $response = WC_Stripe_API::request(
                    array(),
                    'payment_intents/' . $payment_intent_id . '/capture',
                    'POST'
                );
            }

            $response = apply_filters('wc_stripe_auto_capture_response', $response, $payment_intent_id, $order);

            if (!empty($response->error)) {
                throw new Exception($response->error->message);
            }

            if ($response->status === 'succeeded') {
                $order->payment_complete($response->id);
                $order->add_order_note(sprintf(
                    __('Stripe payment captured automatically successfully. Payment Intent: %s Amount: %s', 'wc-stripe-auto-capture'),
                    $response->id,
                    wc_price($response->amount / 100)
                ));
                $order->save();
                error_log('Successfully captured payment automatically for order ' . $order->get_id());

                do_action('wc_stripe_auto_capture_success', $order, $response);

            } else {
                throw new Exception('Capture failed. Status: ' . $response->status);
            }
        } catch (Exception $e) {
            $order->add_order_note(sprintf(
                __('Stripe capture error: %s', 'wc-stripe-auto-capture'),
                $e->getMessage()
            ));
            $order->save();
            error_log('Capture error for order ' . $order->get_id() . ': ' . $e->getMessage());
            
            do_action('wc_stripe_auto_capture_failed', $order, $e);
        }
    }
}