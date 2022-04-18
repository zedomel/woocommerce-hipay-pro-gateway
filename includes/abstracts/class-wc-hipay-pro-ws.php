<?php
/**
 * HiPay Professional Web Services API
 *
 * @author   Jose A. Salim
 * @category Admin
 * @package  WC_Hipay_Professional/Admin
 * @version  1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
* Hipay Professional Web Services API Client
*/
abstract class WC_HiPay_Professional_WS {

  public static $ws_url = 'https://ws.hipay.com';

  public static $ws_url_test = 'https://test-ws.hipay.com';

	/**
	 * Sandbox
	 *
	 * @var bool
	 */
	public static $sandbox = false;

  public static function get_ws_client_url( $client_url = '' )
  {
      if ( (bool) self::$sandbox === false) {
          return self::$ws_url . $client_url . '?wsdl';
      }
      return self::$ws_url_test . $client_url . '?wsdl';
  }

  public static function get_client( $client_url, $trace = false )
  {
      try {
          $ws_options = array(
              'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP,
              'cache_wsdl' => WSDL_CACHE_NONE,
              'soap_version' => SOAP_1_1,
              'encoding' => 'UTF-8',
							'user_agent' => 'WooCommerce/' . WC()->version
          );
					if ( $trace ){
						$ws_options[ 'trace' ] = 1;
					}
          return new SoapClient(self::get_ws_client_url( $client_url ), $ws_options);
      } catch (SoapFault $exception) {
          return false;
      }
  }
}
