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
		// Backward compatibility with version 2.4.2 or lower.
		if ( class_exists( 'WooCommerce\PayPalCommerce\Subscription\Helper\SubscriptionHelper' ) ) {
			require_once 'module/src/legacy/class-ywsbs-wc-paypal-payments-helper.php';
		} else {
			require_once 'module/src/class-ywsbs-wc-paypal-payments-helper.php';
		}

		require_once 'module/src/class-ywsbs-wc-paypal-payments-module.php';
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
		return array_merge(
			$modules,
			array(
				( require 'module/module.php' )(),
			)
		);
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
}
