<?php

namespace CoinGateGate;

///Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}


final class EDD_CoinGate_Payments
{

    private static $instance;
    public $gateway_id = 'coingate';
    public $client = null;
    public $doing_ipn = false;

    private function __construct()
    {
        $this->register();

        if (!edd_is_gateway_active($this->gateway_id)) {
            return;
        }

        $this->config();
        $this->includes();
        $this->filters();
        $this->actions();
    }

    /// Get  current instance
    public static function getInstance()
    {
        if (!isset(self::$instance) && !(self::$instance instanceof EDD_CoinGate_Payments)) {
            self::$instance = new EDD_CoinGate_Payments;
        }

        return self::$instance;
    }

    ///Register the payment gateway
    private function register()
    {
        add_filter('edd_payment_gateways', array($this, 'register_gateway'), 1, 1);
    }

    private function config()
    {
        if (!defined('EDD_COINGATE_CLASS_DIR')) {
            $path = trailingslashit(plugin_dir_path(EDD_PLUGIN_FILE)) . 'includes/gateways/libs/coingate';
            define('EDD_COINGATE_CLASS_DIR', trailingslashit($path));
        }
    }

    // Include the CoinGate SDK
    private function includes()
    {
        require_once EDD_COINGATE_CLASS_DIR . 'vendor/autoload.php';
    }


    private function filters()
    {
        add_filter('edd_accepted_payment_icons', array($this, 'register_payment_icon'), 10, 1);
        add_filter('edd_show_gateways', array($this, 'maybe_hide_gateway_select'));

        if (is_admin()) {
            add_filter('edd_settings_sections_gateways', array($this, 'register_gateway_section'), 1, 1);
            add_filter('edd_settings_gateways', array($this, 'register_gateway_settings'), 1, 1);
        }
    }

    ///Removes CC FORM
    public function edd_custom_coingate_cc_form()
    {
        return;
    }


    private function actions()
    {
        add_action('edd_gateway_coingate', array($this, 'process_purchase'));
        add_action('init', array($this, 'process_ipn'));
        add_action('edd_coingate_cc_form', 'edd_custom_coingate_cc_form');
        add_action('edd_gateway_coingate', array($this, 'process_purchase'));
    }

    /// Show an error message on checkout if CoinGate is enabled but not setup.
    public function check_config()
    {
        $is_enabled = edd_is_gateway_active($this->gateway_id);
        if ((!$is_enabled || false === $this->is_setup()) && 'coingate' == edd_get_chosen_gateway()) {
            edd_set_error('coingate_gateway_not_configured',
                __('There is an error with the CoinGate Payments configuration.', 'easy-digital-downloads'));
        }
    }

    ///Register the gateway
    public function register_gateway($gateways)
    {
        $default_coingate_info = array(
            $this->gateway_id => array(
                'admin_label' => __('CoinGate', 'easy-digital-downloads'),
                'checkout_label' => __('Cryptocurrencies via CoinGate (more than 50 supported)',
                    'easy-digital-downloads'),
                'supports' => array(),
            ),
        );

        $default_coingate_info = apply_filters('edd_register_coingate_gateway', $default_coingate_info);
        $gateways = array_merge($gateways, $default_coingate_info);

        return $gateways;
    }

    public function register_payment_icon($payment_icons)
    {
        $payment_icons[plugins_url('libs/coingate/bitcoin.png', __FILE__)] = "CoinGate";

        return $payment_icons;
    }

    ///Register the payment gateways setting section
    public function register_gateway_section($gateway_sections)
    {
        $gateway_sections['coingate'] = __('CoinGate Payments', 'easy-digital-downloads');

        return $gateway_sections;
    }


//Register the gateway settings
    public function register_gateway_settings($gateway_settings)
    {
        $default_coingate_settings = array(
            'coingate' => array(
                'id' => 'coingate',
                'name' => '<strong>' . __('CoinGate Payment Gateway Settings', 'easy-digital-downloads') . '</strong>',
                'type' => 'header',
            ),
            'coingate_api_auth_token' => array(
                'id' => 'coingate_api_auth_token',
                'name' => __('API Auth Token', 'easy-digital-downloads'),
                'desc' => __('CoinGate API Auth Token received upon creating merchant app at <a href="https://coingate.com/" target="_blank"> CoinGate. </a>',
                    'easy-digital-downloads'),
                'type' => 'text',
                'size' => 'large',
            ),
            'coingate_receive_currency' => array(
                'id' => 'coingate_receive_currency',
                'name' => __('Payout Currency', 'easy-digital-downloads'),
                'desc' => __('Choose the currency in which your payouts will be made (BTC, EUR or USD). For real-time EUR or USD settlements, you must verify as a merchant on <a href="https://coingate.com/" target="_blank"> CoinGate. </a>',
                    'easy-digital-downloads'),
                'type' => 'select',
                'options' => array(
                    'BTC' => __('Bitcoin (฿)', 'easy-digital-downloads'),
                    'USDT' => __('USDT (₮)', 'easy-digital-downloads'),
                    'EUR' => __('Euros (€)', 'easy-digital-downloads'),
                    'USD' => __('U.S. Dollars ($)', 'easy-digital-downloads'),
                    'DO_NOT_CONVERT' => __('Do not convert', 'easy-digital-downloads')
                ),
            ),
        );

        $default_coingate_settings = apply_filters('edd_default_coingate_settings', $default_coingate_settings);
        $gateway_settings['coingate'] = $default_coingate_settings;

        return $gateway_settings;
    }

    public function process_purchase($purchase_data)
    {
        global $edd_options;

        $api_auth_token = edd_get_option('coingate_api_auth_token', '');
        $receive_currency = edd_get_option('coingate_receive_currency', '');
        $ipn_url = trailingslashit(home_url()) . '?edd-listener=CPIPN';
        $success_url = add_query_arg('payment-confirmation', $this->gateway_id,
            get_permalink($edd_options['success_page']));

        $payment_id = edd_insert_payment($purchase_data);

        $order_params = [
            'order_id' => $payment_id,
            'price_amount' => $purchase_data['price'],
            'price_currency' => edd_get_currency(),
            'receive_currency' => $receive_currency,
            'cancel_url' => edd_get_failed_transaction_uri(),
            'callback_url' => $ipn_url,
            'success_url' => $success_url,
            'title' => "Order ID: " . $payment_id,
            'description' => $purchase_data['cart_details'][0]['name'],
            'token' => $api_auth_token,
        ];

        $auth = ['environment' => edd_is_test_mode() ? "sandbox" : "live", 'auth_token' => $api_auth_token];

        try {
            $order = \CoinGate\CoinGate::request('/orders', 'POST', $order_params, $auth);

            if (isset($order['payment_url'])) {
                wp_redirect($order['payment_url']);
            } else {
                edd_set_error('coingate_error',
                    __("Something went wrong. Please contact the seller", 'easy-digital-downloads'));
                edd_send_back_to_checkout('?payment-mode=coingate');
            }

        } catch (\Exception $e) {

            edd_set_error('coingate_error', __($e->getMessage(), 'easy-digital-downloads'));
            edd_send_back_to_checkout('?payment-mode=coingate');
        }
//      edd_empty_cart();
    }

    /// Process Callback from CoinGate
    public function process_ipn()
    {
        if (!isset($_POST['status'])) {
            return;
        }

        $payment_id = edd_get_purchase_id_by_key($_POST['order_id']);
        $api_auth_token = edd_get_option('coingate_api_auth_token', '');
        $auth = ['environment' => edd_is_test_mode() ? "sandbox" : "live", 'auth_token' => $api_auth_token];

        try {
            $order = \CoinGate\Merchant\Order::find($_POST['id'], [], $auth);

        } catch (\Exception $exception) {
            return;
        }

        $this->doing_ipn = true;

        switch ($order->status) {
            case 'new':
                edd_update_payment_status($order->order_id, "pending");
                break;
            case 'pending':
                edd_update_payment_status($order->order_id, "pending");
                break;
            case 'confirming':
                edd_update_payment_status($order->order_id, "pending");
                break;
            case 'paid':
                edd_update_payment_status($order->order_id, "complete");
                break;
            case 'invalid':
                edd_update_payment_status($order->order_id, "failed");
                break;
            case 'expired':
                edd_update_payment_status($order->order_id, "abandoned");
                break;
            case 'canceled':
                edd_update_payment_status($order->order_id, "cancelled");
                break;
            case 'refunded':
                edd_update_payment_status($order->order_id, "refunded");
                break;
        }
    }
}

function EDD_CoinGate()
{
    return EDD_CoinGate_Payments::getInstance();
}

EDD_CoinGate();

