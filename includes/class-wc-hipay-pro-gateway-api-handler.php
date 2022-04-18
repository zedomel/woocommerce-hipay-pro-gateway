<?php
/**
 * Class WC_Gateway_HiPay_API_Handler file.
 *
 * @package WooCommerce\Gateways
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once( dirname( __FILE__ ) . '/abstracts/class-wc-hipay-pro-ws.php');

/**
 * Handles Refunds and other API requests such as capture.
 *
 * @since 3.0.0
 */
class WC_Gateway_HiPay_API_Handler extends WC_HiPay_Professional_WS {

	/**
	 * API Username
	 *
	 * @var string
	 */
	public static $api_username;

	/**
	 * API Password
	 *
	 * @var string
	 */
	public static $api_password;


  /**
	 * API Website ID
	 *
	 * @var string
	 */
	public static $api_websiteId;

  public static function get_capture_request( $order, $amount = '' ) {
    $request = array (
      'wsLogin'               => self::$api_username,
      'wsPassword'            => self::$api_password,
      'transactionPublicId'   => $order->get_transaction_id()
    );
    return apply_filters( 'woocommerce_hipay_capture_request', $request, $order, $amount );
  }

  public static function get_cancel_request( $order ) {
    $request = array (
      'wsLogin'               => self::$api_username,
      'wsPassword'            => self::$api_password,
      'websiteId'             => self::$api_websiteId,
      'transactionPublicId'   => $order->get_transaction_id()
    );
    return apply_filters( 'woocommerce_hipay_cancel_request', $request, $order );
  }

  public static function get_refund_request( $order, $amount = 0 ) {
    $request = array (
      'wsLogin'               => self::$api_username,
      'wsPassword'            => self::$api_password,
      'transactionPublicId'   => $order->get_transaction_id(),
			'amount'								=> $amount > 0 ? wc_format_decimal( $amount ) : wc_format_decimal( $order->get_total() )
    );
    return apply_filters( 'woocommerce_hipay_refund_request', $request, $order, $amount );
  }

	public static function generate( $order, $params ) {
		$client = parent::get_client( '/soap/payment-v2', true );

		if ( ! $client ){
			return new WP_Error( 'hipay-pro-api', 'Error initalizing WS client' );
		}

		try {
			$result = $client->generate( $params );
			unset($client);
		} catch( Exception $e ){
			WC_HiPay_Professional_Gateway::log( 'Payment exception: ' . $e->getMessage() );
			return new WP_Error( 'hipay-pro-api', __( 'An error occurred while trying to contact the HiPay WS', 'wc-hipay-pro' ) );
		}

		WC_HiPay_Professional_Gateway::log( 'Payment Response: ' . wc_print_r( $result, true ) );

		if ( empty( $result ) || ! isset( $result->generateResult ) ) {
			return new WP_Error( 'hipay-pro-api', __( 'Empty Response', 'wc-hipay-pro' ) );
		}

		return (object) $result->generateResult;
	}

  /**
	 * Capture an authorization.
	 *
	 * @param  WC_Order $order Order object.
	 * @param  float    $amount Amount.
	 * @return object Either an object of name value pairs for a success, or a WP_ERROR object.
	 */
	public static function do_capture( $order, $amount = null ) {
    $client = parent::get_client( '/soap/transaction-v2', true );

    if ( ! $client ){
      return new WP_Error( 'hipay-pro-api', 'Error initalizing WS client' );
    }

    try {
      $result = $client->confirm( array(
				'parameters'   =>  self::get_capture_request( $order, $amount )
      ) );
      unset($client);
    } catch( Exception $e ){
      WC_HiPay_Professional_Gateway::log( 'DoCapture exception: ' . $e->getMessage() );
      return new WP_Error( 'hipay-pro-api', __( 'An error occurred while trying to contact the HiPay WS', 'wc-hipay-pro' ) );
    }

		WC_HiPay_Professional_Gateway::log( 'DoCapture Response: ' . wc_print_r( $result, true ) );

		if ( empty( $result ) || ! isset( $result->confirmResult ) ) {
			return new WP_Error( 'hipay-pro-api', __( 'Empty Response', 'wc-hipay-pro' ) );
		}

		return (object) $result->confirmResult;
	}

  public static function do_cancellation( $order ){
    $client = parent::get_client( '/soap/transaction-v2', true );

    if ( ! $client ){
      return new WP_Error( 'hipay-pro-api', 'Error initalizing WS client' );
    }

    try {
      $result = $client->cancel( array(
				'parameters'   =>  self::get_cancel_request( $order )
      ) );
      unset($client);
    } catch( Exception $e ){
      WC_HiPay_Professional_Gateway::log( 'DoCancellation exception: ' . $e->getMessage() );
      return new WP_Error( 'hipay-pro-api', __( 'An error occurred while trying to contact the HiPay WS', 'wc-hipay-pro' ) );
    }

		WC_HiPay_Professional_Gateway::log( 'DoCancellation Response: ' . wc_print_r( $result, true ) );

		if ( empty( $result ) || ! isset( $result->cancelResult ) ) {
			return new WP_Error( 'hipay-pro-api', __( 'Empty Response', 'wc-hipay-pro' ) );
		}

		return (object) $result->cancelResult;
  }

  /**
	 * Refund an order via HiPay.
	 *
	 * @param  WC_Order $order Order object.
	 * @param  float    $amount Refund amount.
	 * @param  string   $reason Refund reason.
	 * @return object Either an object of name value pairs for a success, or a WP_ERROR object.
	 */
	public static function refund_transaction( $order, $amount = null, $reason = '' ) {
    $client = parent::get_client( '/soap/refund-v2', true );

    if ( ! $client ){
      return new WP_Error( 'hipay-pro-api', 'Error initalizing WS client' );
    }

    try {
      $result = $client->card( array(
				'parameters'   =>  self::get_refund_request( $order, $amount, $reason )
      ) );
      unset($client);
    } catch( Exception $e ){
      WC_HiPay_Professional_Gateway::log( 'RefundTransaction exception: ' . $e->getMessage() );
      return new WP_Error( 'hipay-pro-api', __( 'An error occurred while trying to contact the HiPay WS', 'wc-hipay-pro' ) );
    }

		WC_HiPay_Professional_Gateway::log( 'RefundTransaction Response: ' . wc_print_r( $result, true ) );

		if ( empty( $result ) || ! isset( $result->cardResult ) ) {
			return new WP_Error( 'hipay-pro-api', __( 'Empty Response', 'wc-hipay-pro' ) );
		}

		return (object) $result->cardResult;
	}
}
