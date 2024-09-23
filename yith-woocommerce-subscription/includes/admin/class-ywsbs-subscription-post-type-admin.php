<?php
/**
 * YWSBS_Subscription_Post_Type_Admin Class.
 *
 * Manage the subscription post type in admin.
 *
 * @class   YWSBS_Subscription_Post_Type_Admin
 * @since   1.0.0
 * @author  YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YWSBS_Subscription_Post_Type_Admin' ) ) {
	/**
	 * Class YWSBS_Subscription_Post_Type_Admin
	 */
	class YWSBS_Subscription_Post_Type_Admin {
		use YITH_WC_Subscription_Singleton_Trait;

		/**
		 * Flag to avoid multiple execution of code.
		 *
		 * @var bool
		 */
		protected $saved_metabox = false;

		/**
		 * Constructor
		 *
		 * Initialize actions and filters to be used
		 *
		 * @since 2.0.0
		 */
		protected function __construct() {

			add_action( 'add_meta_boxes', array( $this, 'show_info_subscription' ) );
			add_action( 'add_meta_boxes', array( $this, 'show_action_subscription' ) );

			add_action( 'add_meta_boxes', array( $this, 'show_product_subscription' ) );
			add_action( 'add_meta_boxes', array( $this, 'show_subscription_history' ) );

			add_action( 'admin_menu', array( $this, 'remove_publish_box' ) );

			add_action( 'admin_init', array( $this, 'remove_meta_fix' ) );
			add_action( 'save_post', array( $this, 'before_data_saving' ), 0, 2 );

			add_filter( 'yit_fw_metaboxes_type_args', array( $this, 'format_date_fields' ) );

			add_filter( 'yith_plugin_fw_metabox_class', array( $this, 'add_custom_metabox_class' ), 10, 2 );
			add_filter( 'is_protected_meta', array( $this, 'show_protected_meta_data' ), 10, 3 );
		}

		/**
		 * Show also private meta
		 *
		 * @param bool   $protected Protected.
		 * @param string $meta_key Meta key.
		 * @param string $meta_type Meta type.
		 * @return bool
		 */
		public function show_protected_meta_data( $protected, $meta_key, $meta_type ) {
			global $post;

			if ( $post && YITH_YWSBS_POST_TYPE === $post->post_type && '_edit_lock' !== $meta_key ) {
				$protected = false;
			}

			return $protected;
		}

		/**
		 * Add the metabox to show the info of subscription
		 *
		 * @return void
		 * @since  1.0.0
		 */
		public function show_info_subscription() {
			add_meta_box( 'ywsbs-info-subscription', esc_html__( 'Subscription Info', 'yith-woocommerce-subscription' ), array( $this, 'show_subscription_info_metabox' ), YITH_YWSBS_POST_TYPE, 'normal', 'high' );
		}

		/**
		 * Metabox to show the info of the current subscription.
		 *
		 * @param object $post WP_Post.
		 *
		 * @return void
		 * @since  1.0.0
		 */
		public function show_subscription_info_metabox( $post ) {
			$subscription = ywsbs_get_subscription( $post->ID );
			$args         = array(
				'subscription' => $subscription,
			);
			wc_get_template( 'admin/metabox/metabox_subscription_info_content.php', $args, '', YITH_YWSBS_TEMPLATE_PATH . '/' );
		}

		/**
		 * Add the metabox to show the action of subscription
		 *
		 * @return void
		 * @since  1.0.0
		 */
		public function show_action_subscription() {
			add_meta_box( 'ywsbs-action-subscription', esc_html__( 'Subscription Action', 'yith-woocommerce-subscription' ), array( $this, 'show_subscription_action_metabox' ), YITH_YWSBS_POST_TYPE, 'side', 'high' );
		}

		/**
		 * Metabox to show the action of the current subscription
		 *
		 * @access public
		 *
		 * @param object $post WP_Post.
		 *
		 * @return void
		 * @since  1.0.0
		 */
		public function show_subscription_action_metabox( $post ) {
			$subscription = ywsbs_get_subscription( $post->ID );
			$args         = array( 'subscription' => $subscription );
			wc_get_template( 'admin/metabox/metabox_subscription_action_content.php', $args, '', YITH_YWSBS_TEMPLATE_PATH . '/' );
		}

		/**
		 * Add the metabox to show the products of subscription
		 *
		 * @access public
		 *
		 * @return void
		 * @since  1.0.0
		 */
		public function show_product_subscription() {
			add_meta_box( 'ywsbs-product-subscription', esc_html__( 'Subscription Product', 'yith-woocommerce-subscription' ), array( $this, 'show_subscription_product_metabox' ), YITH_YWSBS_POST_TYPE, 'normal', 'high' );
		}

		/**
		 * Metabox to show the product detail of the current subscription
		 *
		 * @access public
		 *
		 * @param object $post WP_Post.
		 *
		 * @return void
		 *
		 * @since 1.0.0
		 */
		public function show_subscription_product_metabox( $post ) {
			$subscription = ywsbs_get_subscription( $post->ID );
			$product      = wc_get_product( ( $subscription->variation_id ) ? $subscription->variation_id : $subscription->product_id );
			$args         = array(
				'product'      => $product,
				'subscription' => $subscription,
			);
			wc_get_template( 'admin/metabox/metabox_subscription_product.php', $args, '', YITH_YWSBS_TEMPLATE_PATH . '/' );
		}

		/**
		 * Add the metabox to show the orders of subscription
		 *
		 * @access public
		 *
		 * @return void
		 * @since  1.0.0
		 */
		public function show_subscription_history() {
			add_meta_box( 'ywsbs-subscription-history', esc_html__( 'Subscription\'s History', 'yith-woocommerce-subscription' ), array( $this, 'show_subscription_history_metabox' ), YITH_YWSBS_POST_TYPE, 'normal', 'high' );
		}

		/**
		 * Meta-box to show the order history of the current subscription
		 *
		 * @access public
		 * @since  1.0.0
		 * @param object $post WP_Post.
		 * @return void
		 */
		public function show_subscription_history_metabox( $post ) {
			$subscription = ywsbs_get_subscription( $post->ID );

			$history     = (array) $subscription->get( 'payed_order_list' );
			$main_order  = (int) $subscription->get( 'order_id' );
			$renew_order = $subscription->get( 'renew_order' );

			$parent_resubscribe_subscription = $subscription->get( 'parent_subscription' );
			$child_resubscribe_subscription  = $subscription->get( 'child_subscription' );

			if ( ! in_array( $main_order, $history, true ) ) {
				$history = array_merge( array( $main_order ), $history );
			}

			if ( ! empty( $renew_order ) && ! in_array( (int) $renew_order, $history, true ) ) {
				array_push( $history, (int) $renew_order );
			}

			if ( ! empty( $parent_resubscribe_subscription ) ) {
				$parent_resubscribe_subscription = ywsbs_get_subscription( $parent_resubscribe_subscription );
				if ( $parent_resubscribe_subscription ) {
					$parent_resubscribe_order = (int) $parent_resubscribe_subscription->get( 'order_id' );
					if ( ! empty( $parent_resubscribe_order ) && ! in_array( $parent_resubscribe_order, $history, true ) ) {
						array_push( $history, ( $parent_resubscribe_order ) );
					}
				}
			}

			if ( ! empty( $child_resubscribe_subscription ) ) {
				$child_resubscribe_subscription = ywsbs_get_subscription( $child_resubscribe_subscription );
				if ( $child_resubscribe_subscription ) {
					$child_resubscribe_order = (int) $child_resubscribe_subscription->get( 'order_id' );
					if ( ! empty( $child_resubscribe_order ) && ! in_array( $child_resubscribe_order, $history, true ) ) {
						array_push( $history, ( $child_resubscribe_order ) );
					}
				}
			}

			$history = wc_get_orders(
				array(
					'post__in' => $history,
					'order_by' => 'id',
					'order'    => 'DESC',
				)
			);

			$args = array(
				'subscription' => $subscription,
				'history'      => $history,
			);

			wc_get_template( 'admin/metabox/metabox_subscription_history.php', $args, '', YITH_YWSBS_TEMPLATE_PATH . '/' );
		}


		/**
		 * Return the labels of schedule meta-box options.
		 *
		 * @return mixed|void
		 */
		public function get_schedule_data_subscription_fields() {
			$fields = array(
				'start_date'        => esc_html__( 'Start Date', 'yith-woocommerce-subscription' ),
				'payment_due_date'  => esc_html__( 'Payment Due Date', 'yith-woocommerce-subscription' ),
				'expired_date'      => esc_html__( 'Expired date', 'yith-woocommerce-subscription' ),
				'next_attempt_date' => esc_html__( 'Next attempt date', 'yith-woocommerce-subscription' ),
			);

			return apply_filters( 'ywsbs_schedule_data_subscription_fields', $fields );
		}

		/**
		 * Remove publish box from single page of the subscription.
		 *
		 * @access public
		 *
		 * @return void
		 * @since  1.0.0
		 */
		public function remove_publish_box() {
			remove_meta_box( 'submitdiv', YITH_YWSBS_POST_TYPE, 'side' );
		}

		/**
		 * Remove the item 'meta' from $_POST to avoid issue during the data saving
		 */
		public function remove_meta_fix() {
			// phpcs:disable WordPress.Security.NonceVerification.Missing
			if ( isset( $_POST['meta'] ) && isset( $_POST['post_type'] ) && YITH_YWSBS_POST_TYPE === sanitize_text_field( wp_unslash( $_POST['post_type'] ) ) ) {
				unset( $_POST['meta'] );
			}
			// phpcs:enable WordPress.Security.NonceVerification.Missing
		}

		/**
		 * Save meta data
		 *
		 * @param integer $post_id Post ID.
		 * @param WP_Post $post Current post.
		 * @return void
		 * @throws Exception Return Exception.
		 */
		public function before_data_saving( $post_id, $post ) {
			// phpcs:disable WordPress.Security.NonceVerification.Missing
			if ( $this->saved_metabox || empty( $_POST['post_ID'] ) || absint( $_POST['post_ID'] ) !== $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}

			// Do not save meta boxes for revisions or auto save.
			if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || is_int( wp_is_post_revision( $post ) ) || is_int( wp_is_post_autosave( $post ) ) ) {
				return;
			}

			$subscription = ywsbs_get_subscription( $post_id );
			$posted       = $_POST;

			// Save Billing and Shipping Meta if different from parent order.
			$meta            = array();
			$billing_fields  = ywsbs_get_order_fields_to_edit( 'billing' );
			$shipping_fields = ywsbs_get_order_fields_to_edit( 'shipping' );

			foreach ( $billing_fields as $key => $billing_field ) {
				$field_id = '_billing_' . $key;
				if ( ! isset( $posted[ $field_id ] ) ) {
					continue;
				}
				$meta[ $field_id ] = $posted[ $field_id ];
			}

			foreach ( $shipping_fields as $key => $shipping_field ) {
				$field_id = '_shipping_' . $key;
				if ( ! isset( $posted[ $field_id ] ) ) {
					continue;
				}
				$meta[ $field_id ] = $posted[ $field_id ];
			}

			if ( isset( $posted['customer_note'] ) ) {
				$meta['customer_note'] = $posted['customer_note'];
			}

			if ( isset( $posted['user_ID'], $posted['user_id'] ) && $posted['user_id'] !== $posted['user_ID'] ) {
				$meta['user_id'] = $posted['user_id'];
			}

			$meta && $subscription->update_subscription_meta( $meta );

			if ( ! empty( $posted['ywsbs_subscription_actions'] ) ) {
				$subscription = ywsbs_get_subscription( $post_id );
				$new_status   = $posted['ywsbs_subscription_actions'];
				YITH_WC_Subscription()->manual_change_status( $new_status, $subscription, 'administrator' );
			}

			$this->saved_metabox = true;
			// phpcs:enable WordPress.Security.NonceVerification.Missing
		}

		/**
		 * Format Timestamps into dates
		 *
		 * @access public
		 * @param array $args Arguments.
		 *
		 * @return array
		 * @since  1.0.0
		 */
		public function format_date_fields( $args ) {

			$date_fields = apply_filters( 'ywsbs_date_fields', array( 'start_date', 'payment_due_date', 'expired_date', 'cancelled_date', 'end_date' ) );

			if ( in_array( $args['args']['args']['id'], $date_fields ) ) { //phpcs:ignore
				$args['args']['args']['value'] = ( $time_stamp = $args['args']['args']['value'] ) ? date_i18n( 'Y-m-d', $time_stamp ) : ''; //phpcs:ignore
			}

			return $args;
		}


		/**
		 * Add new plugin-fw style.
		 *
		 * @param string  $class Class.
		 * @param WP_Post $post Post.
		 *
		 * @return string
		 */
		public function add_custom_metabox_class( $class, $post ) {

			$allow_post_types = array( YITH_YWSBS_POST_TYPE );

			if ( in_array( $post->post_type, $allow_post_types, true ) ) {
				$class .= ' ' . yith_set_wrapper_class();
			}
			return $class;
		}
	}
}
