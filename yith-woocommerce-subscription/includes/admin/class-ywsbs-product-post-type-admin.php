<?php
/**
 * YWSBS_Product_Post_Type_Admin Class.
 *
 * Manage the subscription options inside the product editor.
 *
 * @class   YWSBS_Product_Post_Type_Admin
 * @since   1.0.0
 * @author  YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YWSBS_Product_Post_Type_Admin' ) ) {

	/**
	 * Class YWSBS_Product_Post_Type_Admin
	 */
	class YWSBS_Product_Post_Type_Admin {
		use YITH_WC_Subscription_Singleton_Trait;

		/**
		 * Constructor
		 *
		 * Initialize actions and filters to be used
		 *
		 * @since 2.0.0
		 */
		public function __construct() {
			add_action( 'admin_init', array( $this, 'init' ), 10 );
		}

		/**
		 * Init function.
		 */
		public function init() {
			$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0; //phpcs:ignore
			$post       = isset( $_GET['post'] ) ? $_GET['post'] : $product_id; //phpcs:ignore

			if ( apply_filters( 'ywsbs_enable_subscription_on_product', true, $post ) ) {
				// Product editor.
				add_filter( 'product_type_options', array( $this, 'add_type_options' ) );
				// Custom fields for single product.
				add_filter( 'woocommerce_product_data_tabs', array( $this, 'add_subscription_settings_tab' ), 10, 1 );
				add_filter( 'woocommerce_product_data_panels', array( $this, 'add_custom_fields_for_single_products' ) );
				add_action( 'woocommerce_process_product_meta', array( $this, 'save_custom_fields_for_single_products' ), 10 );
			}
		}

		/**
		 * Add a product type option in single product editor
		 *
		 * @param array $types List of types.
		 *
		 * @return array
		 * @since  1.0.0
		 */
		public function add_type_options( $types ) {
			$types['ywsbs_subscription'] = array(
				'id'            => '_ywsbs_subscription',
				'class'         => 'checkbox_ywsbs_subscription',
				'wrapper_class' => 'show_if_simple',
				'label'         => esc_html__( 'Subscription', 'yith-woocommerce-subscription' ),
				'description'   => esc_html__( 'Create a subscription for this product', 'yith-woocommerce-subscription' ),
				'default'       => 'no',
			);

			return $types;
		}

		/**
		 * Add subscription settings data tab to product edit
		 *
		 * @since 3.0.0
		 * @param array $tabs An array of product tabs.
		 * @return array
		 */
		public function add_subscription_settings_tab( $tabs ) {
			// Hide general tab.
			$tabs = array_merge(
				$tabs,
				array(
					'subscription-settings' => array(
						'label'    => __( 'Subscription settings', 'yith-woocommerce-subscription' ),
						'target'   => 'ywsbs_subscription_settings',
						'class'    => array( 'show_if_simple' ),
						'priority' => 11,
					),
				)
			);
			return $tabs;
		}

		/**
		 * Add custom fields for single product
		 *
		 * @return void
		 * @since  1.0.0
		 */
		public function add_custom_fields_for_single_products() {

			global $thepostid;

			$product      = wc_get_product( $thepostid );
			$enable_limit = $product->get_meta( '_ywsbs_enable_limit' );

			$max_length        = $product->get_meta( '_ywsbs_max_length' );
			$enable_max_length = $product->get_meta( '_ywsbs_enable_max_length' );
			$_ywsbs_limit      = $product->get_meta( '_ywsbs_limit' );

			$_ywsbs_limit = empty( $_ywsbs_limit ) ? 'no' : $_ywsbs_limit;

			if ( empty( $enable_limit ) ) {
				$enable_limit = 'no' === $_ywsbs_limit ? 'no' : 'yes';
				$_ywsbs_limit = 'no' === $_ywsbs_limit ? 'one-active' : $_ywsbs_limit;
			}

			if ( empty( $enable_max_length ) ) {
				$enable_max_length = ! empty( $max_length ) ? 'yes' : 'no';
			}

			$args = array(
				'product'                  => $product,
				'_ywsbs_price_is_per'      => $product->get_meta( '_ywsbs_price_is_per' ),
				'_ywsbs_price_time_option' => $product->get_meta( '_ywsbs_price_time_option' ),
				'_ywsbs_enable_limit'      => $enable_limit,
				'_ywsbs_enable_max_length' => $enable_max_length,
				'_ywsbs_max_length'        => $max_length,
				'_ywsbs_limit'             => $_ywsbs_limit,
				'max_lengths'              => ywsbs_get_max_length_period(),
			);

			wc_get_template( 'product/single-product-options.php', $args, '', YITH_YWSBS_DIR . '/views/' );
		}



		/**
		 * Save custom fields for single product
		 *
		 * @param int $post_id Product id.
		 *
		 * @return void
		 * @since  1.0.0
		 */
		public function save_custom_fields_for_single_products( $post_id ) {

			$posted = $_POST; // phpcs:ignore

			if ( isset( $posted['product-type'] ) && 'variable' === $posted['product-type'] ) {
				$this->reset_custom_field_for_product( $post_id );
				return;
			}

			$product              = wc_get_product( $post_id );
			$manual_fields_saving = array( '_ywsbs_subscription', '_ywsbs_enable_limit' );
			$custom_fields        = array_diff( $this->get_custom_fields_list(), $manual_fields_saving );

			if ( isset( $posted['_ywsbs_price_time_option'] ) && isset( $posted['_ywsbs_max_length'] ) ) {
				$max_length                  = ywsbs_validate_max_length( $posted['_ywsbs_max_length'], $posted['_ywsbs_price_time_option'] );
				$posted['_ywsbs_max_length'] = $max_length;
			}

			foreach ( $manual_fields_saving as $manual_field ) {
				$value = isset( $posted[ $manual_field ] ) ? 'yes' : 'no';
				$product->update_meta_data( $manual_field, $value );
			}

			foreach ( $custom_fields as $meta ) {
				if ( isset( $posted[ $meta ] ) ) {
					$product->update_meta_data( $meta, $posted[ $meta ] );
				}
			}

			$product->save();
		}


		/**
		 * Reset custom field
		 *
		 * @param int $product_id Product id.
		 *
		 * @return void
		 * @since  1.0.0
		 */
		private function reset_custom_field_for_product( $product_id ) {

			$product       = wc_get_product( $product_id );
			$custom_fields = $this->get_custom_fields_list();

			foreach ( $custom_fields as $cf ) {
				$product->delete_meta_data( $cf );
			}

			$product->save();
		}

		/**
		 * Return the list of custom fields relative to subscription.
		 *
		 * @return mixed|void
		 * @since  1.4
		 */
		private function get_custom_fields_list() {
			$custom_fields = array(
				'_ywsbs_subscription',
				'_ywsbs_price_is_per',
				'_ywsbs_price_time_option',
				'_ywsbs_max_length',
				'_ywsbs_enable_max_length',
				'_ywsbs_enable_limit',
				'_ywsbs_limit',
			);

			return apply_filters( 'ywsbs_custom_fields_list', $custom_fields );
		}
	}
}


if ( ! function_exists( 'YWSBS_Product_Post_Type_Admin' ) ) {
	/**
	 * Return the instance of class
	 *
	 * @return YWSBS_Product_Post_Type_Admin
	 */
	function YWSBS_Product_Post_Type_Admin() { //phpcs:ignore
		return YWSBS_Product_Post_Type_Admin::get_instance();
	}
}
