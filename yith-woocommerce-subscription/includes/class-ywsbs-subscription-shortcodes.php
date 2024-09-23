<?php
/**
 * YWSBS_Subscription_Shortcode set all plugin shortcodes
 *
 * @class   YWSBS_Subscription_Shortcodes
 * @since   2.0.0
 * @author  YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.


if ( ! class_exists( 'YWSBS_Subscription_Shortcodes' ) ) {
	/**
	 * Implements the YWSBS_Subscription_Shortcodes class.
	 *
	 * @class   YWSBS_Subscription_Shortcodes
	 * @package YITH\Subscription
	 * @since   2.0.0
	 */
	class YWSBS_Subscription_Shortcodes {

		/**
		 * Constructor for the shortcode class
		 */
		public function __construct() {
			add_shortcode( 'ywsbs_my_account_subscriptions', array( __CLASS__, 'my_account_subscriptions_shortcode' ) );
		}

		/**
		 * Add subscription section on my-account page
		 *
		 * @param array $atts Attributes.
		 * @return  string
		 * @since   1.0.0
		 */
		public static function my_account_subscriptions_shortcode( $atts ) {

			$args = shortcode_atts(
				array(
					'page' => 1,
				),
				$atts
			);

			$all_subs      = YWSBS_Subscription_Helper()->get_subscriptions_by_user( get_current_user_id(), -1 );
			$max_pages     = ceil( count( $all_subs ) / 10 );
			$subscriptions = YWSBS_Subscription_Helper()->get_subscriptions_by_user( get_current_user_id(), $args['page'] );
			ob_start();
			wc_get_template(
				'myaccount/my-subscriptions-view.php',
				array(
					'subscriptions' => $subscriptions,
					'max_pages'     => $max_pages,
					'current_page'  => $args['page'],
				),
				'',
				YITH_YWSBS_TEMPLATE_PATH . '/'
			);
			return ob_get_clean();
		}
	}
}
