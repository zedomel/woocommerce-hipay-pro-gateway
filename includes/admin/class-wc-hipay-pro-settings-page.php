<?php
/**
 * WooCommerce HiPay Professional Settings Page
 *
 * @author      Jose A. Salim
 * @category    Admin
 * @package     WooCommerce_HiPay_Professional/Admin
 * @version     1.0.0
 */

if (! defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if (class_exists('WC_HiPay_Professional_Settings_Page', false)) {
    return new WC_HiPay_Professional_Settings_Page();
}

/**
 * WC_HiPay_Professional_Settings_Page.
 */
class WC_HiPay_Professional_Settings_Page extends WC_Settings_Page
{

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->id = 'wc_hipay_pro_settings';
        $this->label = __('HiPay Settings', 'wc-hipay-pro');

        add_filter('woocommerce_settings_tabs_array', array( $this, 'add_settings_page' ), 20);
        add_action('woocommerce_sections_' . $this->id, array( $this, 'output_sections' ));
        add_action('woocommerce_settings_' . $this->id, array( $this, 'output' ));
        add_action('woocommerce_settings_save_' . $this->id, array( $this, 'save' ));
    }

    /**
     * Add this page to settings.
     *
     * @param array $pages
     *
     * @return mixed
     */
    public function add_settings_page($pages)
    {
        $pages[ $this->id ] = $this->label;

        return $pages;
    }


    /**
		 * Get sections
		 *
		 * @return array
		 */
		public function get_sections() {

			$sections = array(
				''         => __( 'Section 1', 'wc-hipay-pro' )
			);

			return apply_filters( 'woocommerce_get_sections_' . $this->id, $sections );
		}

    /**
     * Get settings array.
     *
     * @return array
     */
    public function get_settings()
    {
      return WC_HiPay_Professional_Admin_Settings::get_settings();
    }

    /**
     * Output the settings.
     */
    public function output()
    {
        $settings = $this->get_settings();
        WC_Admin_Settings::output_fields( $settings );
    }

    /**
     * Save settings.
     */
    public function save()
    {
        $settings = $this->get_settings();
        WC_Admin_Settings::save_fields($settings);

        update_option( 'wc_hipay_pro_activated_key', 'activated' );
        // $deactive_plugin = get_option( 'wc_hipay_pro_deactivate_checkbox', 'no' );
        // $status = get_option( 'wc_hipay_pro_activated_key', 'deactivated' );
        // if ( $deactive_plugin == 'yes' && $status == 'activated' ) {
        //   // Deactive plugin
        //   $response = WC_HiPay_Professional_API_Key::deactivate();
        //   if ( $response->status === 'deactivated' ){
        //     update_option( 'wc_hipay_pro_activated_key', 'deactivated' );
        //     update_option( 'wc_hipay_pro_api_key', '' );
        //   }
        // }
        // else if ( $status == 'deactivated' ) {
        //   // Aactive plugin
        //   $response = WC_HiPay_Professional_API_Key::activate();
        //   if ( $response->status === 'activated' ){
        //     update_option( 'wc_hipay_pro_activated_key', 'activated' );
        //     update_option( 'wc_hipay_pro_api_key', sanizite_text_filed( $response->api_key ) );
        //   }
        // }
    }
}

return new WC_HiPay_Professional_Settings_Page();
