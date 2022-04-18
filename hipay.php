<?php
/*
Plugin Name: Woocommerce Hipay Professional Gateway
Plugin URI:https://zedomel@bitbucket.org/zedomel/woocommerce-hipay-pro-gateway.git
Description: Hipay Professional API for Woocommerce 3.0+
Version: 1.1.1
Author: Jose A. Salim
Author URI: mailto:zedomel@gmail.com
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

// Define WC_HIPAY_PRO_PLUGIN_FILE.
if ( ! defined( 'WC_HIPAY_PRO_PLUGIN_FILE' ) ) {
	define( 'WC_HIPAY_PRO_PLUGIN_FILE', __FILE__ );
}

class WooCommerce_HiPay_Professional {


	/**
	 * Self Upgrade Values
	 *
	 * @var string
	 */
	// Base URL to the remote upgrade API server
	// public $upgrade_url = ''; // URL to access the Update API Manager.

	/**
	 * @var string
	 */
	public $version = '1.1.1';

	/**
	 * The single instance of the class.
	 *
	 * @var WooCoomerce_HiPay_Professional
	 * @since 1.0.0
	 */
	protected static $_instance = null;

	/**
	 * Main WooCommerce_HiPay_Professional Instance.
	 *
	 * Ensures only one instance of WooCommerce_HiPay_Professional is loaded or can be loaded.
	 *
	 * @since 1.0
	 * @static
	 * @see WC_HiPay_Pro()
	 * @return WooCommerce_HiPay_Professional - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 2.1
	 */
	public function __clone() {
		wc_doing_it_wrong( __FUNCTION__, __( 'Cloning is forbidden.', 'wc-hipay-pro' ), '1.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0
	 */
	public function __wakeup() {
		wc_doing_it_wrong( __FUNCTION__, __( 'Unserializing instances of this class is forbidden.', 'wc-hipay-pro' ), '1.0' );
	}

	public function __construct() {
		$this->define_constants();
		$this->init_hooks();
		do_action( 'wc_hipay_pro_loaded' );
	}

	/**
	 * Hook into actions and filters.
	 *
	 * @since 1.0
	 */
	private function init_hooks() {
		// Run the activation function
		include_once WC_HIPAY_PRO_ABSPATH . 'includes/admin/class-wc-hipay-pro-settings.php';
		include_once WC_HIPAY_PRO_ABSPATH . 'includes/class-wc-hipay-pro-install.php';

		register_activation_hook( __FILE__, [ 'WC_HiPay_Professional_Install', 'install' ] );
		// Deletes all data if plugin deactivated
		register_deactivation_hook( __FILE__, [ 'WC_HiPay_Professional_Install', 'uninstall' ] );

		add_action( 'woocommerce_loaded', [ $this, 'init' ] );
		add_action( 'multibanco_do_cron_jobs', [ $this, 'multibanco_do_cron_jobs' ] );

		/* Check Permalink Structure */
		if ( '' == get_option( 'permalink_structure' ) ) {
			add_action( 'admin_notices', [ $this, 'prettyPermalinksMessage' ] );
		}
	}

	/**
	 * Call the cron task related methods in the gateway
	 *
	 * @since 1.0.0
	 **/
	public function multibanco_do_cron_jobs() {
		if ( class_exists( 'WC_HiPay_Multibanco' ) ) {
			$gateway = new WC_HiPay_Multibanco();
			$gateway->check_pending_abandoned_orders();
		}
		// $gateway->sync();
	}

	/**
	 * Define WC Constants.
	 */
	private function define_constants() {
		$this->define( 'WC_HIPAY_PRO_ABSPATH', dirname( WC_HIPAY_PRO_PLUGIN_FILE ) . '/' );
	}

	/**
	 * Define constant if not already set.
	 *
	 * @param string      $name  Constant name.
	 * @param string|bool $value Constant value.
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 */
	public function includes() {
		include_once WC_HIPAY_PRO_ABSPATH . 'includes/class-wc-hipay-pro-gateway.php';
		include_once WC_HIPAY_PRO_ABSPATH . 'includes/class-wc-hipay-pro-multibanco.php';
		include_once WC_HIPAY_PRO_ABSPATH . 'includes/class-wc-hipay-pro-order-actions.php';

		// if (is_admin()) {
		//
				// }
	}

	public function add_settings( $settings ) {
		$settings[] = include WC_HIPAY_PRO_ABSPATH . 'includes/admin/class-wc-hipay-pro-settings-page.php';
		return $settings;
	}

	public function init() {
		add_filter( 'woocommerce_payment_gateways', [ $this, 'add_hipay_gateway' ] );
		add_filter( 'woocommerce_available_payment_gateways', [ $this, 'filter_hipay_multibanco_gateway' ] );
		add_filter( 'woocommerce_get_settings_pages', [ $this, 'add_settings' ], 15, 1 );

		$this->includes();
		// Set up localisation.
		$this->load_plugin_textdomain();
	}

	public function is_active() {
		// return WC_HiPay_Professional_API_Key::check();
		return true;
	}

	/**
	 * Add the Gateway to WooCommerce
	 **/
	public function add_hipay_gateway( $methods ) {
		$methods[] = 'wc_hipay_professional_gateway';
		$methods[] = 'wc_hipay_multibanco';
		return $methods;
	}

	/**
	 * Remove Multibanco option from payment gateways
	 * if order total is greater than 2.500
	 */
	public function filter_hipay_multibanco_gateway( $methods ) {
		if ( isset( WC()->cart ) ) {
			$total_amount = WC()->cart->get_total( 'edit' );
			if ( $total_amount > 2500 ) {
				unset( $methods['hipaymultibanco'] );
			}
		}
		return $methods;
	}

	/**
	 * Load Localisation files.
	 *
	 * Note: the first-loaded translation file overrides any following ones if the same translation is present.
	 *
	 * Locales found in:
	 *      - WP_LANG_DIR/wc-hipay-pro/wc-hipay-pro-LOCALE.mo
	 *      - WP_LANG_DIR/plugins/wc-hipay-pro-LOCALE.mo
	 */
	public function load_plugin_textdomain() {
		$locale = is_admin() && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
		$locale = apply_filters( 'plugin_locale', $locale, 'wc-hipay-pro' );

		// Ready for translation
		unload_textdomain( 'wc-hipay-pro' );
		load_textdomain( 'wc-hipay-pro', WP_LANG_DIR . '/wc-hipay-pro/wc-hipay-pro-' . $locale . '.mo' );
		load_plugin_textdomain( 'wc-hipay-pro', false, plugin_basename( dirname( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Get the plugin url.
	 *
	 * @return string
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', WC_HIPAY_PRO_PLUGIN_FILE ) );
	}

	/**
	 * Get the plugin path.
	 *
	 * @return string
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( WC_HIPAY_PRO_PLUGIN_FILE ) );
	}

	public function template_path() {
		return $this->plugin_path() . '/templates';
	}

	public function prettyPermalinksMessage() {
		echo "<div id='message' class='error'><p>" . __( 'WooCommerce Hipay Professional Gateway works better with custom permalinks. Please go to the <a href="options-permalink.php">Permalinks Options Page</a> to configure them.', 'wc-hipay-pro' ) . '</p></div>';
	}

	/**
	 * Deletes all data if plugin deactivated
	 *
	 * @return void
	 */
	public function uninstall() {
		global $wpdb, $blog_id;

		// Remove options
		if ( is_multisite() ) {
			switch_to_blog( $blog_id );

			foreach ( [
				$this->wc_hipay_pro_data_key,
				$this->wc_hipay_pro_product_id_key,
				$this->wc_hipay_pro_instance_key,
				$this->wc_hipay_pro_deactivate_checkbox_key,
				$this->wc_hipay_pro_activated_key,
			] as $option ) {
				delete_option( $option );
			}

			restore_current_blog();
		} else {
			foreach ( [
				$this->wc_hipay_pro_data_key,
				$this->wc_hipay_pro_product_id_key,
				$this->wc_hipay_pro_instance_key,
				$this->wc_hipay_pro_deactivate_checkbox_key,
				$this->wc_hipay_pro_activated_key,
			] as $option ) {
				delete_option( $option );
			}
		}
	}
} // End of class

/**
 * Main instance of WooCommerce_HiPay_Professional.
 *
 * Returns the main instance of WooCommerce_HiPay_Professional to prevent the need to use globals.
 *
 * @since  1.0
 * @return WooCommerce_HiPay_Professional
 */
function WC_HiPay_Pro() {
	return WooCommerce_HiPay_Professional::instance();
}
// Initialize the class instance only once
WC_HiPay_Pro();
