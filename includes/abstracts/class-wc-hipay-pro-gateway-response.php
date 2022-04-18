<?php

/**
 * Hipay REST API Managert
 *
 * @package REST API Manager
 * @author Jose A. Salim
 * @copyright   Copyright (c) Jose A. Salim
 * @since 1.0
 *
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
#defined( 'ABSPATH' ) || exit;

abstract class WC_Gateway_HiPay_Response {

  /**
	 * Sandbox mode
	 *
	 * @var bool
	 */
	protected $sandbox = false;

  public static $fields = array(
    'operation'   => true,
    'status'      => true,
    'date'        => true,
    'time'        => true,
    'origAmount'  => true,
    'origCurrency'                  => true,
    'idForMerchant'                 => true,
    'emailClient'                   => true,
    'merchantDatas'                 => false,
    'transid'                       => true,
    'is3ds'                         => false,
    'paymentMethod'                 => false,
    'customerCountry'               => false,
    'returnCode'                    => false,
    'returnDescriptionShort'        => false,
    'returnDescriptionLong'         => false
  );

  public function parse_xml_response( $posted ){
    try {
      $xml = new SimpleXMLElement( trim( $posted ) );
    } catch ( Exception $e ){
      return false;
    }

    $result = $xml->result[0];
    $response = array();

    foreach ( self::$fields as $key => $required ) {
      $key = wp_unslash( $key );
      if ( $key == 'merchantDatas' && isset( $result->$key ) ) {
        $response[ $key ] = array();
        foreach ($result->$key->children() as $a_key => $value) {
          if ( preg_match( '#^_aKey_#i', $a_key ) ){
            $key_name = wc_clean( wp_unslash( substr( $a_key, 6 ) ) );
            $response[ $key ][ $key_name ] = wc_clean( wp_unslash( (string) $value ) );
          }
        }
      }
      else {
        if ( ! isset( $result->$key ) && $required ){
          return false;
        }
        else {
          $response[ $key ] = isset( $result->$key ) ? wc_clean( wp_unslash( (string) $result->$key ) ) : '';
        }
      }
    }

    return $response;
  }

  /**
	 * Get the order from the HiPay XML response
	 *
	 * @param  string $response XML Data passed back by HiPay.
	 * @return bool|WC_Order object
	 */
  public function get_hipay_order( $response ) {
    $order_id = intval ( $response['idForMerchant'] );

		if ( ! isset( $response[ 'merchantDatas' ][ 'order_key' ] ) ){
			return false;
		}

		$order_key = $response[ 'merchantDatas' ][ 'order_key' ];
    $order = wc_get_order( $order_id );
    if ( ! $order || $order->get_order_key() !== $order_key ) {
      WC_HiPay_Professional_Gateway::log( 'Order Keys do not match',  'error' );
      return false;
    }

    return $order;
  }

  /**
	 * Complete order, add transaction ID and note.
	 *
	 * @param  WC_Order $order Order object.
	 * @param  string   $txn_id Transaction ID.
	 * @param  string   $note Payment note.
	 */
	protected function payment_complete( $order, $txn_id = '', $note = '' ) {
		$order->add_order_note( $note );
		$order->payment_complete( $txn_id );
		WC()->cart->empty_cart();
	}

  /**
	 * Hold order and add note.
	 *
	 * @param  WC_Order $order Order object.
	 * @param  string   $reason Reason why the payment is on hold.
	 */
	protected function payment_on_hold( $order, $reason = '' ) {
		$order->update_status( 'on-hold', $reason );
		wc_reduce_stock_levels( $order->get_id() );
		WC()->cart->empty_cart();
	}
}
