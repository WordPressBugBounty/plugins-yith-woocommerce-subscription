<?php
/**
 * YITH WooCommerce Subscription Install. Perform actions on install plugin
 *
 * @version 3.0.0
 * @author  YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WC_Subscription_Install' ) ) {
	/**
	 * YITH WooCommerce Subscription Install class
	 */
	final class YITH_WC_Subscription_Install {

		/**
		 * Install plugin process
		 *
		 * @since  3.0.0
		 * @return void
		 */
		public static function install() {

			add_action( 'plugins_loaded', array( __CLASS__, 'load_textdomain' ), 12 );
			add_action( 'init', array( __CLASS__, 'register_post_type' ), 5 );

			// Declare support with HPOS system for WooCommerce 8.
			add_action( 'before_woocommerce_init', array( __CLASS__, 'declare_hpos_support' ) );

			do_action( 'ywsbs_after_installation_process' );
		}

		/**
		 * Load plugin texdomain
		 *
		 * @since 4.4.0
		 * @return void
		 */
		public static function load_textdomain() {
			if ( function_exists( 'yith_plugin_fw_load_plugin_textdomain' ) ) {
				yith_plugin_fw_load_plugin_textdomain( 'yith-woocommerce-subscription', dirname( plugin_basename( YITH_YWSBS_FILE ) ) . '/languages/' );
			}
		}

		/**
		 * Register ywsbs_subscription post type
		 *
		 * @since 1.0.0
		 */
		public static function register_post_type() {

			$supports = false;
			if ( apply_filters( 'ywsbs_test_on', YITH_YWSBS_TEST_ON ) ) {
				$supports = array( 'custom-fields' );
			}

			$args = array(
				'label'               => esc_html__( 'ywsbs_subscription', 'yith-woocommerce-subscription' ),
				'labels'              => array(
					'name'               => esc_html_x( 'Subscriptions', 'Post Type General Name', 'yith-woocommerce-subscription' ),
					'singular_name'      => esc_html_x( 'Subscription', 'Post Type Singular Name', 'yith-woocommerce-subscription' ),
					'menu_name'          => esc_html__( 'Subscription', 'yith-woocommerce-subscription' ),
					'parent_item_colon'  => esc_html__( 'Parent item:', 'yith-woocommerce-subscription' ),
					'all_items'          => esc_html__( 'All subscriptions', 'yith-woocommerce-subscription' ),
					'view_item'          => esc_html__( 'View subscriptions', 'yith-woocommerce-subscription' ),
					'add_new_item'       => esc_html__( 'Add new subscription', 'yith-woocommerce-subscription' ),
					'add_new'            => esc_html__( 'Add new subscription', 'yith-woocommerce-subscription' ),
					'edit_item'          => esc_html__( 'Edit subscription', 'yith-woocommerce-subscription' ),
					'update_item'        => esc_html__( 'Update subscription', 'yith-woocommerce-subscription' ),
					'search_items'       => esc_html__( 'Search by subscription ID', 'yith-woocommerce-subscription' ),
					'not_found'          => esc_html__( 'Not found', 'yith-woocommerce-subscription' ),
					'not_found_in_trash' => esc_html__( 'Not found in trash', 'yith-woocommerce-subscription' ),
				),
				'supports'            => $supports,
				'hierarchical'        => false,
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => false,
				'show_in_rest'        => true,
				'exclude_from_search' => true,
				'capability_type'     => 'ywsbs_sub',
				'capabilities'        => array(
					'read_post'          => 'read_ywsbs_sub',
					'read_private_posts' => 'read_ywsbs_sub',
					'edit_post'          => 'edit_ywsbs_sub',
					'edit_posts'         => 'edit_ywsbs_subs',
					'edit_others_post'   => 'edit_others_ywsbs_subs',
					'delete_post'        => 'delete_ywsbs_sub',
					'delete_others_post' => 'delete_others_ywsbs_subs',
				),
				'map_meta_cap'        => false,
			);

			register_post_type( YITH_YWSBS_POST_TYPE, $args );

			do_action( 'ywsbs_after_register_post_type' );
		}

		/**
		 * Load the plugin fw
		 *
		 * @since  3.0.0
		 * @return void
		 */
		public static function load_plugin_framework() {
			if ( ! defined( 'YIT_CORE_PLUGIN' ) ) {
				global $plugin_fw_data;
				if ( ! empty( $plugin_fw_data ) ) {
					$plugin_fw_file = array_shift( $plugin_fw_data );
					require_once $plugin_fw_file;
				}
			}
		}

		/**
		 * Activation plugin process
		 *
		 * @since  3.0.0
		 * @return void
		 */
		public static function activate() {

			// Make sure plugin FW is loaded.
			self::load_plugin_framework();

			// Set subscription capabilities.
			YWSBS_Subscription_Capabilities::add_capabilities();

			// Regenerate permalink on custom post type registration.
			flush_rewrite_rules();

			do_action( 'yith_ywsbs_plugin_activation_process_completed' );
		}

		/**
		 * Deactivation plugin process
		 *
		 * @since  3.0.0
		 * @return void
		 */
		public static function deactivate() {
			YWSBS_Subscription_Capabilities::remove_capabilities();
		}

		/**
		 * Declare HPOS support
		 *
		 * @since 3.2.0
		 * @return void
		 */
		public static function declare_hpos_support() {
			if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', YITH_YWSBS_INIT );
			}
		}
	}
}
