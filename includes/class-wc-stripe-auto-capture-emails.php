<?php
class WC_Stripe_Auto_Capture_Emails {

    private static $instance;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('wc_stripe_auto_capture_success', array($this, 'send_capture_notification'), 10, 2);
    }

    public function send_capture_notification($order, $response) {
        $settings = WC_Stripe_Auto_Capture_Core::get_instance()->get_settings();

        if ('yes' !== $settings['enable_email_notification']) {
            return;
        }

        $email = apply_filters('wc_stripe_auto_capture_notification_email', $settings['notification_email'], $order);
        $from_name = apply_filters('wc_stripe_auto_capture_from_name', $settings['from_email_name'], $order);
        
        $domain = wp_parse_url(home_url(), PHP_URL_HOST);
        $from_email = apply_filters('wc_stripe_auto_capture_from_email', 'noreply@' . $domain, $order);

        $subject = str_replace(
            '{order_number}',
            $order->get_order_number(),
            apply_filters('wc_stripe_auto_capture_email_subject', $settings['email_subject'], $order)
        );

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            sprintf('From: %s <%s>', $from_name, $from_email),
        );

        $order_url = admin_url('post.php?post=' . $order->get_id() . '&action=edit');
        $order_date = current_time('F j, Y, g:i a');
        $order_total = $order->get_formatted_order_total();

        $message = apply_filters('wc_stripe_auto_capture_email_content', "
        <html>
        <head>
          <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 8px; }
            h2 { color: #1a73e8; }
            .info { background: #f9f9f9; padding: 10px 15px; border-left: 4px solid #1a73e8; margin-top: 10px; }
            a { color: #1a73e8; text-decoration: none; }
          </style>
        </head>
        <body>
        <div class='container'>
            <h2>" . __('Stripe Payment Captured', 'wc-stripe-auto-capture') . "</h2>
            <p>" . __('This is to notify you that a Stripe payment has been <strong>automatically captured</strong> for the following WooCommerce order:', 'wc-stripe-auto-capture') . "</p>

            <div class='info'>
              <p><strong>" . __('Order Number:', 'wc-stripe-auto-capture') . "</strong> #{$order->get_order_number()}</p>
              <p><strong>" . __('Total:', 'wc-stripe-auto-capture') . "</strong> {$order_total}</p>
              <p><strong>" . __('Payment Method:', 'wc-stripe-auto-capture') . "</strong> Stripe</p>
              <p><strong>" . __('Date Captured:', 'wc-stripe-auto-capture') . "</strong> {$order_date}</p>
            </div>

            <p>" . sprintf(__('You can <a href="%s">view the order in the admin panel here</a>.', 'wc-stripe-auto-capture'), $order_url) . "</p>

            <p style='margin-top:20px;'>" . __('Regards,', 'wc-stripe-auto-capture') . "<br></p>
        </div>
        </body>
        </html>
        ", $order);

        wp_mail($email, $subject, $message, $headers);
    }
}