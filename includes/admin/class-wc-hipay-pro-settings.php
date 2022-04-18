<?php
/**
 * WooCommerce HiPay Professional Admin Settings Class
 *
 * @package  WooCommerce/Admin
 * @version  3.4.0
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! class_exists('WC_HiPay_Professional_Admin_Settings', false)) :

    /**
     * WC_HiPay_Professional_Admin_Settings Class.
     */
    abstract class WC_HiPay_Professional_Admin_Settings
    {
        public static function get_settings()
        {
            $settings = array(

              array(
                      'title' => __('WooCommerce HiPay Professional Settings', 'wc-hipay-pro'),
                      'type' => 'title',
                      'desc' => __('This is where you can active/deactive your plugin licence', 'wc-hipay-pro'),
                      'id'   => 'wc_hipay_pro_options',
                  ),
              array(
                        'title'    => __('API key', 'wc-hipay-pro'),
                        'desc'     => __('Your API key generated after license purchase', 'wc-hipay-pro'),
                        'id'       => 'wc_hipay_pro_api_key',
                        'default'  => '',
                        'type'     => 'text',
                        'desc_tip' => true,
                    ),
              array(
                        'title'    => __('Activation email', 'wc-hipay-pro'),
                        'desc'     => __('Your email address registered at plugin purchase', 'wc-hipay-pro'),
                        'id'       => 'wc_hipay_pro_activation_email',
                        'default'  => '',
                        'type'     => 'email',
                        'desc_tip' => true,
                    ),
              array(
                        'title'    => __('Product ID Key', 'wc-hipay-pro'),
                        'desc'     => __('Installation product ID Key', 'wc-hipay-pro'),
                        'id'       => 'wc_hipay_pro_product_id',
                        'default'  => '',
                        'type'     => 'text',
                        'desc_tip' => true,
                    ),
              array(
                        'title'    => __('Instance Key', 'wc-hipay-pro'),
                        'desc'     => __('Installation instance key', 'wc-hipay-pro'),
                        'id'       => 'wc_hipay_pro_instance_key',
                        'default'  => '',
                        'type'     => 'text',
                        'desc_tip' => true,
                    ),
              array(
                        'title'    => __('Deactive license', 'wc-hipay-pro'),
                        'desc'     => __('Check to deactive your license', 'wc-hipay-pro'),
                        'id'       => 'wc_hipay_pro_deactivate_checkbox',
                        'default'  => 'no',
                        'type'     => 'checkbox',
                        'desc_tip' => true,
                    ),
              array(
                  'title'    => __('Activated key', 'wc-hipay-pro'),
                  'desc'     => __('Activation key', 'wc-hipay-pro'),
                  'id'       => 'wc_hipay_pro_activated_key',
                  'default'  => '',
                  'type'     => 'text',
                  'desc_tip' => true,
                  'custom_attributes'  => array( 'readonly'  => true, 'disabled'  => true ),
              ),

              array(
                'type' => 'sectionend',
                'id'   => 'wc_hipay_pro_options'
              )
            );

            /**
              * Filter MyPlugin Settings
              *
              * @since 1.0.0
              * @param array $settings Array of the plugin settings
              */
            return apply_filters('woocommerce_get_settings_wc_hipay_pro_settings', $settings);
        }
    }
endif;
