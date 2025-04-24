<?php
class WC_Stripe_Auto_Capture_Admin {

    private static $instance;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_init', array($this, 'check_dependencies'));
    }

    public function check_dependencies() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return false;
        }

        if (!class_exists('WC_Stripe_Helper') || !class_exists('WC_Stripe_API')) {
            add_action('admin_notices', array($this, 'stripe_missing_notice'));
            return false;
        }

        return true;
    }

    public function woocommerce_missing_notice() {
        echo '<div class="error"><p>';
        printf(
            __('WooCommerce Stripe Auto Capture requires WooCommerce to be installed and active. %s', 'wc-stripe-auto-capture'),
            '<a href="' . esc_url(admin_url('plugin-install.php?s=woocommerce&tab=search&type=term')) . '">' . __('Install WooCommerce now', 'wc-stripe-auto-capture') . '</a>'
        );
        echo '</p></div>';
    }

    public function stripe_missing_notice() {
        echo '<div class="error"><p>';
        printf(
            __('WooCommerce Stripe Auto Capture requires WooCommerce Stripe Gateway to be installed and active. %s', 'wc-stripe-auto-capture'),
            '<a href="' . esc_url(admin_url('plugin-install.php?s=woocommerce+stripe&tab=search&type=term')) . '">' . __('Install WooCommerce Stripe Gateway now', 'wc-stripe-auto-capture') . '</a>'
        );
        echo '</p></div>';
    }

    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            __('Stripe Auto Capture', 'wc-stripe-auto-capture'),
            __('Stripe Auto Capture', 'wc-stripe-auto-capture'),
            'manage_options',
            'wc-stripe-auto-capture',
            array($this, 'render_settings_page')
        );
    }

    public function register_settings() {
        register_setting('wc_stripe_auto_capture_settings', 'wc_stripe_auto_capture_settings', array($this, 'sanitize_settings'));

        add_settings_section(
            'wc_stripe_auto_capture_general',
            __('General Settings', 'wc-stripe-auto-capture'),
            array($this, 'render_general_section'),
            'wc-stripe-auto-capture'
        );

        add_settings_field(
            'days_before_capture',
            __('Days Before Capture', 'wc-stripe-auto-capture'),
            array($this, 'render_days_before_capture_field'),
            'wc-stripe-auto-capture',
            'wc_stripe_auto_capture_general'
        );

        add_settings_field(
            'enable_email_notification',
            __('Enable Email Notification', 'wc-stripe-auto-capture'),
            array($this, 'render_enable_email_notification_field'),
            'wc-stripe-auto-capture',
            'wc_stripe_auto_capture_general'
        );

        add_settings_field(
            'notification_email',
            __('Notification Email', 'wc-stripe-auto-capture'),
            array($this, 'render_notification_email_field'),
            'wc-stripe-auto-capture',
            'wc_stripe_auto_capture_general'
        );

        add_settings_field(
            'from_email_name',
            __('From Email Name', 'wc-stripe-auto-capture'),
            array($this, 'render_from_email_name_field'),
            'wc-stripe-auto-capture',
            'wc_stripe_auto_capture_general'
        );

        add_settings_field(
            'email_subject',
            __('Email Subject', 'wc-stripe-auto-capture'),
            array($this, 'render_email_subject_field'),
            'wc-stripe-auto-capture',
            'wc_stripe_auto_capture_general'
        );
    }

    public function sanitize_settings($input) {
        $output = array();

        if (isset($input['days_before_capture'])) {
            $output['days_before_capture'] = absint($input['days_before_capture']);
            if ($output['days_before_capture'] < 1 || $output['days_before_capture'] > 6) {
                $output['days_before_capture'] = 6;
                add_settings_error('wc_stripe_auto_capture_settings', 'invalid_days', __('Days before capture must be between 1 and 6.', 'wc-stripe-auto-capture'));
            }
        }

        $output['enable_email_notification'] = isset($input['enable_email_notification']) ? 'yes' : 'no';

        if (isset($input['notification_email'])) {
            $output['notification_email'] = sanitize_email($input['notification_email']);
            if (!is_email($output['notification_email'])) {
                $output['notification_email'] = get_option('admin_email');
                add_settings_error('wc_stripe_auto_capture_settings', 'invalid_email', __('Invalid notification email address.', 'wc-stripe-auto-capture'));
            }
        }

        if (isset($input['from_email_name'])) {
            $output['from_email_name'] = sanitize_text_field($input['from_email_name']);
        }

        if (isset($input['email_subject'])) {
            $output['email_subject'] = sanitize_text_field($input['email_subject']);
        }

        return apply_filters('wc_stripe_auto_capture_sanitize_settings', $output, $input);
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        settings_errors('wc_stripe_auto_capture_settings');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('wc_stripe_auto_capture_settings');
                do_settings_sections('wc-stripe-auto-capture');
                submit_button(__('Save Settings', 'wc-stripe-auto-capture'));
                ?>
            </form>
        </div>
        <?php
    }

    public function render_general_section() {
        echo '<p>' . esc_html__('Configure the automatic capture of Stripe payments.', 'wc-stripe-auto-capture') . '</p>';
    }

    public function render_days_before_capture_field() {
        $settings = WC_Stripe_Auto_Capture_Core::get_instance()->get_settings();
        ?>
        <input type="number" min="1" max="6" name="wc_stripe_auto_capture_settings[days_before_capture]" value="<?php echo esc_attr($settings['days_before_capture']); ?>" class="small-text" />
        <p class="description"><?php esc_html_e('Number of days before payment expiration to capture the payment (Stripe payments expire after 7 days).', 'wc-stripe-auto-capture'); ?></p>
        <?php
    }

    public function render_enable_email_notification_field() {
        $settings = WC_Stripe_Auto_Capture_Core::get_instance()->get_settings();
        ?>
        <label>
            <input type="checkbox" name="wc_stripe_auto_capture_settings[enable_email_notification]" value="1" <?php checked('yes', $settings['enable_email_notification']); ?> />
            <?php esc_html_e('Send email notification when payment is captured', 'wc-stripe-auto-capture'); ?>
        </label>
        <?php
    }

    public function render_notification_email_field() {
        $settings = WC_Stripe_Auto_Capture_Core::get_instance()->get_settings();
        ?>
        <input type="email" name="wc_stripe_auto_capture_settings[notification_email]" value="<?php echo esc_attr($settings['notification_email']); ?>" class="regular-text" />
        <p class="description"><?php esc_html_e('Email address to send capture notifications to.', 'wc-stripe-auto-capture'); ?></p>
        <?php
    }

    public function render_from_email_name_field() {
        $settings = WC_Stripe_Auto_Capture_Core::get_instance()->get_settings();
        ?>
        <input type="text" name="wc_stripe_auto_capture_settings[from_email_name]" value="<?php echo esc_attr($settings['from_email_name']); ?>" class="regular-text" />
        <p class="description"><?php esc_html_e('Name to use in the "From" field of notification emails.', 'wc-stripe-auto-capture'); ?></p>
        <?php
    }

    public function render_email_subject_field() {
        $settings = WC_Stripe_Auto_Capture_Core::get_instance()->get_settings();
        ?>
        <input type="text" name="wc_stripe_auto_capture_settings[email_subject]" value="<?php echo esc_attr($settings['email_subject']); ?>" class="regular-text" />
        <p class="description"><?php esc_html_e('Subject for the capture notification email. Use {order_number} as placeholder.', 'wc-stripe-auto-capture'); ?></p>
        <?php
    }
}