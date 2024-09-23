<?php //phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Implements YITH WooCommerce Subscription
 *
 * @class   YITH_WC_Subscription
 * @since   1.0.0
 * @author  YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YITH_WC_Subscription' ) ) {

	/**
	 * Class YITH_WC_Subscription
	 */
	class YITH_WC_Subscription {
		use YITH_WC_Subscription_Singleton_Trait;

		/**
		 * Subscription Admin.
		 *
		 * @var YITH_WC_Subscription_Admin
		 */
		public $admin;

		/**
		 * Subscription Frontend.
		 *
		 * @var YITH_WC_Subscription_Frontend
		 */
		public $frontend;

		/**
		 * Shortcodes.
		 *
		 * @var YWSBS_Subscription_Shortcodes
		 */
		public $shortcodes;

		/**
		 * Subscriptions endpoint
		 *
		 * @var string
		 */
		public static $endpoint = '';

		/**
		 * Subscriptions view endpoint
		 *
		 * @var string
		 */
		public static $view_endpoint = '';

		/**
		 * Constructor
		 *
		 * Initialize plugin and registers actions and filters to be used
		 *
		 * @since  1.0.0
		 */
		public function __construct() {

			$this->include_required();
			$this->load();

			YITH_WC_Subscription_Install::install();

			// Register Endpoints.
			add_action( 'init', array( $this, 'add_endpoint' ), 15 );

			/* general actions */
			add_filter( 'woocommerce_locate_core_template', array( $this, 'filter_woocommerce_template' ), 10, 3 );
			add_filter( 'woocommerce_locate_template', array( $this, 'filter_woocommerce_template' ), 10, 3 );

			// Change product prices.
			add_filter( 'woocommerce_get_price_html', array( $this, 'change_price_html' ), 10, 2 );
			add_filter( 'woocommerce_order_formatted_line_subtotal', array( $this, 'order_formatted_line_subtotal' ), 10, 3 );

			// Ensure a subscription is never in the cart with products.
			add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'cart_item_validate' ), 10, 3 );
		}

		/**
		 * Include required common functions.
		 * Must be available immediately.
		 *
		 * @since 3.0.0
		 * @return void
		 */
		protected function include_required() {
			include_once YITH_YWSBS_INC . 'functions-yith-wc-subscription.php';
			include_once YITH_YWSBS_INC . 'functions-yith-wc-subscription-product.php';
		}

		/**
		 * Add the endpoint for the pages in my account to manage the subscription list and view.
		 *
		 * @since 2.0.0
		 */
		public function add_endpoint() {

			self::$endpoint      = apply_filters( 'ywsbs_endpoint', 'my-subscription' );
			self::$view_endpoint = apply_filters( 'ywsbs_view_endpoint', 'view-subscription' );

			$endpoints = array(
				'subscriptions'     => self::$endpoint,
				'view-subscription' => self::$view_endpoint,
			);

			foreach ( $endpoints as $key => $endpoint ) {
				WC()->query->query_vars[ $key ] = $endpoint;
				add_rewrite_endpoint( $endpoint, WC()->query->get_endpoints_mask() );
			}
		}

		/**
		 * Change the status of subscription manually
		 *
		 * @param   string             $new_status    New Status.
		 * @param   YWSBS_Subscription $subscription  Subscription.
		 * @param   string             $from          Who wants to change the status.
		 *
		 * @return bool
		 * @since  1.0.0
		 */
		public function manual_change_status( $new_status, $subscription, $from = '' ) {
			switch ( $new_status ) {
				case 'active':
					if ( ! $subscription->can_be_active() ) {
						$this->add_notice( esc_html__( 'This subscription cannot be activated', 'yith-woocommerce-subscription' ), 'error' );
					} else {
						$subscription->update_status( 'active', $from );
						$this->add_notice( esc_html__( 'This subscription is now active', 'yith-woocommerce-subscription' ), 'success' );
					}
					break;
				case 'cancelled':
					if ( ! $subscription->can_be_cancelled() ) {
						$this->add_notice( esc_html__( 'This subscription cannot be cancelled', 'yith-woocommerce-subscription' ), 'error' );
					} else {
						// filter added to gateway payments.
						if ( ! apply_filters( 'ywsbs_cancel_recurring_payment', true, $subscription ) ) {
							$this->add_notice( esc_html__( 'This subscription cannot be cancelled', 'yith-woocommerce-subscription' ), 'error' );

							return false;
						}

						$subscription->update_status( 'cancelled', $from );
						$this->add_notice( esc_html__( 'This subscription is now cancelled', 'yith-woocommerce-subscription' ), 'success' );
					}
					break;

				default:
			}

			return false;
		}

		/**
		 * Print a WC message
		 *
		 * @param   string $message  Message to show.
		 * @param   string $type     Type od message.
		 *
		 * @since 1.0.0
		 */
		public function add_notice( $message, $type ) {
			if ( ! is_admin() ) {
				wc_add_notice( $message, $type );
			}
		}

		/**
		 * Include required core files used in admin and on the frontend.
		 *
		 * @since 2.0.0
		 */
		public function load() {

			if ( self::is_request( 'admin' ) ) {
				$this->admin = YITH_WC_Subscription_Admin::get_instance();
				YWSBS_Subscription_Post_Type_Admin::get_instance();
				YWSBS_Subscription_List_Table::get_instance();
				YWSBS_Product_Post_Type_Admin::get_instance();
				// Privacy.
				YWSBS_Subscription_Privacy::get_instance();
			}

			if ( self::is_request( 'frontend' ) ) {
				$this->frontend   = YITH_WC_Subscription_Frontend::get_instance();
				$this->shortcodes = new YWSBS_Subscription_Shortcodes();
				YITH_WC_Subscription_Limit::get_instance();
			}

			YWSBS_Subscription_Helper::get_instance();
			YWSBS_Subscription_Order::get_instance();
			YWSBS_Subscription_Cron::get_instance();

			// PayPal Standard.
			include_once YITH_YWSBS_INC . 'gateways/paypal/class-yith-wc-subscription-paypal.php';
			YWSBS_Subscription_Paypal();
		}

		/**
		 * Locate default templates of woocommerce in plugin, if exists
		 *
		 * @param   string $core_file      .
		 * @param   string $template       .
		 * @param   string $template_base  .
		 *
		 * @return string
		 * @since  1.0.0
		 */
		public function filter_woocommerce_template( $core_file, $template, $template_base ) {

			$located = yith_ywsbs_locate_template( $template );

			if ( $located ) {
				return $located;
			} else {
				return $core_file;
			}
		}

		/**
		 * Change html price.
		 *
		 * @param   float      $price    Price.
		 * @param   WC_Product $product  Product.
		 *
		 * @return string
		 */
		public function change_price_html( $price, $product ) {

			if ( ! $this->is_subscription( $product->get_id() ) ) {
				return $price;
			}

			$price_is_per             = $product->get_meta( '_ywsbs_price_is_per' );
			$price_time_option        = $product->get_meta( '_ywsbs_price_time_option' );
			$price_time_option_string = ywsbs_get_price_per_string( $price_is_per, $price_time_option );

			$price .= ' / ' . $price_time_option_string;

			return $price;
		}

		/**
		 * Check if the product is a subscription.
		 *
		 * @param   int| WC_Product $product  Product.
		 *
		 * @return bool
		 * @internal param $product_id
		 */
		public function is_subscription( $product ) {
			if ( is_numeric( $product ) ) {
				$product = wc_get_product( $product );
			}

			if ( ! $product instanceof WC_Product ) {
				return false;
			}

			$is_subscription = $product->get_meta( '_ywsbs_subscription' );
			$price_is_per    = $product->get_meta( '_ywsbs_price_is_per' );

			return apply_filters( 'ywsbs_is_subscription', ( 'yes' === $is_subscription && '' !== $price_is_per ), $product->get_id() );
		}

		/**
		 * Check if in the cart there are subscription that needs shipping
		 *
		 * @return bool
		 * @since  1.0.0
		 */
		public function cart_has_subscription_with_shipping() {

			$cart_has_subscription_with_shipping = false;

			$cart_contents = WC()->cart->get_cart();

			if ( ! isset( $cart_contents ) || empty( $cart_contents ) ) {
				return $cart_has_subscription_with_shipping;
			}

			foreach ( $cart_contents as $cart_item ) {
				$product = $cart_item['data'];
				if ( $this->is_subscription( $product->id ) && $product->needs_shipping() ) {
					$cart_has_subscription_with_shipping = true;
				}
			}

			return apply_filters( 'ywsbs_cart_has_subscription_with_shipping', $cart_has_subscription_with_shipping );
		}

		/**
		 * Validate the cart item.
		 *
		 * @param   bool $valid       Is valid.
		 * @param   int  $product_id  Product id.
		 * @param   int  $quantity    Quantity.
		 *
		 * @return mixed
		 */
		public function cart_item_validate( $valid, $product_id, $quantity ) {
			$item_key = $this->cart_has_subscriptions();
			if ( $this->is_subscription( $product_id ) && $item_key ) {
				$this->clean_cart_from_subscriptions( $item_key );
				$message = __( 'A subscription has been removed from your cart. You cannot purchases different subscriptions at the same time.', 'yith-woocommerce-subscription' );
				wc_add_notice( $message, 'notice' );
			}

			return $valid;
		}

		/**
		 * Return the ids of user subscriptions
		 *
		 * @param   int    $user_id  User ID.
		 * @param   string $status   Status of Subscription.
		 *
		 * @return array|int
		 */
		public function get_user_subscriptions( $user_id, $status = '' ) {

			$args = array(
				'post_type'      => YITH_YWSBS_POST_TYPE,
				'posts_per_page' => - 1,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => 'user_id',
						'value'   => $user_id,
						'compare' => '=',
					),
				),
			);

			if ( ! empty( $status ) ) {
				$args['meta_query'][] = array(
					'key'     => 'status',
					'value'   => $status,
					'compare' => '=',
				);
			}

			$posts = get_posts( $args );

			return $posts ? wp_list_pluck( $posts, 'ID' ) : 0;
		}


		/**
		 * Removes all subscription products from the shopping cart.
		 *
		 * @param   string $item_key  Item key.
		 *
		 * @since 1.0
		 */
		public function clean_cart_from_subscriptions( $item_key ) {
			WC()->cart->set_quantity( $item_key, 0 );
		}

		/**
		 * Check if in the cart there are subscription
		 *
		 * @return bool/int
		 * @since  1.0.0
		 */
		public function cart_has_subscriptions() {
			$contents = WC()->cart->cart_contents;
			if ( ! empty( $contents ) ) {
				foreach ( $contents as $item_key => $item ) {
					if ( $this->is_subscription( $item['product_id'] ) ) {
						return $item_key;
					}
				}
			}

			return false;
		}

		/**
		 * Format the line subtotal
		 *
		 * @param   float    $subtotal  Subtotal.
		 * @param   array    $item      Item.
		 * @param   WC_Order $order     Order.
		 *
		 * @return string
		 */
		public function order_formatted_line_subtotal( $subtotal, $item, $order ) {

			$product_id = $item['product_id'];
			$product    = wc_get_product( $product_id );

			if ( ! $this->is_subscription( $product ) ) {
				return $subtotal;
			}

			$price_is_per             = $product->get_meta( '_ywsbs_price_is_per' );
			$price_time_option        = $product->get_meta( '_ywsbs_price_time_option' );
			$price_time_option_string = ywsbs_get_price_per_string( $price_is_per, $price_time_option );
			$subtotal                .= ' / ' . $price_time_option_string;

			return apply_filters( 'ywsbs_order_formatted_line_subtotal', $subtotal, $item, $this, $product );
		}

		/**
		 * What type of request is this?
		 *
		 * @param   string $type  admin, ajax, cron or frontend.
		 *
		 * @return bool
		 */
		public static function is_request( $type ) {
			switch ( $type ) {
				case 'admin':
					return is_admin() && ! defined( 'DOING_AJAX' ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX && ( ! isset( $_REQUEST['context'] ) || ( isset( $_REQUEST['context'] ) && 'frontend' !== $_REQUEST['context'] ) ) ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
				case 'ajax':
					return defined( 'DOING_AJAX' );
				case 'frontend':
					return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
			}

			return false;
		}


		/**
		 * Check if in the order there are subscription
		 *
		 * @param   int $order_id  Order id.
		 *
		 * @return bool
		 * @since  1.0.0
		 */
		public function order_has_subscription( $order_id ) {

			$order       = wc_get_order( $order_id );
			$order_items = $order->get_items();

			if ( empty( $order_items ) ) {
				return false;
			}

			foreach ( $order_items as $key => $order_item ) {
				$id = ( $order_item['variation_id'] ) ? $order_item['variation_id'] : $order_item['product_id'];

				if ( YITH_WC_Subscription()->is_subscription( $id ) ) {
					return true;
				}
			}

			return false;
		}
	}
}

/**
 * Unique access to instance of YITH_WC_Subscription class
 *
 * @return \YITH_WC_Subscription
 */
function YITH_WC_Subscription() { //phpcs:ignore
	return YITH_WC_Subscription::get_instance();
}
