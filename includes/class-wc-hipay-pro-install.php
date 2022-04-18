<?php

/**
 * Installation related functions and actions.
 *
 * @package WooCommerce_HiPay_Professional/Classes
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WC_HiPay_Professional_Install {


	static $product_id = 'WooCommerce HiPay Professional';

	public static function init() {
    add_action( 'init', array( __CLASS__, 'check_version' ), 5 );
    add_filter( 'wpmu_drop_tables', array( __CLASS__, 'wpmu_drop_tables' ) );
	}

  /**
	 * Check plugin version and run the updater is required.
	 *
	 * This check is done on all requests and runs if the versions do not match.
	 */
	public static function check_version() {
		if ( ! defined( 'IFRAME_REQUEST' ) && version_compare( get_option( 'wc_hipay_pro_version' ), WC_HiPay_Pro()->version, '<' ) ) {
			self::install();
		}
	}

  /**
	 * Default options.
	 *
	 * Sets up the default options used on the settings page.
	 */
	private static function create_options() {
    // Include settings so that we can run through defaults.
		$settings = WC_HiPay_Professional_Admin_Settings::get_settings();
		foreach ($settings as $value) {
			if ( isset( $value['default'] ) && isset( $values[ 'id' ] ) ){
				add_option( $value['id'], $value['default '] );
			}
		}
  }

  /**
	 * Show action links on the plugin screen.
	 *
	 * @param   mixed $links Plugin Action links.
	 * @return  array
	 */
	public static function plugin_action_links( $links ) {
		$action_links = array(
			'settings' => '<a href="' . admin_url( 'admin.php?page=wc-hipay-pro-settings' ) . '" aria-label="' . esc_attr__( 'View WooCommerce settings', 'woocommerce' ) . '">' . esc_html__( 'Settings', 'wc-hipay-pro' ) . '</a>',
		);

		return array_merge( $action_links, $links );
	}

  /**
	 * Install plugin.
	 */
  public static function install() {
    if ( ! is_blog_installed() ) {
			return;
		}

    self::create_options();
    self::create_tables();
		self::create_installation_key();
		self::update_version();
		self::create_cron_jobs();
		
		self::update_db_version();
  }

  private static function create_cron_jobs(){
  	$timestamp = wp_next_scheduled( 'multibanco_do_cron_jobs' );
  	if ( $timestamp === false ){
  		wp_schedule_event( strtotime('00:00'), 'daily', 'multibanco_do_cron_jobs' );
  	}
  }

	/**
	 * Update plugin version if new installation
	 */
	public static function update_version() {
		$curr_ver = get_option( 'wc_hipay_pro_version' );
		// checks if the current plugin version is lower than the version being installed
		if ( version_compare( WC_HiPay_Pro()->version, $curr_ver, '>' ) ) {
			// update the version
			update_option( 'wc_hipay_pro_version', WC_HiPay_Pro()->version );
		}
	}

	/**
	 * Update DB version to current.
	 * @param string $version
	 */
	public static function update_db_version( $version = null ) {
		delete_option( 'wc_hipay_pro_db_version' );
		add_option( 'wc_hipay_pro_db_version', is_null( $version ) ? WC_HiPay_Pro()->version : $version );
	}

	/**
	 * Set up the database tables which the plugin needs to function.
	 *
	 * Tables:
	 *		woocommerce_hipay_mb - Table for storing multibanco references
	 */
	private static function create_tables() {
		global $wpdb;

		$wpdb->hide_errors();

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		/**
		 * Before updating with DBDELTA, remove any primary keys which could be
		 * modified due to schema updates.
		 */

		dbDelta( self::get_schema() );
	}

	/**
	 * Get Table schema.	 
	 * @return string
	 */
	private static function get_schema() {
		global $wpdb;

		$collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			$collate = $wpdb->get_charset_collate();
		}
		
		$tables = "CREATE TABLE {$wpdb->prefix}woocommerce_hipay_mb (
		  id bigint(20) NOT NULL AUTO_INCREMENT,
		  create_date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		  reference varchar(20) NOT NULL,
		  processed tinyint(4) NOT NULL DEFAULT '0',
		  time_limit smallint(2) NOT NULL,
		  order_id bigint(20) NOT NULL,
		  expire_date datetime NOT NULL,
		  processed_date datetime NOT NULL,
		  entity varchar(7) NOT NULL,
			PRIMARY KEY id (id)
		) $collate;";

		return $tables;
	}

	/**
	 * Generate the default data arrays
	 */
	public static function create_installation_key() {

		// Reset api_key and activation_email
		update_option( 'wc_hipay_pro_api_key', '' );
		update_option( 'wc_hipay_pro_activation_email', '' );

		require_once( plugin_dir_path( __FILE__ ) . 'class-wc-hipay-pro-password-generator.php' );

		$passwd_generator = new WC_Hipay_Professional_Password_Generator();
		// Generate a unique installation $instance id
		$instance = $passwd_generator->generate_password( 32, false );

		$single_options = array(
			'wc_hipay_pro_product_id' 					=> self::$product_id,
			'wc_hipay_pro_instance_key' 				=> $instance,
			'wc_hipay_pro_deactivate_checkbox' 	=> 'yes',
			'wc_hipay_pro_activated_key' 				=> 'Activated'
		);

		foreach ( $single_options as $key => $value ) {
			update_option( $key, $value );
		}

		add_option( 'wc_hipay_pro_salt', uniqid() );
	}

	/**
	 * Uninstall tables when MU blog is deleted.
	 * @param  array $tables
	 * @return string[]
	 */
	public static function wpmu_drop_tables( $tables ) {
		global $wpdb;

		$tables[] = $wpdb->prefix . 'woocommerce_hipay_mb';

		return $tables;
	}
	
	/**
	 * Deletes all data if plugin deactivated
	 * @return void
	 */
	public static function uninstall() {
		global $wpdb, $blog_id;

		$settings = WC_HiPay_Professional_Admin_Settings::get_settings();
		// Remove options
		if ( is_multisite() ) {

			switch_to_blog( $blog_id );

			foreach ( $settings as $option) {
					if ( isset( $option[ 'id' ] ) ) {
						delete_option( $option[ 'id' ] );
					}
			}
			delete_option( 'wc_hipay_pro_version' );

			restore_current_blog();

		} else {
			foreach ( $settings as $option) {
					if ( isset( $option[ 'id' ] ) ) {
						delete_option( $option[ 'id' ] );
					}
			}
			delete_option( 'wc_hipay_pro_version' );
		}

		wp_clear_scheduled_hook( 'multibanco_do_cron_jobs' );
	}
} // End of class

WC_HiPay_Professional_Install::init();
