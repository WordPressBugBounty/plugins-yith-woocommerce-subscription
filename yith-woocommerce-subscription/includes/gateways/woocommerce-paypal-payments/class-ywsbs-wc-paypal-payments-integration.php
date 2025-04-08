<?php
/**
 * YWSBS_WC_PayPal_Payments_Integration integration with WooCommerce PayPal Payments Plugin
 *
 * @class   YWSBS_WC_Payments
 * @since   2.4.0
 * @author YITH
 * @package YITH/Subscription/Gateways
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

/**
 * Compatibility class for WooCommerce PayPal Payments.
 *
 * @extends YWSBS_WC_PayPal_Payments_Integration
 */
class YWSBS_WC_PayPal_Payments_Integration {
	use YITH_WC_Subscription_Singleton_Trait;

	/**
	 * Construct
	 *
	 * @since 2.27
	 */
	protected function __construct() {
		$this->include_files();
		// Register module for paypal payments plugin.
		add_filter( 'woocommerce_paypal_payments_modules', array( $this, 'add_module' ), 10, 1 );
		add_filter( 'ywsbs_load_paypal_standard_handler', array( $this, 'load_paypal_standard_handler' ), 10, 1 );
	}

	/**
	 * Include required files for gateway integration
	 *
	 * @return void
	 */
	protected function include_files() {

		$pp_version = $this->get_plugin_version();
		if ( empty( $pp_version ) ) {
			return;
		}

		$files_map = array(
			'class-ywsbs-wc-paypal-disabled-sources.php' => 'YWSBS_WC_PayPal_Disabled_Sources',
			'class-ywsbs-wc-paypal-payments-helper.php'  => 'YWSBS_WC_PayPal_Payments_Helper',
			'class-ywsbs-wc-paypal-payments-renewal-handler.php' => 'YWSBS_WC_PayPal_Payments_Renewal_Handler',
			'class-ywsbs-wc-paypal-payments-module.php'  => 'YWSBS_WC_PayPal_Payments_Module',
		);

		// Conditionally load legacy files to grant backward compatibility.
		$legacy_dir = YITH_YWSBS_INC . 'gateways/woocommerce-paypal-payments/module/src/legacy';
		foreach ( scandir( $legacy_dir ) as $legacy_version_dir ) {
			if ( in_array( $legacy_version_dir, array( '.', '..' ), true ) || ! version_compare( $pp_version, $legacy_version_dir, '<=' ) ) {
				continue;
			}

			foreach ( scandir( $legacy_dir . DIRECTORY_SEPARATOR . $legacy_version_dir ) as $file ) {
				if ( in_array( $file, array( '.', '..' ), true ) ) {
					continue;
				}

				// Check class exists to avoid require duplicates.
				if ( empty( $files_map[ $file ] ) || ! class_exists( $files_map[ $file ] ) ) {
					require_once $legacy_dir . DIRECTORY_SEPARATOR . $legacy_version_dir . DIRECTORY_SEPARATOR . $file;
				}
			}
		}

		// include common.
		foreach ( $files_map as $file => $class_name ) {
			if ( ! class_exists( $class_name ) ) {
				require_once YITH_YWSBS_INC . "gateways/woocommerce-paypal-payments/module/src/{$file}";
			}
		}
	}

	/**
	 * Add module to the WooCommerce PayPal Payments modules list
	 *
	 * @param array $modules Array of available modules.
	 * @return array
	 */
	public function add_module( $modules ) {
		// Double check class exists.
		if ( class_exists( 'YWSBS_WC_PayPal_Payments_Module', false ) ) {
			return array_merge(
				$modules,
				array(
					( require 'module/module.php' )(),
				)
			);
		}

		return $modules;
	}

	/**
	 * Check if PayPal standard is loaded, otherwise load it to continue handle IPN request
	 *
	 * @param boolean $load True if handlers are going to be loaded, false otherwise.
	 * @return boolean
	 */
	public function load_paypal_standard_handler( $load ) {
		$settings = get_option( 'woocommerce_ppcp-gateway_settings', array() );
		return $load || ( ! empty( $settings['enabled'] ) && 'yes' === $settings['enabled'] );
	}

	/**
	 * Get WooCommerce PayPal Payments plugin version reading the plugin metadata.
	 *
	 * @since 4.1.2
	 * @return string|false
	 */
	protected function get_plugin_version() {
		$plugin_metadata = array_filter(
			get_plugins(),
			function ( $plugin_init ) {
				return false !== strpos( $plugin_init, 'woocommerce-paypal-payments.php' ) && is_plugin_active( $plugin_init );
			},
			ARRAY_FILTER_USE_KEY
		);

		if ( empty( $plugin_metadata ) ) {
			return false;
		}

		$plugin_metadata = array_shift( $plugin_metadata );
		return $plugin_metadata['Version'] ?? '1.0.0';
	}
}
