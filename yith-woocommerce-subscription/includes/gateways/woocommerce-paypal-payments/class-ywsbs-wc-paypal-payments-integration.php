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

		// Backward compatibility with version 2.4.2 or lower.
		if ( version_compare( $pp_version, '2.4.3', '<' ) ) {
			require_once 'module/src/legacy/class-ywsbs-wc-paypal-payments-helper.php';
		} else {
			require_once 'module/src/class-ywsbs-wc-paypal-payments-helper.php';
		}

		// Backward compatibility with version 2.9.0 or lower.
		if ( version_compare( $pp_version, '2.9.1', '<' ) ) {
			require_once 'module/src/legacy/class-ywsbs-wc-paypal-payments-module.php';
		} else {
			require_once 'module/src/class-ywsbs-wc-paypal-payments-module.php';
		}

		require_once 'module/src/class-ywsbs-wc-paypal-disabled-sources.php';
		require_once 'module/src/class-ywsbs-wc-paypal-payments-renewal-handler.php';
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
