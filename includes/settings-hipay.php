<?php
/**
 * Settings for Hipay Gateway.
 *
 * @package WooCommerce/Classes/Payment
 */
defined( 'ABSPATH' ) || exit;

return array(
  'enabled'               => array(
		'title'   => __( 'Enable/Disable', 'wc-hipay-pro' ),
		'type'    => 'checkbox',
		'label'   => __( 'Enable Hipay Professional', 'wc-hipay-pro' ),
		'default' => 'no',
	),
  'sandbox_mode' => array(
    'title' => __( 'Sandbox mode', 'wc-hipay-pro' ),
    'type' => 'checkbox',
    'label' => __( 'Enable Hipay sandbox mode', 'wc-hipay-pro1' ),
    'default' => 'no',
    'description' => __( "If checked the plugin will use the Hipay test system otherwise it's will use the <b>Production Server</b>. Note, you need separate accounts for each system. See 'How to setup Hipay' for more information.", "wc-hipay-pro" ),
  ),
  'debug'                 => array(
		'title'       => __( 'Debug log', 'woocommerce' ),
		'type'        => 'checkbox',
		'label'       => __( 'Enable logging', 'woocommerce' ),
		'default'     => 'no',
		/* translators: %s: URL */
		'description' => sprintf( __( 'Log Hipay events, inside %s Note: this may log personal information. We recommend using this for debugging purposes only and deleting the logs when finished.', 'wc-hipay-pro' ), '<code>' . WC_Log_Handler_File::get_log_file_path( 'hipay_professional' ) . '</code>' ),
	),
  'hipay_notification'      => array(
		'title'       => __( 'HiPay Email Notifications', 'wc-hipay-pro' ),
		'type'        => 'checkbox',
		'label'       => __( 'Enable HiPay email notifications', 'wc-hipay-pro' ),
		'default'     => 'yes',
		'description' => __( 'Send notifications when an HiPay callback response is received from HiPay indicating refunds, chargebacks and cancellations.', 'wc-hipay-pro' ),
	),
  'title' => array(
    'title' => __( 'Title', 'wc-hipay-pro' ),
    'type' => 'text',
    'description' => __( 'This controls the title which the user sees during checkout.', 'wc-hipay-pro' ),
    'default' => __( 'HiPay', 'wc-hipay-pro' )
  ),
  'description' => array(
    'title' => __( 'Description', 'wc-hipay-pro' ),
    'type' => 'textarea',
    'description' => __( 'This controls the description which the user sees during checkout.', 'wc-hipay-pro' ),
    'default' => __( 'Pay via Hipay payment service.', 'wc-hipay-pro' )
  ),
  'defaultlang' => array(
    'title' => __( 'Default Language', 'wc-hipay-pro' ),
    'type' => 'select',
    'description' => __( 'System will try to select appropriate language with browser language and Billing Country. If none of available language are available, system wil use default language you have selected', 'wc-hipay-pro' ),
    'options' => array('fr_FR' => 'fr_FR', 'fr_BE' => 'fr_BE', 'nl_BE' => 'nl_BE', 'nl_NL' => 'nl_NL', 'de_DE' => 'de_DE', 'en_GB' => 'en_GB', 'en_US' => 'en_US', 'es_ES' => 'es_ES', 'pt_PT' => 'pt_PT', 'pt_BR' => 'pt_BR', 'pl_PL' => 'pl_PL'),
    'default' => 'en_US'
  ),
  'hipaytestid' => array(
    'title' => __( 'Hipay TEST ID account', 'wc-hipay-pro' ),
    'type' => 'text',
    'description' => __( 'ID number of the Hipay TEST account on which this store website is declared. Normally this is your main account. Do not use your member area id here!', 'wc-hipay-pro' ),
    'default' => ''
  ),
  'hipaytestidpw' => array(
    'title' => __( 'Password TEST account', 'wc-hipay-pro' ),
    'type' => 'password',
    'description' => __("Merchant password of the Hipay TEST account on which this store website is declared (it's not the login password!). To set a new merchant password: Log in to your Hipay account, navigate to Payment buttons where you can find a list of your registered sites. Click on the site informations button of the related site. Enter your merchant password and click on confirm. Do not forget to enter your new password here too.", "wc-hipay-pro" ),
    'default' => ''
  ),
  'hipaytestsiteid' => array(
    'title' => __( 'Hipay TEST site ID', 'wc-hipay-pro' ),
    'type' => 'text',
    'description' => __( 'Id of the selected TEST site. To get a site id register your store website on the selected Hipay TEST account. You can find this option in your Hipay account under the menu item Payment buttons.', 'wc-hipay-pro' ),
    'default' => ''
  ),
  'hipayid' => array(
    'title' => __( 'Hipay PROD ID account', 'wc-hipay-pro' ),
    'type' => 'text',
    'description' => __( 'ID number of the Hipay account on which this store website is declared. Normally this is your main account. Do not use your member area id here!', 'wc-hipay-pro' ),
    'default' => ''
  ),
  'hipayidpw' => array(
    'title' => __( 'Password PROD account', 'wc-hipay-pro' ),
    'type' => 'password',
    'description' => __( 'Merchant password of the Hipay PROD account on which this store website is declared (it\'s not the login password!). To set a new merchant password: Log in to your Hipay account, navigate to Payment buttons where you can find a list of your registered sites. Click on the site informations button of the related site. Enter your merchant password and click on confirm. Do not forget to enter your new password here too.', 'wc-hipay-pro' ),
    'default' => ''
  ),
  'hipaysiteid' => array(
    'title' => __( 'Hipay PROD site ID', 'wc-hipay-pro' ),
    'type' => 'text',
    'description' => __( 'Id of the selected site. To get a site id register your store website on the selected Hipay account. You can find this option in your Hipay account under the menu item Payment buttons.', 'wc-hipay-pro' ),
    'default' => ''
  ),
  'hipayordertitle' => array(
    'title' => __( 'Set a Title for your order', 'wc-hipay-pro' ),
    'type' => 'text',
    'description' => __( 'This Title will be shown in each client order. idea: <b>Order on eShop mywebsite.com</b>', 'wc-hipay-pro' ),
    'default' => ''
  ),
  'hipayorderinfo' => array(
    'title' => __( 'Additional info', 'wc-hipay-pro' ),
    'type' => 'text',
    'description' => __( 'Use this to give more details to your client. idea: <b>The best products on earth ;-)</b>', 'wc-hipay-pro' ),
    'default' => ''
  ),
  'hipaycat' => array(
    'title' => __( 'Hipay Default Category', 'wc-hipay-pro' ),
    'type' => 'select',
    'description' => __( 'Select the category. They are related to your Hipay Category settings. Only set after change site ID.', 'wc-hipay-pro' ),
    'options' => $this->get_site_categories(),
    'default' => ''
  ),
  'hipayrating' => array(
    'title' => __( 'Age Classification', 'wc-hipay-pro' ),
    'type' => 'select',
    'description' => __( 'Select the minimum age of the buyers.', 'wc-hipay-pro' ),
    'options' => array('ALL' => __( 'Everyone', 'wc-hipay-pro'), '+12' => __( 'For ages 12 and over', 'wc-hipay-pro'), '+16' => __( 'For ages 16 and over', 'wc-hipay-pro'), '+18' => __( 'For ages 18 and over', 'wc-hipay-pro')),
    'default' => 'ALL'
  ),
  'hipayemail' => array(
    'title' => __( 'Email', 'wc-hipay-pro' ),
    'type' => 'text',
    'description' => __( 'Email notification for the Merchant', 'wc-hipay-pro' ),
    'default' => ''
  ),
  'hipaylogo' => array(
    'title' => __( 'Merchant Logo url', 'wc-hipay-pro' ),
    'type' => 'text',
    'description' => __( 'If a logo URL was entered, the logo is displayed in the payment dialog. This logo, in GIF, PNG or JPG (JPEG) format must be accessible from the Internet via HTTPS protocol. This logo must not exceed 100x100 pixels in size.', 'wc-hipay-pro' ),
    'default' => ''
  ),
  'hipaycapture' => array(
    'title' => __( 'Capture Type', 'wc-hipay-pro' ),
    'type' => 'select',
    'description' => __( 'Select the capture type.', 'wc-hipay-pro' ),
    'options' => array('0' => __( 'Immediate', 'wc-hipay-pro'), '1' => __( 'Manual', 'wc-hipay-pro') ),
    'default' => '0'
  )
);
