<?php
/**
 * YWSBS_Subscription_My_Account Class.
 *
 * @class   YWSBS_Subscription_My_Account
 * @since   1.0.0
 * @author  YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YWSBS_Subscription_My_Account' ) ) {
	/**
	 * Class YWSBS_Subscription_My_Account
	 */
	class YWSBS_Subscription_My_Account {
		use YITH_WC_Subscription_Singleton_Trait;

		/**
		 * Subscription actions arguments
		 *
		 * @var array
		 */
		protected $subscription_actions_args = array();

		/**
		 * Constructor
		 *
		 * Initialize plugin and registers actions and filters to be used
		 *
		 * @since  1.0.0
		 */
		public function __construct() {

			// Endpoints.
			add_action( 'woocommerce_account_subscriptions_endpoint', array( $this, 'subscriptions_page' ), 1 );
			add_action( 'woocommerce_account_view-subscription_endpoint', array( $this, 'load_subscription_detail_page' ), 1 );

			add_filter( 'woocommerce_account_menu_items', array( $this, 'add_subscription_menu_item' ), 20 );
			add_filter( 'woocommerce_account_menu_item_classes', array( $this, 'set_subscription_menu_active_on_view_subscription' ), 10, 2 );

			add_filter( 'woocommerce_endpoint_subscriptions_title', array( $this, 'load_subscriptions_title' ) );
			add_filter( 'woocommerce_endpoint_view-subscription_title', array( $this, 'load_subscriptions_title' ) );

			// View Subscription Actions.
			add_action( 'woocommerce_order_needs_payment', array( $this, 'check_order_needs_payment' ), 10, 3 );

			add_action( 'init', array( $this, 'init' ), 30 );
		}

		/**
		 * Check if the order can be paid on my account page
		 *
		 * @param bool     $needs_payment The order needs payment.
		 * @param WC_Order $order Order.
		 * @param array    $valid_order_statuses Status valid to pay an order.
		 * @return bool
		 */
		public function check_order_needs_payment( $needs_payment, $order, $valid_order_statuses ) {
			if ( $needs_payment ) {
				return true;
			}

			$subscriptions = $order->get_meta( 'subscriptions' );

			return ! empty( $subscriptions ) && in_array( $order->get_status(), $valid_order_statuses, true );
		}

		/**
		 * Init method to set proteo icon.
		 */
		public function init() {
			if ( defined( 'YITH_PROTEO_VERSION' ) ) {
				add_filter( 'yith_proteo_myaccount_custom_icon', array( $this, 'customize_my_account_proteo_icon' ), 10, 2 );
			}
		}

		/**
		 * Active the subscription menu inside the view subscription page.
		 *
		 * @param array  $classes Class list.
		 * @param string $endpoint Current item menu.
		 *
		 * @return array
		 */
		public function set_subscription_menu_active_on_view_subscription( $classes, $endpoint ) {
			global $wp;

			if ( YITH_WC_Subscription::$endpoint === $endpoint && isset( $wp->query_vars['view-subscription'] ) ) {
				array_push( $classes, 'is-active' );
			}

			return $classes;
		}

		/**
		 * Change the title of the endpoint.
		 *
		 * @param string $title The endpoint title.
		 * @return string
		 * @since 1.0.0
		 */
		public function load_subscriptions_title( $title ) {
			return esc_html__( 'Your Subscriptions', 'yith-woocommerce-subscription' );
		}

		/**
		 * Save My Account Address.
		 *
		 * @param int    $user_id User ID being saved.
		 * @param string $load_address Type of address e.g. billing or shipping.
		 */
		public function my_account_save_address( $user_id, $load_address ) {

			$posted = $_REQUEST; // phpcs:ignore

			$fields = WC()->countries->get_address_fields( esc_attr( $posted[ $load_address . '_country' ] ), $load_address . '_' );

			if ( isset( $posted['ywsbs_edit_address_to_subscription'] ) ) {
				// edit the address to single subscription.
				$subscription_id = $posted['ywsbs_edit_address_to_subscription'];
				$subscription    = ywsbs_get_subscription( $subscription_id );
				$meta            = array();
				if ( $subscription->get_user_id() === $user_id ) {

					foreach ( $fields as $key => $item ) {
						if ( isset( $posted[ $key ] ) ) {

							$meta[ '_' . $key ] = $posted[ $key ];
						}
					}
				}

				! empty( $meta ) && $subscription->update_subscription_meta( $meta );

				wp_safe_redirect( ywsbs_get_view_subscription_url( $subscription_id ) );
				exit();

			} elseif ( isset( $posted['change_subscriptions_addresses'] ) ) {
				// edit the address to all subscriptions.
				$subscriptions = YITH_WC_Subscription()->get_user_subscriptions( $user_id, 'active' );
				if ( $subscriptions ) {
					foreach ( $subscriptions as $sub_id ) {
						$sub  = ywsbs_get_subscription( $sub_id );
						$meta = array();

						foreach ( $fields as $key => $item ) {
							if ( isset( $posted[ $key ] ) ) {
								$meta[ '_' . $key ] = $posted[ $key ];
							}
						}

						! empty( $meta ) && $sub->update_subscription_meta( $meta );
					}
				}
			}
		}


		/**
		 * Load the page of subscription
		 *
		 * @since 1.0.0
		 */
		public function load_subscription_detail_page() {
			global $wp;

			if ( ! is_page( wc_get_page_id( 'myaccount' ) ) || ! isset( $wp->query_vars['view-subscription'] ) ) {
				return;
			}

			$this->view_subscription();
		}

		/**
		 * Load the page with subscriptions
		 *
		 * @param int $current_page Current page.
		 * @since 1.0.0
		 */
		public function subscriptions_page( $current_page ) {
			global $wp;

			$current_page = empty( $current_page ) ? 1 : absint( $current_page );

			if ( ! is_page( wc_get_page_id( 'myaccount' ) ) || ! isset( $wp->query_vars['subscriptions'] ) ) {
				return;
			}

			echo YWSBS_Subscription_Shortcodes::my_account_subscriptions_shortcode( array( 'page' => $current_page ) ); // phpcs:ignore
		}

		/**
		 * Show the subscription detail
		 *
		 * @since 1.0.0
		 */
		public function view_subscription() {
			global $wp;
			if ( ! is_user_logged_in() ) {
				wc_get_template( 'myaccount/form-login.php', null, '', YITH_YWSBS_TEMPLATE_PATH . '/' );
			} else {
				$subscription_id = $wp->query_vars['view-subscription'];
				$subscription    = ywsbs_get_subscription( $subscription_id );

				wc_get_template(
					'myaccount/view-subscription.php',
					array(
						'subscription' => $subscription,
						'user'         => get_user_by( 'id', get_current_user_id() ),
					),
					'',
					YITH_YWSBS_TEMPLATE_PATH . '/'
				);
			}
		}


		/**
		 * Return all it is necessary to set the actions inside the subscription detail page.
		 *
		 * @param YWSBS_Subscription $subscription Current Subscription.
		 * @return array|mixed|void
		 */
		public function get_subscription_action_args( $subscription ) {

			if ( isset( $this->subscription_actions_args[ $subscription->get_id() ] ) ) {
				return $this->subscription_actions_args[ $subscription->get_id() ];
			}

			$style  = get_option( 'ywsbs_subscription_action_style', 'buttons' );
			$pause  = false;
			$cancel = false;
			$resume = false;

			if ( $subscription->can_be_cancelled() ) {

				$dropdown_text = '';

				if ( 'dropdown' === $style ) {
					$dropdown_text = get_option( 'ywsbs_text_cancel_subscription_dropdown' );
				}

				$modal_cancel_text = get_option( 'ywsbs_text_cancel_subscription_modal' );

				$cancel = array(
					'dropdown_text'      => $dropdown_text,
					'modal_text'         => $modal_cancel_text,
					'button_label'       => esc_html__( 'Cancel', 'yith-woocommerce-subscription' ),
					'modal_button_label' => get_option( 'ywsbs_text_cancel_subscription_button' ),
					'close_modal_button' => get_option( 'ywsbs_text_close_modal' ),
					'nonce'              => wp_create_nonce( 'ywsbs_cancel_subscription' ),
				);
			}

			if ( ! $cancel && ! $pause && ! $resume ) {
				return false;
			}

			$args = array(
				'style'        => get_option( 'ywsbs_subscription_action_style', 'buttons' ),
				'subscription' => $subscription,
				'pause'        => $pause,
				'cancel'       => $cancel,
				'resume'       => $resume,
			);

			$this->subscription_actions_args[ $subscription->get_id() ] = $args;

			return $args;
		}



		/**
		 * Add the menu item on WooCommerce My account Menu
		 * before the Logout item menu.
		 *
		 * @param array $wc_menu WooCommerce menu list.
		 *
		 * @return mixed
		 */
		public function add_subscription_menu_item( $wc_menu ) {

			if ( isset( $wc_menu['customer-logout'] ) ) {
				$logout = $wc_menu['customer-logout'];
				unset( $wc_menu['customer-logout'] );
			}

			$wc_menu['subscriptions'] = esc_html__( 'Subscriptions', 'yith-woocommerce-subscription' );

			if ( isset( $logout ) ) {
				$wc_menu['customer-logout'] = $logout;
			}

			return $wc_menu;
		}


		/**
		 * Change the icon inside my account on Proteo Theme.
		 *
		 * @param string $icon Icon.
		 * @param string $endpoint Endpoint.
		 *
		 * @return string
		 */
		public function customize_my_account_proteo_icon( $icon, $endpoint ) {

			if ( 'subscriptions' === $endpoint ) {
				return '<span class="yith-proteo-myaccount-icons ywsbs-icon ywsbs-icon-dollar lnr"></span>';
			}

			return $icon;
		}


		/**
		 * Add subscription section on my-account page
		 *
		 * @return  string
		 * @deprecated 2.0.0
		 * @since   1.0.0
		 */
		public function my_account_subscriptions_shortcode() {
			_deprecated_function( 'YWSBS_Subscription_My_Account::my_account_subscriptions_shortcode', '2.0.0', 'YWSBS_Subscription_Shortcodes::my_account_subscriptions_shortcode' );
			return YWSBS_Subscription_Shortcodes::my_account_subscriptions_shortcode();
		}
	}
}

/**
 * Unique access to instance of YWSBS_Subscription_My_Account class
 *
 * @return YWSBS_Subscription_My_Account
 */
function YWSBS_Subscription_My_Account() { // phpcs:ignore
	return YWSBS_Subscription_My_Account::get_instance();
}
