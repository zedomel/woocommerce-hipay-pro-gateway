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
		'label'   => __( 'Enable Hipay Multibanco Payment', 'wc-hipay-pro' ),
		'default' => 'no',
	),
  'sandbox' => array(
    'title' => __( 'Sandbox mode', 'wc-hipay-pro' ),
    'type' => 'checkbox',
    'label' => __( 'Enable Hipay sandbox mode', 'wc-hipay-pro' ),
    'default' => 'no',
    'description' => __( "If checked the plugin will use the Hipay test system otherwise it's will use the <b>Production Server</b>. Note, you need separate accounts for each system. See 'How to setup Hipay' for more information.", "wc-hipay-pro" ),
  ),
  'debug'                 => array(
		'title'       => __( 'Debug log', 'woocommerce' ),
		'type'        => 'checkbox',
		'label'       => __( 'Enable logging', 'woocommerce' ),
		'default'     => 'no',
		/* translators: %s: URL */
		'description' => sprintf( __( 'Log Hipay events, inside %s Note: this may log personal information. We recommend using this for debugging purposes only and deleting the logs when finished.', 'wc-hipay-pro' ), '<code>' . WC_Log_Handler_File::get_log_file_path( 'hipaymultibanco' ) . '</code>' ),
	),  
  'title'     => array(
    'title'       => __( 'Title', 'wc-hipay-pro' ),
    'type'        => 'text',
    'description' => __( 'This controls the title which the user sees during checkout.', 'wc-hipay-pro' ),
    'default'     => __( 'Multibanco', 'wc-hipay-pro' )
  ),
  'description' => array(
    'title' => __( 'Description', 'wc-hipay-pro' ),
    'type' => 'textarea',
    'description' => __( 'This controls the description which the user sees during checkout.', 'wc-hipay-pro' ),
    'default' => __( 'Pay via Multibanco or your HomeBanking payment service.', 'wc-hipay-pro' )
  ),
  'description_ref' => array(
      'title' => __( 'Message for References', 'wc-hipay-pro' ),
      'type' => 'textarea',
      'description' => __( 'Message to follow the entity, reference and amount', 'wc-hipay-pro' ),
      'default' => __( 'Make the payment of the following Reference in a Multibanco terminal or through your Home Banking', 'wc-hipay-pro' )
    ),
  'entity' => array(
    'title'     => __( 'Entity', 'wc-hipay-pro' ),
    'type'      => 'select',
    'description' => __( 'Contracted entity', 'wc-hipay-pro' ),
    'options'     => array(
        '11249'   => __('11 249', 'wc-hipay-pro' ),
        '10241'   => __('10 241', 'wc-hipay-pro' )
    )
  ),  
  'timeLimitDays' => array(
    'title' => __( 'Limit time to payment', 'wc-hipay-pro' ),
    'type' => 'select',
    'description' => __( 'Number of days to customer perform the payment', 'wc-hipay-pro' ),
    'options'     => array(
        '-1'  => __( 'Default in HiPay settings', 'wc-hipay-pro' ),
        '3'   => __('3', 'wc-hipay-pro' ),
        '30'  => __('30', 'wc-hipay-pro' ),
        '90'  => __('90', 'wc-hipay-pro' ),          
      )
  ),  
  'send_ref_email' => array(
    'title' => __( 'Send references by email', 'wc-hipay-pro' ),
    'type' => 'checkbox',
    'label' => __( 'Send emails', 'wc-hipay-pro' ),
    'default' => 'no',
    'description' => __( "Check this option to add Multibanco references to new order emails.", "wc-hipay-pro" ),
  ),
  'hw_username' => array(
    'title' => __( 'Username', 'wc-hipay-pro' ),
    'type' => 'text',
    'description' => __( 'Username of Multibanco Web Services.', 'wc-hipay-pro' ),
    'required' => true
  ),
  'hw_password' => array(
    'title' => __( 'Password', 'wc-hipay-pro' ),
    'type' => 'text',
    'description' => __( 'Password of Multibanco Web Services.', 'wc-hipay-pro' ),
    'required' => true
  ),
  'hw_sandbox_username' => array(
    'title' => __( 'Sandbox username', 'wc-hipay-pro' ),
    'type' => 'text',
    'description' => __( 'Sandbox username of Multibanco Web Services.', 'wc-hipay-pro' ),
    'required' => true
  ),
  'hw_sandbox_password' => array(
    'title' => __( 'Sanbox password', 'wc-hipay-pro' ),
    'type' => 'text',
    'description' => __( 'Sandbox password of Multibanco Web Services.', 'wc-hipay-pro' ),
    'required' => true
  ),  
  'stockonpayment' => array(
    'title' => __( 'Reduce stock', 'wc-hipay-pro' ),
    'type' => 'checkbox',
    'description' => __( 'Reduce stock only after payment.', 'wc-hipay-pro' ),
    'default' => 'no'
  ),
  'salt' => array(
    'title' => __( 'Encryptation key', 'wc-hipay-pro' ),
    'type' => 'text',
    'description' => __( 'Always required.', 'wc-hipay-pro' ),
    'required' => true,
    'default' => uniqid()
  ),

);
