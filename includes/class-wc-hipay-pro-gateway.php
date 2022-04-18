<?php

/**
 * WooCommerce Hipay Professional Gateway
 *
 * Hanldes generic payment gateway functionality which is extended by idividual payment gateways.
 *
 * @class WC_Payment_Gateway
 * @version 2.1.0
 * @package WooCommerce/Abstracts
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hipay extends default WooCommerce Payment Gateway class
 **/
class WC_HiPay_Professional_Gateway extends WC_Payment_Gateway {


	/**
	 * Whether or not logging is enabled
	 *
	 * @var bool
	 */
	public static $log_enabled = false;
	/**
	 * Logger instance
	 *
	 * @var WC_Logger
	 */
	public static $log = false;

	/**
	 *	HiPay Website URL
	 *
	 * @var string
	 */
	public static $hipay_website_url = 'https://hipay.com';

	/**
	* Construct for the gateway
	*/
  public function __construct() {

    $this->id			= 'hipay_professional';
    $this->has_fields	= false;
		$this->order_button_text = __('Proceed to Hipay', 'wc-hipay-pro' );
    $this->method_title	= __( 'Hipay', 'wc-hipay-pro' );
    $this->method_description = __( 'Hipay redirects customers to Hipay to enter their payment information.', 'wc-hipay-pro' );
    $this->supports           = array(
  			'products',
  			'refunds',
  	);

		// Load the settings.
		$this->init_form_fields();
    $this->init_settings();

    // Define user set variables
    $this->title 						= $this->get_option( 'title' );
    $this->description			= $this->get_option( 'description' );
		$this->sandbox_mode 		= 'yes' === $this->get_option( 'sandbox_mode', 'no' );
		$this->debug 						= 'yes' === $this->get_option( 'debug', 'no' );
		$this->receiver_email		= $this->get_option( 'receiver_email', get_option( 'admin_email' ) );
		self::$log_enabled			= $this->debug;

		// Production credentials
    $this->hipay_login			= $this->get_option( 'hipayid' );
    $this->hipay_password		= $this->get_option( 'hipayidpw' );
    $this->hipay_siteid			= $this->get_option( 'hipaysiteid' );

		// Sandbox credentials
    $this->hipay_sandbox_login			= $this->get_option( 'hipaytestid' );
    $this->hipay_sandbox_password		= $this->get_option( 'hipaytestidpw' );
    $this->hipay_sandbox_siteid			= $this->get_option( 'hipaytestsiteid' );

		// Other options
    $this->defaultlang				= $this->get_option( 'defaultlang' );
    $this->hipay_cat					= $this->get_option( 'hipaycat' );
    $this->hipay_order_title	= $this->get_option( 'hipayordertitle' );
    $this->hipay_order_info		= $this->get_option( 'hipayorderinfo' );
    $this->hipay_rating				= $this->get_option( 'hipayrating' );

    $this->hipay_delay		= "0";
    $this->hipay_logo			= $this->get_option( 'hipaylogo' );
    $this->hipay_capture 	= $this->get_option( 'hipaycapture' );

		if ( $this->sandbox_mode ){
			$this->description .= ' ' . sprintf( __( 'SANDBOX ENABLED. You can use sandbox testing accounts only. See the he <a href="%s">Hipay Professional - Overview</a> for more details.', 'wc-hipay-pro' ), 'https://developer.hipay.com/getting-started/platform-hipay-professional/overview/' );
			$this->description = trim( $this->description );
		}
    // Hooks
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts') );
    
    add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		// If order has been captured
		add_action( 'woocommerce_order_status_on-hold_to_processing', array( $this, 'capture_payment' ) );
		add_action( 'woocommerce_order_status_on-hold_to_completed', array( $this, 'capture_payment' ) );

		// If order has been cancelled
		add_action( 'woocommerce_order_status_on-hold_to_cancelled', array( $this, 'cancel_transaction' ) );

		if ( ! $this->is_valid_for_use() ) {
			$this->enabled = 'no';
		}
		else {
			include_once dirname( __FILE__ ) . '/class-wc-hipay-pro-gateway-handler.php';
			new WC_HiPay_Professional_Gateway_Handler( $this->sandbox_mode, $this->sandbox_mode ? $this->hipay_sandbox_password : $this->hipay_password );
		}
  }

  /**
	 * Return whether or not this gateway still requires setup to function.
	 *
	 * When this gateway is toggled on via AJAX, if this returns true a
	 * redirect will occur to the settings page instead.
	 *
	 * @since 2.0.1
	 * @return bool
	 */
	public function needs_setup() {
		return $this->sandbox_mode ? ( empty ($this->hipay_sandbox_login) || empty( $this->hipay_sandbox_password ) ) :
			( empty( $this->hipay_login) || empty( $this->hipay_password) );
	}

	/**
	 * Logging method.
	 *
	 * @param string $message Log message.
	 * @param string $level Optional. Default 'info'. Possible values:
	 *                      emergency|alert|critical|error|warning|notice|info|debug.
	 */
	public static function log( $message, $level = 'info' ) {
		if ( self::$log_enabled ) {
			if ( empty( self::$log ) ) {
				self::$log = wc_get_logger();
			}
			self::$log->log( $level, $message, array( 'source' => 'hipay_professional' ) );
		}
	}

	/**
	 * Processes and saves options.
	 * If there is an error thrown, will continue to save and validate fields, but will leave the erroring field out.
	 *
	 * @return bool was anything saved?
	 */
	public function process_admin_options() {
		$saved = parent::process_admin_options();
		// Maybe clear logs.
		if ( 'yes' !== $this->get_option( 'debug', 'no' ) ) {
			if ( empty( self::$log ) ) {
				self::$log = wc_get_logger();
			}
			self::$log->clear( 'hipay_professional' );
		}
		return $saved;
	}

	/**
	 * Load admin scripts.
	 *
	 * @since 3.3.0
	 */
	public function admin_scripts() {
		// $screen    = get_current_screen();
		// $screen_id = $screen ? $screen->id : '';
		// if ( 'woocommerce_page_wc-settings' !== $screen_id ) {
		// 	return;
		// }
		// $suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		// wp_enqueue_script( 'woocommerce_hipay_admin', WC()->plugin_url() . '/includes/gateways/hipay/assets/js/hipay-admin' . $suffix . '.js', array(), WC_VERSION, true );
	}

	//TODO: why zdm-booking?
	public function enqueue_scripts(){
		wp_register_style( 'zdm-booking-style', WC_HiPay_Pro()->plugin_url() . '/assets/css/style.css' );

		if( is_checkout() ){
			wp_enqueue_style( 'zdm-booking-style' );
		}
	}


  public function get_icon(){

    if ( isset( WC()->customer ) ) {

      if( version_compare( WC()->version, '3.0', ">=" ) ) {
        $country		= WC()->customer->get_billing_country();
      } else {
        $country		= WC()->customer->get_country();
      }
		}
		else{
			$country = WC()->countries->get_base_country();
		}

		$icon = WC_HiPay_Pro()->plugin_path() . '/images/hipay_logo_' . strtolower( $country ) . '.png';
		if ( file_exists( $icon ) ){
			$icon_url	= WC_HiPay_Pro()->plugin_url() . '/images/hipay_logo_' . strtolower( $country ) . '.png';
		}
		else {
			$icon_url	= WC_HiPay_Pro()->plugin_url() . '/images/hipay_logo_default.png';
		}

		$icon_html = '<img style="max-width: 75px;" src="' . esc_url( $icon_url ) . '" alt="' . esc_attr__( 'HiPay acceptance mark', 'wc-hipay-pro' ) . '" />';
		$icon_html .= sprintf( '<a href="%1$s" class="about_hipay" onclick="javacript:window.open(\'%1$s\', \'HiPay\', \'toolbar=no, location=no, directories=no, status=no, menubar=no,scrollbars=yes, resizable=yes, width=1060, height=700\'); return false;">' . esc_attr__( 'What is HiPay?', 'wc-hipay-pro' ) . '</a>', esc_url( $this->get_about_hipay_url( $country ) ) );

		return apply_filters( 'woocommerce_gateway_icon', $icon_html, $this->id );
  }

	protected function get_about_hipay_url( $country ){
		$countries = array( 'EN', 'FR', 'IT');
		if ( in_array( strtoupper( trim( $country ) ), $countries ) ){
			return self::$hipay_website_url . '/' . strtolower( $country );
		}
		return self::$hipay_website_url . '/en';
	}

	/**
	 * Check if this gateway is enabled and available in the user's country.
	 *
	 * @return bool
	 */
	public function is_valid_for_use() {
		return in_array(
			get_woocommerce_currency(),
			apply_filters(
				'woocommerce_hipay_supported_currencies',
				array( 'AUD', 'BRL', 'CAD', 'USD', 'EUR', 'CHF', 'SEK', 'GBP', 'PLN' )
			),
			true
		);
	}

	/**
  * Admin Panel Options
  * - Options for bits like 'title' and availability on a country-by-country basis
  */
  function admin_options() {
		if ( $this->is_valid_for_use() ) {
    	parent::admin_options();
		} else {
			?>
			<div class="inline error">
				<p>
					<strong><?php esc_html_e( 'Gateway disabled', 'wc-hipay-pro' ); ?></strong>: <?php esc_html_e( 'HiPay does not support your store currency.', 'wc-hipay-pro' ); ?>
				</p>
			</div>
			<?php
		}
  }

  /**
  * Initialise Gateway Settings Form Fields
  */
  public function init_form_fields() {
    $this->form_fields = include 'settings-hipay.php';
  }

  protected function get_site_categories(){
		$sandbox_mode = $this->get_option( 'sandbox_mode', 'no' );
		$siteid = $sandbox_mode ? $this->get_option( 'hipaytestsiteid', '' ) : $this->get_option( 'hipaysiteid', '' );
		if ( empty ( $siteid ) ){
			return array();
		}

		if( ! $sandbox_mode ) {
			$url = 'https://payment.hipay.com/order/list-categories/id/' . $siteid;
		} else {
			$url = 'https://test-payment.hipay.com/order/list-categories/id/' . $siteid;
		}

		$xmlArray = array();

    $turl=parse_url($url);
    if ( ! isset( $turl[ 'path' ] ) ) {
      $turl[ 'path' ] = '/';
    }

		$curl = curl_init();

		$options = array (
			CURLOPT_TIMEOUT							=> 30,
			CURLOPT_POST								=> 0,
			CURLOPT_SSL_VERIFYPEER			=> false,
			CURLOPT_USERAGENT						=> 'HIPAY',
			CURLOPT_URL									=> $turl['scheme'] . '://' . $turl['host'] . $turl['path'],
			CURLOPT_HEADER							=> 0,
			CURLOPT_RETURNTRANSFER			=> true,
			CURLOPT_FAILONERROR					=> false
		);

	  curl_setopt_array( $curl, $options );
		$data = curl_exec( $curl );
		$errno = curl_errno( $curl );
		$status = curl_getinfo( $curl, CURLINFO_HTTP_CODE );

    if ( $data === false || $errno != CURLE_OK ) {
        $output = $turl['scheme'] . '://' . $turl['host'] . $turl['path'] . ' is not reachable';
        $output .= '<br />Network problem ? Verify your proxy configuration.';
				$this->log( 'Retrieve site categories failed (curl error): ' . curl_errno( $curl ), 'error ' );
    }
    curl_close($curl);

		// Parse response
		try {
			$xml = @new SimpleXMLElement( trim( $data ) );

			if ( isset ($xml->categoriesList ) ){
				foreach ($xml->categoriesList->children() as $cat ) {
					$id  = intval ( $cat->attributes()[ 'id' ] );
					$xmlArray[ $id ] = $cat[0];
				}
			}
		} catch(Exception $e){
			$this->log( 'Retrieve site categories failed: ' . $e->getMessage(), 'error ' );
		}

    return $xmlArray;
  }

  /**
   * There are no payment fields Hipay, but we want to show the description if set.
   **/
  function payment_fields() {
    if ( $this->description ) echo wpautop( wptexturize( __( $this->description, 'wc-hipay-pro' ) ) );
  }

	/**
	* @Override
	* Process the payment and return the result
	*
	* @param int $order_id Order ID
	* @return array
	*/
	function process_payment( $order_id ) {
		include_once dirname( __FILE__ ) . '/class-wc-hipay-pro-gateway-request.php';

		$order 					= wc_get_order( $order_id );
		$hipay_request 	= new WC_Gateway_HiPay_Request( $this );

		return array(
			'result' 	=> 'success',
			'redirect'	=> $hipay_request->get_request_url( $order ), #$order->get_checkout_payment_url(true)
		);
	}

	public function generate_payment_link( $order ){
		return $this->process_payment( $order->get_id() );
	}

	/**
	 * Can the order be refunded via HiPay?
	 *
	 * @param  WC_Order $order Order object.
	 * @return bool
	 */
	public function can_refund_order( $order ) {
		$has_api_creds = false;

		if ( $this->sandbox_mode ) {
			$has_api_creds = $this->get_option( 'hipaytestid' ) && $this->get_option( 'hipaytestidpw' );
		} else {
			$has_api_creds = $this->get_option( 'hipayid' ) && $this->get_option( 'hipayidpw' );
		}
		return $order && $order->get_transaction_id() && $has_api_creds;
	}

	/**
	 * Init the API class and set the username/password etc.
	 */
	public function init_api() {
		include_once dirname( __FILE__ ) . '/class-wc-hipay-pro-gateway-api-handler.php';

		WC_Gateway_HiPay_API_Handler::$api_username		= $this->sandbox_mode ? $this->get_option( 'hipaytestid' ) 			: $this->get_option( 'hipayid' );
		WC_Gateway_HiPay_API_Handler::$api_password		= $this->sandbox_mode ? $this->get_option( 'hipaytestidpw' )		: $this->get_option( 'hipayidpw' );
		WC_Gateway_HiPay_API_Handler::$api_websiteId	= $this->sandbox_mode ? $this->get_option( 'hipaytestsiteid' )	: $this->get_option( 'hipaysiteid' );
		WC_Gateway_HiPay_API_Handler::$sandbox				= $this->sandbox_mode;
	}

	/**
	 * Process a refund if supported.
	 *
	 * @param  int    $order_id Order ID.
	 * @param  float  $amount Refund amount.
	 * @param  string $reason Refund reason.
	 * @return bool|WP_Error
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$order = wc_get_order( $order_id );

		if ( ! $this->can_refund_order( $order ) ) {
			return new WP_Error( 'error', __( 'Refund failed.', 'wc-hipay-pro' ) );
		}

		$this->init_api();
		$result = WC_Gateway_HiPay_API_Handler::refund_transaction( $order, $amount, $reason );

		if ( is_wp_error( $result ) ) {
			$this->log( 'Refund Failed: ' . $result->get_error_message(), 'error' );
			return new WP_Error( 'error', $result->get_error_message() );
		}

		$this->log( 'Refund Result: ' . wc_print_r( $result, true ) );

		if ( ! empty( $result->code ) && $result->code == 0) { //Success
				$order->add_order_note(
					sprintf( __( 'Refunded %1$s - Refund ID: %2$s', 'wc-hipay-pro' ), $result->amount, $result->transactionPublicId )
				);
				return true;
		}
		return isset( $result->description ) ? new WP_Error( 'error', $result->description ) : false;
	}

	/**
	 * Capture payment when the order is changed from on-hold to complete or processing
	 *
	 * @param  int $order_id Order ID.
	 */
	public function capture_payment( $order_id ) {
		$order = wc_get_order( $order_id );
			
		$valid_payment_status = apply_filters( 'wc_hipay_pro_valid_payment_to_capture', 'authorization', $order_id );

		if ( 'hipay_professional' === $order->get_payment_method() && $valid_payment_status === get_post_meta( $order->get_id(), '_hipay_status', true ) && $order->get_transaction_id() ) {
			$this->init_api();
			$result = WC_Gateway_HiPay_API_Handler::do_capture( $order );			
			
			if ( !is_wp_error( $result ) && isset( $result->code ) && intval( $result->code ) === 0) {
				$this->log( 'Capture Result: ' . wc_print_r( $result, true ) );

				$order->add_order_note( sprintf( __( 'Payment of %1$d was captured - Transaction ID: %2$s', 'wc-hipay-pro' ), $order->get_id(), $result->transactionPublicId ) );
				
				update_post_meta( $order->get_id(), '_hipay_status', 'captured' );
				update_post_meta( $order->get_id(), '_transaction_id', $result->transactionPublicId );		

				return true;
			}
			
			if( is_wp_error( $result ) ){
				$this->log( 'Capture Failed: ' . $result->get_error_message(), 'error' );
				$order->add_order_note( sprintf( __( 'Payment could not captured: %s', 'wc-hipay-pro' ), $result->get_error_message() ) );
			}
			else{
				$order->add_order_note( sprintf( __( 'Payment could not captured - Description: %1$s', 'wc-hipay-pro' ), $result->description ) );
			}						
		}
		
		return false;
	}


	/**
	*
	* Cancel an unpaid transaction (authorized) when status changed to cancelled
	*
	* @param int $order_id Order ID.
	*/
	public function cancel_transaction( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( 'hipay_professional' === $order->get_payment_method() ){

		 	if( 'authorization' === get_post_meta( $order->get_id(), '_hipay_status', true ) && $order->get_transaction_id() ) {
				$this->init_api();
				$result = WC_Gateway_HiPay_API_Handler::do_cancellation( $order );

				if ( is_wp_error( $result ) ) {
					$this-log( 'Cancellation Failed: ' . $result->get_error_message(), 'error' );
					$order->add_order_note( sprintf( __( 'Order could not be cancelled: %1$s', 'wc-hipay-pro' ), $result->get_error_message() ) );
					return;
				}

				$this->log( 'Cancellation Result: ' . wc_print_r( $result, true ) );

				if ( ! empty( $result->code ) && $result->code == 0) {
					$order->add_order_note( sprintf( __( 'Order %1$d was cancelled - Transaction ID: %2$s', 'wc-hipay-pro' ), $order->get_id(), $result->transactionPublicId ) );
					update_post_meta( $order->get_id(), '_hipay_status', 'cancellation' );
					update_post_meta( $order->get_id(), '_transaction_id', $result->transactionPublicId );					
				}
			}			
		}
	}

	function set_locale( $order ){
		$lang = substr( $_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2 );
		$billcountry = $order->get_billing_country();
		if ( ! empty( $billcountry ) ){
			if ( $lang == 'fr' && $billcountry == 'FR' ) {
				$this->locale = 'fr_FR';
			} elseif ( $lang == 'fr' && $billcountry == 'BE' ) {
				$this->locale = 'fr_BE';
			} elseif ( $lang == 'nl' && $billcountry == 'BE' ) {
				$this->locale = 'nl_BE';
			} elseif ( $billcountry == 'DE' ) {
				$this->locale = 'de_DE';
			} elseif ( $lang == 'en' && $billcountry == 'GB' ) {
				$this->locale = 'en_GB';
			} elseif ( $lang == 'en' && $billcountry == 'US' ) {
				$this->locale = 'en_US';
			} elseif ( $billcountry == 'ES' ) {
				$this->locale = 'es_ES';
			} elseif ( $lang == 'nl' && $billcountry == 'NL' ) {
				$this->locale = 'nl_NL';
			} elseif ( $lang == 'pt' && $billcountry == 'PT' ) {
				$this->locale = 'pt_PT';
			} elseif ( $lang == 'pt' && $billcountry == 'BR' ) {
				$this->locale = 'pt_BR';
			} elseif ( $billcountry == 'PL' ) {
				$this->locale = 'pl_PL';
			} elseif ( $billcountry == 'IT' ) {
				$this->locale = 'it_IT';
			} else {
				$this->locale = $this->defaultlang;
			}
		} else {
			$this->locale = $this->defaultlang;
		}
	}
}
