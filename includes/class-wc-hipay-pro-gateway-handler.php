<?php
/**
 * Handles responses from PayPal IPN.
 *
 * @package WooCommerce/PayPal
 * @version 3.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once dirname( __FILE__ ) . '/abstracts/class-wc-hipay-pro-gateway-response.php';

/**
 * WC_Gateway_Paypal_IPN_Handler class.
 */
class WC_HiPay_Professional_Gateway_Handler extends WC_Gateway_HiPay_Response {

  /**
   * HiPay password for MD5 checksum
   *
   * @var string HiPay password
   */
  protected $wsPassword;
  /**
	 * Constructor.
	 *
	 * @param bool   $sandbox Use sandbox or not.
   * @param string $wsPassword HiPay password
	 */
	public function __construct( $sandbox = false, $wsPassword ) {
		add_action( 'woocommerce_api_wc_hipay_professional_gateway', array( $this, 'check_response') );
		add_action( 'valid-hipay-pro-request', array( $this, 'valid_response' ), 5 );

    $this->wsPassword = $wsPassword;
		$this->sandbox    = $sandbox;
	}

  /**
	 * Check for Hipay Response.
	 */
	public function check_response() {
    // Get XML[C] send by POST from Hipay servers.
    if ( ! empty( $_POST ) && $this->validate_response() ){
      $posted = wp_unslash( $_POST[ 'xml' ] ); //WPCS: CSRF ok, input var ok           
      do_action( 'valid-hipay-pro-request', $posted );
      exit;
    }

    wp_die( 'HiPay Request Failure', 'HiPay Pro', array( 'response' => 500 ) );
  }

  /**
	 * There was a valid response.
	 *
	 * @param  array $posted Post data after wp_unslash.
	 */
  public function valid_response( $posted ){
		$response = $this->parse_xml_response( $posted );

		if ( $response === false ) {
			// Error, XML file is not transmitted by Hipay
      WC_HiPay_Professional_Gateway::log( 'Received invalid response from Hipay.' );
			wp_die( __( "Hipay Request Failure", "wc-hipay-pro" ) );
			exit;
		} else {
			// XML file is proccessed ==> Update order in the Database
			$order = $this->get_hipay_order( $response );

      WC_HiPay_Professional_Gateway::log( 'Found order #' . $order->get_id() );
      WC_HiPay_Professional_Gateway::log( 'Payment status: ' . $response[ 'operation' ] );

      // Update payment
      $this->payment_status( $order, $response );
    }
  }

  /**
	 * Check HiPay response validity.
	 */
  public function validate_response() {
    WC_HiPay_Professional_Gateway::log( 'Checking HiPay response is valid' );

		if ( ! isset( $_POST[ 'xml' ] ) ){
			WC_HiPay_Professional_Gateway::log( 'Empty response!' );
			return false;
		}

    // Get received values from post data.
    $validate_hipay = wp_unslash( $_POST );
    $response = $validate_hipay[ 'xml' ];

    try {
      $xml = new SimpleXMLElement( trim( $response ) );
    } catch ( Exception $e ){
      WC_HiPay_Professional_Gateway::log( 'Received invalid response from HiPay Professional' );
      return false;
    }

    WC_HiPay_Professional_Gateway::log( 'HiPay Response: ' . wc_print_r( $response, true ) );

    // Check to sse if the response is valid
    $signature = hash('md5',  $xml->result->asXML() . $this->wsPassword  );
		$ch = isset( $xml->result->merchantDatas->_aKey_ch) ? (string) $xml->result->merchantDatas->_aKey_ch : '';
		$order_key = isset( $xml->result->merchantDatas->_aKey_order_key) ? (string) $xml->result->merchantDatas->_aKey_order_key : '';
		$salt = get_option( 'wc_hipay_pro_salt' );

		$md5content = (string) $xml->md5content;
		$order_id = isset( $xml->result->idForMerchant ) ? intval( $xml->result->idForMerchant ) : 0;
		if( ! $order_id ){
			WC_HiPay_Professional_Gateway::log( 'Received invalid response from HiPay Professional. No order ID in the response.' );
	    return false;
		}

		$order = wc_get_order( $order_id );
		if( !$order ){
			WC_HiPay_Professional_Gateway::log( 'Received invalid response from HiPay Professional. Order does not exists.' );
			return false;
		}

		WC_HiPay_Professional_Gateway::log($signature);
		WC_HiPay_Professional_Gateway::log($md5content);

		// if ( hash_equals( $signature, $md5content ) &&
		if ( (string) $xml->result->status === 'ok' && $ch === sha1( $salt . $order->get_id() ) && $order_key === $order->get_order_key() ){
      WC_HiPay_Professional_Gateway::log( 'Received valid response from HiPay Professional' );
			return true;
    }

    WC_HiPay_Professional_Gateway::log( 'Received invalid response from HiPay Professional' );

    return false;
  }

  /**
	 * Check for a valid transaction status.
	 *
	 * @param string $txn_status Transaction status.
	 */
	protected function validate_transaction_status( $txn_status ) {
		$accepted_statuses = array( 'ok', 'nok', 'cancel', 'waiting' );

		if ( ! in_array( strtolower( $txn_status ), $accepted_statuses, true ) ) {
			WC_HiPay_Professional_Gateway::log( 'Aborting, Invalid status:' . $txn_status );
			exit;
		}
	}

  /**
   * Check for a valid transaction operation.
   *
   * @param string $txn_op Transaction operation.
   */
  protected function validate_transaction_operation( $txn_op ) {
    $accepted_operations = array( 'capture', 'authorization', 'cancellation', 'refund', 'reject' );

    if ( ! in_array( strtolower( $txn_op ), $accepted_operations, true ) ) {
      WC_HiPay_Professional_Gateway::log( 'Aborting, Invalid operation:' . $txn_op );
      exit;
    }
  }

	/**
	 * Check currency from HiPay matches the order.
	 *
	 * @param WC_Order $order    Order object.
	 * @param string   $currency Currency code.
	 */
	protected function validate_currency( $order, $currency ) {
		if ( $order->get_currency() != $currency ) {
			WC_HiPay_Professional_Gateway::log( 'Payment error: Currencies do not match (sent "' . $order->get_currency() . '" | returned "' . $currency . '")' );

			/* translators: %s: currency code. */
			$order->update_status( 'on-hold', sprintf( __( 'Validation error: HiPay currencies do not match (code %s).', 'wc-hipay-pro' ), $currency ) );
			exit;
		}
	}

	/**
	 * Check payment amount from HiPay matches the order.
	 *
	 * @param WC_Order $order  Order object.
	 * @param int      $amount Amount to validate.
	 */
	protected function validate_amount( $order, $amount ) {
		if ( number_format( $order->get_total(), 2, '.', '' ) !== number_format( $amount, 2, '.', '' ) ) {
			WC_HiPay_Professional_Gateway::log( 'Payment error: Amounts do not match (gross ' . $amount . ')' );

			/* translators: %s: Amount. */
			$order->update_status( 'on-hold', sprintf( __( 'Validation error: HiPay amounts do not match (gross %s).', 'wc-hipay-pro' ), $amount ) );
			exit;
		}
	}

	/**
	 * Check customer data from HiPay.
	 * WooCommerce -> Settings -> Checkout -> HiPay, it will log an error about it.
	 *
	 * @param WC_Order $order          Order object.
	 * @param string   $response       XML response
	 */
	protected function validate_customer_data( $order, $response ) {
		if ( strcasecmp( trim( $response[ 'emailClient' ] ), trim( $order->get_billing_email() ) ) !== 0 ) {
			WC_HiPay_Professional_Gateway::log( "HiPay Response is for another customer: {$response[ 'emailClient' ]}. Customer email is {$order->get_billing_email()}" );
			/* translators: %s: email address . */
			$order->update_status( 'on-hold', sprintf( __( 'Validation error: HiPay response from a different email address (%s).', 'wc-hipay-pro' ), $response[ 'emailClient' ] ) );
			exit;
		}

    if ( trim( $response[ 'customerCountry'] ) !== trim( $order->get_billing_country() ) ){
      WC_HiPay_Professional_Gateway::log( "HiPay Response country does not match customer country: {$response[ 'customerCountry' ]}. Customer country is {$order->get_billing_country()}" );
      /* translators: %s: email address . */
      $order->update_status( 'on-hold', sprintf( __( 'Validation error: HiPay response from a different country (%s).', 'wc-hipay-pro' ), $response[ 'customerCountry' ] ) );
      exit;
    }
	}

  /**
	 * Handle a payment.
	 *
	 * @param WC_Order $order  Order object.
	 * @param array    $posted Posted data.
	 */
	protected function payment_status( $order, $response ) {
    // if ( $order->has_status( wc_get_is_paid_statuses() ) ){
    //   WC_HiPay_Professional_Gateway::log( 'Aborting, Order #' . $order->get_id() . ' is already complete.' );
    //   exit;
    // }

		$this->save_hipay_meta_data( $order, $response );
    $this->validate_transaction_status( $response['status'] );
    $this->validate_transaction_operation( $response['operation'] );
		$this->validate_currency( $order, $response['origCurrency'] );
		$this->validate_amount( $order, $response['origAmount'] );
		$this->validate_customer_data( $order, $response );

    if ( 'ok' === $response[ 'status' ] ) {
      if ( 'capture' === $response['operation'] ) {
        if ( $order->has_status( 'cancelled' ) ) {
          $this->payment_status_paid_cancelled_order( $order, $response );
        }

        $this->payment_complete( $order, ( ! empty( $response['transid'] ) ? wc_clean( $response['transid'] ) : '' ), __( 'HiPay payment completed', 'wc-hipay-pro' ) );
      }
      else if ( 'authorization' === $response['operation'] ) {
          $this->payment_on_hold( $order, __( 'Payment authorized. Change payment status to processing or complete to capture funds.', 'wc-hipay-pro' ) );
      }
      else if ( 'cancellation' === $response['operation'] ) {
        $this->payment_cancelled( $order, $response );
      }
      else if ( 'refund' === $response['operation'] ){
        $this->payment_refunded( $order, $response );
		  }
    }
    else {
      $this->payment_failed( $order, $response );
    }
  }

   /**
	 * Handle a failed payment.
	 *
	 * @param WC_Order $order  Order object.
	 * @param array    $response Posted data.
	 */
	protected function payment_failed( $order, $response ) {
		$order->update_status( 'failed', sprintf( __( 'Payment %s via HiPay.', 'wc-hipay-pro' ), wc_clean( $response['operation'] ) ) );
	}

  /**
	 * When a user cancelled order is marked paid.
	 *
	 * @param WC_Order $order  Order object.
	 * @param array    $response XML data.
	 */
	protected function payment_status_paid_cancelled_order( $order, $response ) {
		$this->send_hipay_email_notification(
			/* translators: %s: order link. */
			sprintf( __( 'Payment for cancelled order %s received', 'wc-hipay-order' ), '<a class="link" href="' . esc_url( $order->get_edit_order_url() ) . '">' . $order->get_order_number() . '</a>' ),
			/* translators: %s: order ID. */
			sprintf( __( 'Order #%s has been marked paid by HiPay, but was previously cancelled. Admin handling required.', 'wc-hipay-pro' ), $order->get_order_number() )
		);
	}

  /**
	 * Handle a refunded order.
	 *
	 * @param WC_Order $order  Order object.
	 * @param array    $response XML data.
	 */
	protected function payment_refunded( $order, $response ) {
		// Only handle full refunds, not partial.
		if ( $order->get_total() === wc_format_decimal( $response['origAmount'] ) ) {

			/* translators: %s: payment status. */
			$order->update_status( 'refunded', sprintf( __( 'Payment refunded (%s).', 'wc-hipay-pro' ), $response['operation'] ) );

			$this->send_hipay_email_notification(
				/* translators: %s: order link. */
				sprintf( __( 'Payment for order %s refunded', 'wc-hipay-pro' ), '<a class="link" href="' . esc_url( $order->get_edit_order_url() ) . '">' . $order->get_order_number() . '</a>' ),
				/* translators: %1$s: order ID, %2$s: reason code. */
				sprintf( __( 'Order #%1$s has been marked as refunded - HiPay reason code: %2$s', 'wc-hipay-pro' ), $order->get_order_number(), isset( $response[ 'merchantDatas' ]['reason'] ) ?  wc_clean( $response[ 'merchantDatas' ]['reason'] ) : '' )
			);
		}
		else {
			$order->add_order_note(
				sprintf( __( 'HiPay Refunded %1$s - Refund ID: %2$s', 'wc-hipay-pro' ), $result->refundedAmount, $result->transactionPublicId )
			);
		}
	}

  /**
	 * Handle a cancelled reversal.
	 *
	 * @param WC_Order $order  Order object.
	 * @param array    $response XML data.
	 */
	protected function payment_cancelled( $order, $response ) {

    $order->update_status( 'cancelled', sprintf( __( 'Payment cancelled (%s).', 'wc-hipay-pro' ), $response['operation'] ) );

		$this->send_hipay_email_notification(
			/* translators: %s: order link. */
			sprintf( __( 'Reversal cancelled for order #%s', 'wc-hipay-pro' ), $order->get_order_number() ),
			/* translators: %1$s: order ID, %2$s: order link. */
			sprintf( __( 'Order #%1$s has had a reversal cancelled. Please check the status of payment and update the order status accordingly here: %2$s', 'wc-hipay-pro' ), $order->get_order_number(), esc_url( $order->get_edit_order_url() ) )
		);
	}

  /**
	 * Save important data from the HiPay to the order.
	 *
	 * @param WC_Order $order  Order object.
	 * @param array    $response XML data.
	 */
	protected function save_hipay_meta_data( $order, $response ) {
		if ( ! empty( $response['paymentMethod'] ) ) {
			update_post_meta( $order->get_id(), 'Payment method', wc_clean( $response['paymentMethod'] ) );
		}
		if ( ! empty( $response['transid'] ) ) {
			update_post_meta( $order->get_id(), '_transaction_id', wc_clean( $response['transid'] ) );
		}
		if ( ! empty( $response['operation'] ) && ! empty( $response[ 'status' ] ) && 'ok' === (string) $response[ 'status' ] ) {			
			update_post_meta( $order->get_id(), '_hipay_status', wc_clean( $response['operation'] ) );
		}
	}

  /**
	 * Send a notification to the user handling orders.
	 *
	 * @param string $subject Email subject.
	 * @param string $message Email message.
	 */
	protected function send_hipay_email_notification( $subject, $message ) {
		$new_order_settings = get_option( 'woocommerce_new_order_settings', array() );
		$mailer             = WC()->mailer();
		$message            = $mailer->wrap_message( $subject, $message );

		$woocommerce_hipay_settings = get_option( 'woocommerce_hipay_professional_settings' );
		if ( ! empty( $woocommerce_hipay_settings['hipay_notification'] ) && 'no' === $woocommerce_hipay_settings['hipay_notification'] ) {
			return;
		}

		$mailer->send( ! empty( $new_order_settings['recipient'] ) ? $new_order_settings['recipient'] : get_option( 'admin_email' ), strip_tags( $subject ), $message );
	}
}
