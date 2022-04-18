<?php

/**
 * WooCommerce Hipay Professional Gateway - Compra Fácil Multibanco
 *
 * Hanldes generic payment gateway functionality which is extended by idividual payment gateways.
 *
 * @class WC_Payment_Gateway
 * @version 1.0.4
 * @package WooCommerce/Abstracts
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Hipay extends default WooCommerce Payment Gateway class
 **/
class WC_HiPay_Multibanco extends WC_Payment_Gateway  {
		
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

	public static $ws_url = 'https://hm.comprafacil.pt/%s/webservice/comprafacilWS.asmx?wsdl';

	public static $sandbox_ws_url = 'https://hm.comprafacil.pt/%s/webservice/comprafacilWS.asmx?wsdl';


	protected $notify_url = null;

	/**
	 *	HiPay Website URL
	 *
	 * @var string
	 */
	public static $hipay_website_url = 'https://hipay.com';


	public function __construct() {
		
		$this->woocommerce_version = WC()->version;
		if ( version_compare( WC()->version, '3.0', ">=" ) ) 
			$this->woocommerce_version_check = true;
		else
			$this->woocommerce_version_check = false;

		$this->php_version = phpversion();

		$this->id = 'hipaymultibanco';		
		$this->icon 			= WC_HiPay_Pro()->plugin_path() .  '/images/mb_choose.png';
		$this->has_fields = false;
		$this->method_title     = __('HiPay Wallet Multibanco', 'wc-hipay-pro' );
		$this->method_description = __( 'HiPay Multibanco generates payment references to be paied in Multibanco network.', 'wc-hipay-pro' );

		$this->init_form_fields();
		$this->init_settings();

		
		$this->title 						= $this->get_option('title');
		$this->description 			= $this->get_option('description');
		$this->entity 					= $this->get_option('entity');
		$this->sandbox 					= $this->get_option('sandbox');
		$this->username 				= $this->get_option('hw_username');
		$this->password 				= $this->get_option('hw_password');
		$this->sandbox_username = $this->get_option('hw_sandbox_username');
		$this->sandbox_password = $this->get_option('hw_sandbox_password');
		$this->salt							= $this->get_option('salt');
		$this->stockonpayment		= $this->get_option('stockonpayment');
		$this->reference				= "";
		$this->description_ref 	= $this->get_option('description_ref');
		$this->timeLimitDays 		= $this->get_option('timeLimitDays');
		$this->send_email 			= $this->get_option('send_ref_email');
		$this->notify_url 			= WC()->api_request_url('WC_HiPay_Multibanco');
		$this->debug 						= 'yes' === $this->get_option( 'debug', 'no' );

		self::$log_enabled			= $this->debug;

		if ( $this->sandbox === 'yes' ){
			$this->description .= ' ' . sprintf( __( 'SANDBOX ENABLED. You can use sandbox testing accounts only. See the he <a href="%s">Hipay Professional - Overview</a> for more details.', 'wc-hipay-pro' ), 'https://developer.hipay.com/getting-started/platform-hipay-professional/overview/' );
			$this->description = trim( $this->description );
		}

		add_action('woocommerce_api_wc_hipay_multibanco', array($this, 'check_callback_response') );

		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

		add_action('woocommerce_thankyou_hipaymultibanco', array($this, 'thanks_page'));

		add_action('woocommerce_email_before_order_table', array($this, 'email_instructions'), 9, 3);		
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
		return $this->sandbox === 'yes' ? ( empty ($this->sandbox_username) || empty( $this->sandbox_password ) ) :
			( empty( $this->username) || empty( $this->password) );
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
			self::$log->log( $level, $message, array( 'source' => 'hipaymultibanco' ) );
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
			self::$log->clear( 'hipaymultibanco' );
		}
		return $saved;
	}

	function init_form_fields() {		 
		$this->form_fields = include 'settings-hipay-mb.php';		
	}

	protected function get_ws_url(){			
		if( $this->sandbox === 'yes' ){				
			return sprintf( self::$sandbox_ws_url, ( $this->entity === '10241' ? 'SIBSClickTeste' : 'SIBSClick2Teste' ) );
		}
		else{
			return sprintf( self::$ws_url, ( $this->entity === '10241' ? 'SIBSClick' : 'SIBSClick2' ) );
		}			
	}

		public function admin_options() {

		   global $wpdb;

			$soap_active = false;
			$has_webservice_access = false;
			$has_webservice_access_config = false;

			if (extension_loaded('soap')) {
				$soap_active = true;

				$wsURL = $this->get_ws_url();				
				try {
					$client = new SoapClient($wsURL);
					$has_webservice_access = true;

          if ( ($this->username != "" && $this->password !="") || ( $this->sandbox_username != "" && $this->sandbox_password !="" ) ) {

          	$dateStartStr = date("d-m-Y H:i:s");
            $dataEndStr = $dateStartStr;
            $type = "P";
            $parameters = array(
              "username" => $this->sandbox === 'yes' ? $this->sandbox_username : $this->username,
              "password" => $this->sandbox === 'yes' ? $this->sandbox_password : $this->password,
              "dateStartStr" => $dateStartStr,
              "dataEndStr" => $dataEndStr,
              "type" => $type,
            );

            $res = $client->getInfo($parameters);
            if ($res->GetReferencesInfoResult) {
              $has_webservice_access = true;				
            }
            else {
							$has_webservice_access = false;
            }

            $has_webservice_access_error = $res->error;
          }                
        } catch (Exception $e){
        	$has_webservice_access_error = $e->getMessage();
        	$this->log( 'Get info error: ' .  $e->getMessage(), 'error' );
        }
			}		

			?>
			<h3><?php _e('Multibanco Payment by HiPay Wallet', 'wc-hipay-pro'); ?></h3>
			<p><?php _e('Issue Multibanco payments references in your store, to be payed at Multibanco network or HomeBanking.', 'wc-hipay-pro'); ?></p>

			<table class="wc_emails widefat" cellspacing="0">
			<tbody>
			<tr>
				<td class="wc-email-settings-table-status">
					<?php
					if ($soap_active){ ?>
						<span class="status-enabled"></span>
					<?php
					} else	{ ?>
						<span class="status-disabled"></span>
					<?php
					}	?>
				</td>
				<td class="wc-email-settings-table-name"><?php echo __( 'SOAP LIB', 'wc-hipay-pro' ); ?></td>
				<td>
                                        <?php
					if (!$soap_active) echo __( 'Install or active SOAP library in  PHP.', 'wc-hipay-pro' );       ?>
				</td>
			</tr>

      <tr>
        <td class="wc-email-settings-table-status">                   
            <span class="status-enabled"></span>
        </td>					
				<td class="wc-email-settings-table-name"><?php echo __( 'Registry table schema', 'wc-hipay-pro' ); ?></td>				
			</tr>

      <tr>
	      <td class="wc-email-settings-table-status">
          <?php
          if ($soap_active && $has_webservice_access) :?>
            <span class="status-enabled"></span>	
          <?php else: ?>
            <span class="status-disabled"></span>
         	<?php endif; ?>
	      </td>
      	<td class="wc-email-settings-table-name"><?php echo __( 'Webservices access', 'wc-hipay-pro' ); ?></td>
        <td>
        	<?php if (!$has_webservice_access) :
						echo __( 'Check if the WS access if temporary or due authentication failure.', 'wc-hipay-pro' );
						echo "<br>" . __( 'Error: ', 'wc-hipay-pro' ) .$has_webservice_access_error;
					elseif (!$soap_active):
						echo __( 'Install or active SOAP library in  PHP.', 'wc-hipay-pro' );
					endif; ?>
				</td>
      </tr>


		</tbody>
	</table>

	<table class="form-table">
		<?php $this->generate_settings_html(); ?>
	</table>

	<p>
		<?php _e('MAKE SURE THAT:<br>1. SOAP library is active for PHP<br>2. WooCommerce REST API is active<br><br>', 'wc-hipay-pro'); ?></p>
	<p><?php printf( __('CANCELATION OF MULTIBANCO REFERENCES<br>Cancel orders with expired references setting up a cronjob to run every day (e.g. 12:00 am )<br><br>URL<br>%s<br><br>Stock is updated ( incremented with the holded amount) if not choose for update after payment confirmation', 'wc-hipay-pro'), get_site_url() ); ?></p>
	<?php
	}

	public function get_icon(){    
		$icon_url = WC_HiPay_Pro()->plugin_url() . '/images/mb_payment.jpg';		
		$icon_html = '<img src="' . esc_url( $icon_url ) . '" alt="' . esc_attr__( 'HiPay acceptance mark', 'wc-hipay-pro' ) . '" />';		
		return apply_filters( 'woocommerce_gateway_icon', $icon_html, $this->id );
  }

	protected function get_about_hipay_url(){		
		return self::$hipay_website_url . '/en';
	}

	function thanks_page($order_id) {

				
		$order = wc_get_order( $order_id );		
		$order_total = $order->get_formatted_order_total();					

		$draft = array();
		if ( isset( $_GET[ 'ref' ] ) && isset( $_GET[ 'ent' ] ) && isset( $_GET[ 'exp' ] ) ){
			$draft = array(
				'reference'			=> sanitize_text_field( $_GET[ 'ref' ] ),
				'entity'				=> sanitize_text_field( $_GET[ 'ent' ] ),
				'expire_date'		=> intval( $_GET[ 'exp' ] ),
			);							
		}

		$draft = apply_filters( 'zdm_booking_thanks_page_mb_reference', $draft, $order_id );
		extract( array(
    	'draft'				=> $draft,
    	'order_id'		=> $order_id,
    	'description'	=> $this->description,
    	'order_total'	=> $order_total
		) );

		$template = WC_HiPay_Pro()->plugin_path() . '/templates/html-multibanco-reference.php';			
		include $template;
	
		do_action( 'wc_hipay_pro_mb_thanks_page_after_table', $order_id, $order, wp_unslash( $_GET ) );

		WC()->cart->empty_cart();
		wc_clear_cart_after_payment();		
	}


	/**
	* @Override
	* Process the payment and return the result
	*
	* @param int $order_id Order ID
	* @return array
	*/
  function process_payment( $order_id ) {
	
		global $wpdb;

		$order = new WC_Order( $order_id );		
		$order_total = $order->get_total();		

		if ( apply_filters( 'wc_hipay_pro_mb_generate_reference', true, $order_id ) ){
			$myref = $this->generate_reference($order_id,$order_total);

			$table_name = $wpdb->prefix . 'woocommerce_hipay_mb';
			$timeLimitDays = $this->timeLimitDays + 1;
			$expiration_time = strtotime("+". $timeLimitDays ." days");
			$expire_date = date('Y-m-d', $expiration_time );
			$expire_date .= " 00:00:00";
		
			$wpdb->insert( $table_name, array( 'entity' => $myref->entity, 'reference' => $myref->reference, 'time_limit' => $this->timeLimitDays, 'expire_date' => $expire_date, 'order_id' => $order_id ) );

			$order->update_status('on-hold', __('Waiting Multibanco payment.', 'wc-hipay-pro'));

			// Reduce stock
			if ($this->stockonpayment !== "yes"){
				$order->reduce_order_stock();
			}

			$order->add_order_note('Entity: ' .$myref->entity . ' Multibanco Ref.: '. $myref->reference );

			return array(
	      'result' 		=> 'success',
	      'redirect'	=> add_query_arg( 'exp', $expiration_time, add_query_arg( 'ent', $myref->entity, add_query_arg( 'ref', $myref->reference, add_query_arg('key', $order->get_order_key(), $order->get_checkout_order_received_url() ) ) ) )
  		);

		}
		else{
			$order->update_status('pending', __('Multibanco reference was not automatic generated. Please, use order action to generate a MB ref.', 'wc-hipay-pro') );
			return array(
				'result'		=> 'success',
				'redirect'	=> add_query_arg('key', $order->get_order_key(), $order->get_checkout_order_received_url() )
			);			
		}
	}

  function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
		
		global $wpdb;
		
		$order_total 					= $order->get_formatted_order_total();
		$order_status 				= $order->get_status();
		$order_payment_method = $order->get_payment_method();
		$order_id 						= $order->get_id();
		
  	if ( $this->send_email !== 'yes' || $order_status !== 'on-hold' || $order_payment_method !== 'hipaymultibanco'){ 
  		return;
  	}

		$table_name = $wpdb->prefix . 'woocommerce_hipay_mb';
		$sql = $wpdb->prepare("SELECT reference, entity, expire_date FROM {$table_name} WHERE order_id = %d AND expire_date >= %s ORDER BY created_date DESC LIMIT 1", $order_id, date( 'Y-m-d' ) );

    $draft = $wpdb->get_row( $sql, ARRAY_A );	
    if( !empty( $draft ) ){
    	$draft['expire_date'] = strtotime( $draft->expire_date );
    	extract( array(
    		'draft'				=> $draft,
    		'order_id'		=> $order_id,
    		'description'	=> $this->description,
    		'order_total'	=> $order_total
    	) );
    
    	$template = WC_HiPay_Pro()->plugin_path() . '/templates/html-multibanco-reference.php';
    	include $template;
    }
  }

  /**
   * Check callback response
   *   
   */
	function check_callback_response() {
		
		global $wpdb;
		
		$order_id 	=  isset( $_GET['order'] ) ? absint( $_GET[ 'order' ] ) : 0;
		$ch 				= sanitize_text_field( $_GET["ch"] );		
		$reference 	= isset( $_GET['ref'] ) ? sanitize_text_field( $_GET["ref"] ) : '';

		if ( $order_id && $reference && $ch == sha1($this->salt . $order_id ) ){			
			try
			{			
				$wsURL = $this->get_ws_url();				
				$parameters = array(
					"reference" => $reference,
					"username" => $this->sandbox === 'yes' ? $this->sandbox_username : $this->username,
					"password" => $this->sandbox === 'yes' ? $this->sandbox_password : $this->password
					);

				$client = new SoapClient($wsURL);

				$paid = false;
				$res = $client->getInfoReference($parameters);								
				if ( $res->getInfoReferenceResult ) {
					$paid = $res->paid;
					if ($paid) {
						$this->log( 'Captured: ' . sanitize_text_field( $res->status ) );						 
						$order = wc_get_order( $order_id );

						if ($this->stockonpayment === 'yes') {
							wc_reduce_stock_levels( $order->get_id() );							
							$order->add_order_note( __( 'Stock updated after payment', 'wc-hipay-pro') );
						}

						// Check order status
						if( $order->has_status( 'cancelled' ) ){
							$this->payment_status_paid_cancelled_order( $order );							
						}

						$this->payment_complete( $order, $reference, __("MULTIBANCO Ref. captured: " . $reference , "wc-hipay-pro" ) );					


						$wpdb->update( $table_name, array( 'processed' => 1, 'processed_date' => date('Y-m-d H:i:s')), array(	
							'reference' => $reference, 
							'order_id'	=> $order_id 
						) );

					} else {
						$this->log( 'Not payed');
						$order = wc_get_order( $order_id );
						$order->add_order_note( __( 'Payment failed: MB ref. not payed.', 'wc-hipay-pro' ) );
					}
				}
				else {
					return false;
				}
			}
			catch (Exception $e){
				$error = $e->getMessage();
				$this->log( 'Exception MB: ' . $error, 'error' );
				return false;
			}
		}

		return true;
	}


	/**
	 * Generate a MB reference to be payed
	 *
	 * @param int $order_id order id
	 * @param float $order_value order amount
	 *
	 */
	public function generate_reference( $order_id, $order_value) {				
		$wsURL = $this->get_ws_url();

		$ch = sha1($this->salt . $order_id);
		$callback_url = add_query_arg( 
			array(
				'order'	=> $order_id, 			
				'ch'		=> $ch 
			), 
			$this->notify_url 
		);

		$order = wc_get_order( $order_id );              
		try {
			$order_value = number_format($order_value, 2, ".", "");
			$parameters = apply_filters( 'wc_hipay_pro_generate_reference_mb', array(
				"origin" 						=> $callback_url,
				"username" 					=> $this->sandbox === 'yes' ? $this->sandbox_username : $this->username,
				"password" 					=> $this->sandbox === 'yes' ? $this->sandbox_password : $this->password,
				"amount" 						=> $order_value,
				"additionalInfo" 		=> "",
				"name" 							=> $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
				"address" 					=> $order->get_formatted_billing_address(),
				"postCode" 					=> $order->get_billing_postcode(),
				"city"							=> $order->get_billing_city(),
				"NIC" 							=> "",
				"externalReference" => $order_id,
				"contactPhone" 			=> $order->get_billing_phone(),
				"email" 						=> $order->get_billing_email(),
				"IDUserBackoffice" 	=> -1,
				"timeLimitDays" 		=> absint( $this->timeLimitDays ),
				"sendEmailBuyer" 		=> false
			) );

			$client = new SoapClient($wsURL);

			$res = $client->getReferenceMB($parameters);			

			if ( $res->getReferenceMBResult )
			{				
				$this->entity 		= $res->entity;
				$this->reference 	= $res->reference;
				$res->error = "";
				return $res;
			}
			else
			{
				return $res;
			}

		}
		catch (Exception $e){
			$error = $e->getMessage();
			$this->log( 'Error generating MB reference: ' . $error, 'error' );
			return false;
		}
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

	protected function payment_status_paid_cancelled_order( $order ) {
		$this->send_hipay_email_notification(
			/* translators: %s: order link. */
			sprintf( __( 'Payment for cancelled order %s received', 'wc-hipay-order' ), '<a class="link" href="' . esc_url( $order->get_edit_order_url() ) . '">' . $order->get_order_number() . '</a>' ),
			/* translators: %s: order ID. */
			sprintf( __( 'Order #%s has been marked paid by HiPay, but was previously cancelled. Admin handling required.', 'wc-hipay-pro' ), $order->get_order_number() )
		);
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

	public function check_pending_abandoned_orders(){
		global $wpdb;

		//get all pending orders
		$table_name = $wpdb->prefix . 'woocommerce_hipay_mb';
		$expire_date = date('Y-m-d');
		$expire_date .= " 00:00:00";

		$sql = $wpdb->prepare( "SELECT ID, reference, order_id, expire_date FROM {$table_name} WHERE processed = 0 and expire_date <= %s", $expire_date );
		$drafts = $wpdb->get_results( $sql );
		foreach ( $drafts as $draft ) {
			//get order
			$order = wc_get_order( $draft->order_id );				
			$order_id 	= $order->get_id();
			$order_status = $order->get_status();				
			$payment_method = $order->get_payment_method();
			
			//update order if pending or on-hold
			if ($payment_method == 'hipaymultibanco' && in_array( $order_status, array( 'pending', 'on-hold' ) ) ) {

				$order->update_status('cancelled', sprintf( __("MULTIBANCO Ref. has expired in %s", "wc-hipay-pro" ), $draft->expire_date ) );

				if ( $this->stockonpayment !== 'yes' ){

					$items = $order->get_items();
					foreach ( $items as $item ) {							
						$qt 					= $item->get_quantity();
						$product_id 	= $item->get_product_id();
						$variation_id = $item->get_variation_id();							
						
						$product = $variation_id ? wc_get_product( $variation_id ) : wc_get_product( $product_id );
						
						if( $product->get_manage_stock() ){
							wc_update_product_stock ( $product , $qt, 'increase' );
							$order->add_order_note('Stock increased: #'.$product_id. ' variation #'.$variation_id. ' stock +'.$qt );							
						}
					}
				}
			}

		//update mb table processed
		$wpdb->update( $table_name, array( 'processed' => 1, 'processed_date' => date('Y-m-d H:i:s')), array('ID' => $draft->ID) );
		}
	}
}