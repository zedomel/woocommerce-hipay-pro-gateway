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

class WC_Hipay_Order_Actions {

  public function __construct(){
    add_filter( 'woocommerce_order_actions', array( $this, 'add_actions' ) );
  	add_action( 'woocommerce_order_action_wc_order_capture_action', 'WC_Hipay_Order_Actions::capture_order' );
    add_action( 'woocommerce_order_action_wc_order_generate_mb_reference', 'WC_Hipay_Order_Actions::generate_mb_reference' );
    add_action( 'woocommerce_order_action_wc_order_generate_payment_link', 'WC_Hipay_Order_Actions::generate_payment_link' );
  }

  /**
  *
  */
  public function add_actions( $actions ){
     global $theorder;

    //bail if the order has been completed for or this action has been run
    if( $theorder->get_payment_method() === 'hipay_professional' ){             
      // If transaction status is different from authorized bail out
      $status = get_post_meta( $theorder->get_id(), '_hipay_status', true );
      if ( $status === 'authorization' ){
        $actions[ 'wc_order_capture_action' ] = __( 'Confirm transaction (Capture)', 'wc-hipay-pro' );        
      }      
    }
    
    if ( !$theorder->is_paid() ){
      $actions[ 'wc_order_generate_mb_reference' ] = __( 'Generate MB reference', 'wc-hipay-pro');
      $actions[ 'wc_order_generate_payment_link' ] = __( 'Generate HiPay payment link ', 'wc-hipay-pro');
    }
    
    return $actions;
  }

  /**
	*
	*/
	public static function capture_order( $order ){
    if ( $order->get_payment_method() === 'hipay_professional' && get_post_meta( $order->get_id(), '_hipay_status', true ) === 'authorization' ) {
			$hipay_gw = wc_get_payment_gateway_by_order( $order );
			if ( $hipay_gw && method_exists( $hipay_gw, 'capture_payment') && $hipay_gw->capture_payment( $order->get_id() ) ){ //Success
				$order->add_order_note( __( 'Order was captured manually.', 'wc-hipay-pro' ) );
				return true;
			}
		}
		return false;
  }

  public static function generate_mb_reference( $order ){
    global $wpdb;

    if ( ! $order->is_paid() ){
      $payment_gateways = WC()->payment_gateways()->payment_gateways();
      $hipay_mb_gw = false;
      foreach ($payment_gateways as $payment_gateway) {
        if( $payment_gateway->id === 'hipay_multibanco' ){
          $hipay_mb_gw = $payment_gateway;
          break;
        }
      }      
      if ( $hipay_mb_gw && method_exists( $hipay_mb_gw, 'generate_reference' ) ){
        $ref = $hipay_mb_gw->generate_reference( $order->get_id(), $order->get_total() );
        if( $ref && empty( $ref->error ) ) {          
          $timeLimitDays = $hipay_mb_gw->timeLimitDays + 1;
          $expiration_time = strtotime("+". $timeLimitDays ." days");
          $expire_date = date('Y-m-d', $expiration_time );
          $expire_date .= " 00:00:00";
    
          $wpdb->insert( $wpdb->prefix . 'woocommerce_hipay_mb', array( 'entity' => $ref->entity, 'reference' => $ref->reference, 'time_limit' => $hipay_mb_gw->timeLimitDays, 'expire_date' => $expire_date, 'order_id' => $order->get_id() ) );
          $order->add_order_note('Entity: ' .$ref->entity . ' Multibanco Ref.: '. $ref->reference );
          $order->set_payment_method('hipay_multibanco');
        }
      }
    }
  }

  public static function generate_payment_link( $order ){  
    if( ! $order->is_paid() ){
      $payment_gateways = WC()->payment_gateways()->payment_gateways();
      $hipay_gw = false;
      foreach ($payment_gateways as $payment_gateway) {
        if( $payment_gateway->id === 'hipay_professional' ){
          $hipay_gw = $payment_gateway;
          break;
        }
      }
      if ( $hipay_gw && method_exists( $hipay_gw, 'generate_payment_link' ) ){
        // add_filter( 'wc_hipay_new_order_request_params', 'WC_Hipay_Order_Actions::set_automatic_capture_link', 10, 2 );
        $payment_link = $hipay_gw->generate_payment_link( $order );   
        // remove_filter( 'wc_hipay_new_order_request_params', 'WC_Hipay_Order_Actions::set_automatic_capture_link', 10);

        update_post_meta( $order->get_id(), 'hipay_payment_link', $payment_link[ 'redirect' ] );
        $order->set_payment_method('hipay_professional');
      }
    }
  }

  // public static function set_automatic_capture_link( $params, $order ) {    
  //   $params[ 'manualCapture' ] = 0;    
  //   return $params;
  // }
}

new WC_Hipay_Order_Actions();
