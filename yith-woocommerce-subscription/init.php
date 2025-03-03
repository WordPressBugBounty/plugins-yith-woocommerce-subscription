<?php
/**
 * Plugin Name: YITH WooCommerce Subscription
 * Plugin URI: https://yithemes.com/themes/plugins/yith-woocommerce-subscription/
 * Description: <code><strong>YITH WooCommerce Subscription</strong></code> allows enabling automatic recurring payments on your products. Once you buy a subscription-based product, the plugin will renew the payment automatically based on your own settings. Perfect for any kind of subscriptions, like magazines, software and so on. <a href="https://yithemes.com/" target="_blank">Get more plugins for your e-commerce shop on <strong>YITH</strong></a>.
 * Version: 4.5.0
 * Author: YITH
 * Author URI: https://yithemes.com/
 * Text Domain: yith-woocommerce-subscription
 * Domain Path: /languages/
 * WC requires at least: 9.5
 * WC tested up to: 9.7
 * Requires Plugins: woocommerce
 *
 * @package YITH\Subscription
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'is_plugin_active' ) ) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

! defined( 'YITH_YWSBS_DIR' ) && define( 'YITH_YWSBS_DIR', plugin_dir_path( __FILE__ ) );

/* Plugin Framework Version Check */
if ( file_exists( YITH_YWSBS_DIR . 'plugin-fw/init.php' ) ) {
	require_once YITH_YWSBS_DIR . 'plugin-fw/init.php';
}

// This version can't be activate if premium version is active  ________________________________________.
if ( defined( 'YITH_YWSBS_PREMIUM' ) ) {
	/**
	 * Admin notice when the free version will be installed
	 */
	function yith_ywsbs_install_free_admin_notice() {
		?>
		<div class="error">
			<p><?php esc_html_e( 'You can\'t activate the free version of YITH WooCommerce Subscription while you are using the premium one.', 'yith-woocommerce-subscription' ); ?></p>
		</div>
		<?php
	}

	add_action( 'admin_notices', 'yith_ywsbs_install_free_admin_notice' );

	deactivate_plugins( plugin_basename( __FILE__ ) );
	return;
}

// Registration hook  ________________________________________.
if ( ! function_exists( 'yith_plugin_registration_hook' ) ) {
	require_once 'plugin-fw/yit-plugin-registration-hook.php';
}
register_activation_hook( __FILE__, 'yith_plugin_registration_hook' );

// Define constants ________________________________________.

! defined( 'YITH_YWSBS_VERSION' ) && define( 'YITH_YWSBS_VERSION', '4.5.0' );
! defined( 'YITH_YWSBS_FREE_INIT' ) && define( 'YITH_YWSBS_FREE_INIT', plugin_basename( __FILE__ ) );
! defined( 'YITH_YWSBS_INIT' ) && define( 'YITH_YWSBS_INIT', plugin_basename( __FILE__ ) );
! defined( 'YITH_YWSBS_FILE' ) && define( 'YITH_YWSBS_FILE', __FILE__ );
! defined( 'YITH_YWSBS_URL' ) && define( 'YITH_YWSBS_URL', plugins_url( '/', __FILE__ ) );
! defined( 'YITH_YWSBS_ASSETS_URL' ) && define( 'YITH_YWSBS_ASSETS_URL', YITH_YWSBS_URL . 'assets' );
! defined( 'YITH_YWSBS_TEMPLATE_PATH' ) && define( 'YITH_YWSBS_TEMPLATE_PATH', YITH_YWSBS_DIR . 'templates' );
! defined( 'YITH_YWSBS_INC' ) && define( 'YITH_YWSBS_INC', YITH_YWSBS_DIR . 'includes/' );
! defined( 'YITH_YWSBS_TEST_ON' ) && define( 'YITH_YWSBS_TEST_ON', false );
! defined( 'YITH_YWSBS_SLUG' ) && define( 'YITH_YWSBS_SLUG', 'yith-woocommerce-subscription' );
! defined( 'YITH_YWSBS_POST_TYPE' ) && define( 'YITH_YWSBS_POST_TYPE', 'ywsbs_subscription' );

if ( ! defined( 'YITH_YWSBS_SUFFIX' ) ) {
	$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
	define( 'YITH_YWSBS_SUFFIX', $suffix );
}

// Require plugin autoload.
if ( ! class_exists( 'YITH_WC_Subscription_Autoloader' ) ) {
	require_once YITH_YWSBS_INC . 'class-yith-wc-subscription-autoloader.php';
}

add_action(
	'plugins_loaded',
	function () {
		if ( ! function_exists( 'WC' ) ) {
			// Print a notice if WooCommerce is not installed.
			add_action(
				'admin_notices',
				function () {
					?>
					<div class="error">
						<p><?php esc_html_e( 'YITH WooCommerce Subscription is enabled but not effective. It requires WooCommerce in order to work.', 'yith-woocommerce-subscription' ); ?></p>
					</div>
					<?php
				}
			);
		} else {
			require_once YITH_YWSBS_INC . 'class-yith-wc-subscription.php';
			YITH_WC_Subscription();
		}
	},
	11
);

// Since WooCommerce PayPal Payments load at plugins_loaded[10] we must operate first to add the integration.
add_action(
	'plugins_loaded',
	function () {
		if ( ! apply_filters( 'ywsbs_enable_woocommerce_paypal_payments_gateway', true ) || ! class_exists( 'WooCommerce\PayPalCommerce\PluginModule' ) ) {
			return;
		}

		include_once YITH_YWSBS_INC . 'gateways/woocommerce-paypal-payments/class-ywsbs-wc-paypal-payments-integration.php';
		YWSBS_WC_PayPal_Payments_Integration::get_instance();
	},
	9
);

register_activation_hook( __FILE__, '\YITH_WC_Subscription_Install::activate' );
register_deactivation_hook( __FILE__, '\YITH_WC_Subscription_Install::deactivate' );
