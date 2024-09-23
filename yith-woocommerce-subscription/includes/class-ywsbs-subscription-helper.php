<?php
/**
 * Implements YWSBS_Subscription_Helper Class
 *
 * @class   YWSBS_Subscription_Helper
 * @since   1.0.0
 * @author  YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YWSBS_Subscription_Helper' ) ) {

	/**
	 * Class YWSBS_Subscription_Helper
	 */
	class YWSBS_Subscription_Helper {
		use YITH_WC_Subscription_Singleton_Trait;

		/**
		 * Constructor
		 *
		 * Initialize plugin and registers actions and filters to be used
		 *
		 * @since  1.0.0
		 */
		protected function __construct() {}

		/**
		 * Return the subscription recurring price formatted
		 *
		 * @param YWSBS_Subscription $subscription Subscription.
		 * @param string             $tax_display Display tax.
		 * @param bool               $show_time_option Show time option.
		 * @param bool               $shipping Add shipping price to total.
		 *
		 * @return string
		 * @since  1.0.0
		 */
		public function get_formatted_recurring( $subscription, $tax_display = '', $show_time_option = true, $shipping = false ) {

			$price_time_option_string = ywsbs_get_price_per_string( $subscription->get( 'price_is_per' ), $subscription->get( 'price_time_option' ) );
			$tax_inc                  = get_option( 'woocommerce_prices_include_tax' ) === 'yes';

			if ( wc_tax_enabled() && ( 'incl' === get_option( 'woocommerce_tax_display_shop' ) || $tax_inc ) ) {
				$shipping_price = $shipping ? $subscription->get_order_shipping() + $subscription->get_order_shipping_tax() : 0;
				$sbs_price      = $subscription->get_line_total() + $subscription->get_line_tax() + $shipping_price;
			} else {
				$shipping_price = $shipping ? $subscription->get_order_shipping() : 0;
				$sbs_price      = $subscription->get_line_total();
			}

			$recurring  = wc_price( $sbs_price, array( 'currency' => $subscription->get( 'order_currency' ) ) );
			$recurring .= $show_time_option ? ' / ' . $price_time_option_string : '';

			$recurring = apply_filters_deprecated( 'ywsbs-recurring-price', array( $recurring, $subscription ), '2.0.0', 'ywsbs_recurring_price', 'This filter will be removed in the next major release' );

			return apply_filters( 'ywsbs_recurring_price', $recurring, $subscription );
		}

		/**
		 * Return the subscription max_length of a product.
		 *
		 * @param WC_Product $product Product.
		 * @param bool|array $subscription_info Subscription information.
		 *
		 * @return string
		 */
		public static function get_total_subscription_price( $product, $subscription_info ) {

			$max_length = self::get_subscription_product_max_length( $product );

			if ( ! $max_length ) {
				return '';
			}

			$recurring_price = $subscription_info && isset( $subscription_info['recurring_price'] ) ? $subscription_info['recurring_price'] : $product->get_price();

			$total_price = $recurring_price * $max_length;

			if ( ! empty( $subscription_info['price_is_per'] ) ) {
				$total_price = $total_price / $subscription_info['price_is_per'];
			}

			return $total_price;
		}


		/**
		 * Get the formatted period for price
		 *
		 * @param WC_Product $product Product.
		 * @param array      $subscription_info List of subscription parameters.
		 *
		 * @return string
		 */
		public static function get_subscription_max_length_formatted_for_price( $product, $subscription_info = false ) {

			$max_length = $subscription_info ? $subscription_info['max_length'] : self::get_subscription_product_max_length( $product );

			if ( empty( $max_length ) ) {
				return '';
			}

			$price_time_option    = $subscription_info ? $subscription_info['price_time_option'] : $product->get_meta( '_ywsbs_price_time_option' );
			$max_length_formatted = ywsbs_get_price_per_string( $max_length, $price_time_option, true );

			// APPLY_FILTER: ywsbs_subscription_max_length_formatted_for_price: to filter the formatted subscription period for price.
			return apply_filters( 'ywsbs_subscription_max_length_formatted_for_price', $max_length_formatted, $product );
		}


		/**
		 * Get all subscriptions of a user
		 *
		 * @param int $user_id User ID.
		 * @param int $page Page number.
		 *
		 * @return array
		 * @since  1.0.0
		 */
		public function get_subscriptions_by_user( $user_id, $page = -1 ) {

			$args = array(
				'post_type'  => YITH_YWSBS_POST_TYPE,
				'meta_key'   => 'user_id',  // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value' => $user_id,   // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			);

			if ( -1 === $page ) {
				$args['posts_per_page'] = -1;
			} else {
				$args['posts_per_page'] = apply_filters( 'ywsbs_num_of_subscription_on_a_page_my_account', 10 );
				$args['paged']          = $page;
			}

			$subscriptions = get_posts( $args );

			return $subscriptions;
		}

		/**
		 * Get the formatted period for price
		 *
		 * @param WC_Product $product Product.
		 * @param array      $subscription_info List of subscription parameters.
		 *
		 * @return string
		 */
		public static function get_subscription_period_for_price( $product, $subscription_info = false ) {

			if ( ! $product ) {
				return '';
			}

			$price_is_per             = $subscription_info ? $subscription_info['price_is_per'] : $product->get_meta( '_ywsbs_price_is_per' );
			$price_time_option        = $subscription_info ? $subscription_info['price_time_option'] : $product->get_meta( '_ywsbs_price_time_option' );
			$price_time_option_string = ywsbs_get_price_per_string( $price_is_per, $price_time_option, false );

			// APPLY_FILTER: ywsbs_subscription_period_for_price: to filter the formatted subscription period for price.
			return apply_filters( 'ywsbs_subscription_period_for_price', $price_time_option_string, $product, $subscription_info );
		}

		/**
		 * Return the subscription max_length of a product.
		 *
		 * @param WC_Product $product Product.
		 *
		 * @return string
		 */
		public static function get_subscription_product_max_length( $product ) {

			$max_length        = $product->get_meta( '_ywsbs_max_length' );
			$enable_max_length = $product->get_meta( '_ywsbs_enable_max_length' );

			// previous version.
			if ( empty( $enable_max_length ) ) {
				return $max_length;
			}

			return ( 'yes' === $enable_max_length ) ? $max_length : '';
		}


		/**
		 * Get the raw recurring price.
		 *
		 * @param WC_Product $product Product.
		 * @param array      $subscription_info List of subscription parameters.
		 *
		 * @return string
		 */
		public static function get_subscription_recurring_price( $product, $subscription_info = false ) {

			$recurring_price = $subscription_info && isset( $subscription_info['recurring_price'] ) ? $subscription_info['recurring_price'] : $product->get_price();

			// APPLY_FILTER: ywsbs_subscription_recurring_price: to filter raw recurring price.
			return apply_filters( 'ywsbs_subscription_recurring_price', $recurring_price, $product, $subscription_info );
		}
	}
}

/**
 * Unique access to instance of YWSBS_Subscription class
 *
 * @return YWSBS_Subscription_Helper
 */
function YWSBS_Subscription_Helper() {  //phpcs:ignore
	return YWSBS_Subscription_Helper::get_instance();
}
