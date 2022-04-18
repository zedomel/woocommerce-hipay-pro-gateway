<?php
/**
 * Class WC_Gateway_HiPay_Request file.
 *
 * @package WooCommerce\Gateways
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Generates requests to send to HiPay
 */
class WC_Gateway_HiPay_Request
{

    /**
     * Stores line items to send to HiPay.
     *
     * @var array
     */
    protected $line_items = array();

    /**
     * Pointer to gateway making the request.
     *
     * @var WC_HiPay_Professional_Gateway
     */
    protected $gateway;

    /**
     * Endpoint for requests from HiPay.
     *
     * @var string
     */
    protected $notify_url;

    /**
     * Endpoint for requests to HiPay.
     *
     * @var string
     */
    protected $endpoint;

    /**
     * Constructor.
     *
     * @param WC_HiPay_Professional_Gateway $gateway HiPay gateway object.
     */
    public function __construct($gateway)
    {
        $this->gateway    = $gateway;
        $this->notify_url = WC()->api_request_url('WC_HiPay_Professional_Gateway');
    }

    /**
     * Get the HiPay request URL for an order.
     *
     * @param  WC_Order $order Order object.
     * @param  bool     $sandbox Whether to use sandbox mode or not.
     * @return string
     */
    public function get_request_url($order)
    {
        $this->gateway->set_locale($order);
        $this->gateway->init_api();

        $params = $this->get_payment_request_params($order);
        $result = WC_Gateway_HiPay_API_Handler::generate($order, array( 'parameters' =>
                    $params
                ));

        if (is_wp_error($result)) {
            WC_HiPay_Professional_Gateway::log(sprintf('Order could not be created: %s', $result->get_error_message()));
            wc_add_notice(__('Payment error:', 'wc-hipay-pro') . $result->get_error_message(), 'error');
            return;
        }

        if (isset($result->code)) {
            if (0 === $result->code && ! empty($result->redirectUrl)) {
                WC_HiPay_Professional_Gateway::log(sprintf('Redirecting to HiPay payment page: %1$s', $result->redirectUrl));
                return $result->redirectUrl;
            } else {
                WC_HiPay_Professional_Gateway::log(sprintf('No redirect URL in hipay response. Code: %1$d - Message: %2$s', $result->code, $result->description));
                wc_add_notice( __('Payment error:', 'wc-hipay-pro') . $result->description, 'error');
                return;
            }
        }

        WC_HiPay_Professional_Gateway::log(sprintf('Hipay Request Error.'));
        wc_add_notice(__('Payment error:', 'wc-hipay-pro'), 'error');
        return;
    }

    public function get_payment_request_params($order)
    {
        $params = array(
      'wsLogin'             => WC_Gateway_HiPay_API_Handler::$api_username,
      'wsPassword'          => WC_Gateway_HiPay_API_Handler::$api_password,
      'websiteId'           => WC_Gateway_HiPay_API_Handler::$api_websiteId,
      'categoryId'          => $this->gateway->hipay_cat,
      'currency'            => $order->get_currency(),
      'amount'              => $order->get_total(),
      'rating'              => $this->gateway->hipay_rating,
      'locale'              => $this->gateway->locale,
      'customerIpAddress'   => WC_Geolocation::get_ip_address(),
      'executionDate'       => date('Y-m-dTH:i:s'),
      'manualCapture'       => $this->gateway->hipay_capture,
      'description'         => $this->get_order_description($order),
      'customerEmail'       => $order->get_billing_email(),

      //URLs
      'urlCallback'         => $this->notify_url,
      'urlAccept'           => $this->gateway->get_return_url($order),
      'urlDecline'          => esc_url_raw($order->get_cancel_order_url_raw()),
      'urlCancel'           => esc_url_raw($order->get_cancel_order_url_raw()),
      'urlLogo'             => $this->gateway->hipay_logo !== '' ? $this->gateway->hipay_logo : '',

      'merchantReference'   => $order->get_id(),
      'merchantComment'     => $this->gateway->hipay_order_info,
      'emailCallback'       => $this->gateway->receiver_email,

      'freeData'            => $this->get_freedata($order),
    );

      return apply_filters('wc_hipay_new_order_request_params', $params, $order);
    }

    public function get_order_description($order)
    {
        return apply_filters('wc_hipay_pro_order_description', sprintf(__('Order: #%1$d', 'wc-hipay-pro'), $order->get_id()), $order);
    }

    public function get_freedata($order)
    {
        $salt = get_option( 'wc_hipay_pro_salt' );
        $ch = sha1($salt . $order->get_id());
        $freedata = array(
          array( 'key'  => 'order_key', 'value'   => $order->get_order_key() ),
          array( 'key'  => 'ch',        'value'   => $ch)
        );
        return apply_filters('wc_hipay_pro_free_data', $freedata, $order);
    }
}
