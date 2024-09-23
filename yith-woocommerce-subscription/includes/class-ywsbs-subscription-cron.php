<?php //phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Implements YWSBS_Subscription_Cron Class
 *
 * @class   YWSBS_Subscription_Cron
 * @since   1.0.0
 * @author  YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YWSBS_Subscription_Cron' ) ) {
	/**
	 * Class YWSBS_Subscription_Cron
	 */
	class YWSBS_Subscription_Cron {
		use YITH_WC_Subscription_Singleton_Trait;

		/**
		 * Constructor
		 *
		 * Initialize plugin and registers actions and filters to be used
		 *
		 * @since  1.0.0
		 */
		protected function __construct() {

			add_action( 'wp_loaded', array( $this, 'set_cron' ), 30 );
			add_action( 'ywsbs_renew_orders', array( $this, 'renew_orders' ) );
			add_action( 'ywsbs_pay_renew_subscription_orders', array( $this, 'pay_renew_orders' ) );

			if ( ywsbs_delete_cancelled_pending_enabled() ) {
				add_action( 'ywsbs_trash_pending_subscriptions', array( $this, 'ywsbs_trash_pending_subscriptions' ) );
				add_action( 'ywsbs_trash_cancelled_subscriptions', array( $this, 'ywsbs_trash_cancelled_subscriptions' ) );
			}
		}

		/**
		 * Set cron.
		 */
		public function set_cron() {

			$ve         = get_option( 'gmt_offset' ) > 0 ? '+' : '-';
			$time_start = strtotime( '00:00 ' . $ve . get_option( 'gmt_offset' ) . ' HOURS' );

			if ( ! wp_next_scheduled( 'ywsbs_renew_orders' ) ) {
				wp_schedule_event( $time_start, 'hourly', 'ywsbs_renew_orders' );
			}

			if ( ! wp_next_scheduled( 'ywsbs_pay_renew_subscription_orders' ) ) {
				wp_schedule_event( $time_start, 'hourly', 'ywsbs_pay_renew_subscription_orders' );
			}

			if ( ywsbs_delete_cancelled_pending_enabled() ) {
				$trash_pending = get_option( 'ywsbs_trash_pending_subscriptions' );
				if ( isset( $trash_pending['number'] ) && ! empty( $trash_pending['number'] ) && ! wp_next_scheduled( 'ywsbs_trash_cancelled_subscriptions' ) ) {
					wp_schedule_event( $time_start, 'daily', 'ywsbs_trash_pending_subscriptions' );
				}

				$trash_cancelled = get_option( 'ywsbs_trash_cancelled_subscriptions' );
				if ( isset( $trash_cancelled['number'] ) && ! empty( $trash_cancelled['number'] ) && ! wp_next_scheduled( 'ywsbs_trash_cancelled_subscriptions' ) ) {
					wp_schedule_event( $time_start, 'daily', 'ywsbs_trash_cancelled_subscriptions' );
				}
			} else {
				wp_clear_scheduled_hook( 'ywsbs_trash_pending_subscriptions' );
				wp_clear_scheduled_hook( 'ywsbs_trash_cancelled_subscriptions' );
			}
		}


		/**
		 * Renew Order
		 *
		 * Create new order for active or in trial period subscription
		 */
		public function renew_orders() {

			global $wpdb;

			$to = time() + 86400;

			$query = $wpdb->prepare(
				"SELECT ywsbs_p.ID FROM {$wpdb->prefix}posts as ywsbs_p
                 INNER JOIN  {$wpdb->prefix}postmeta as ywsbs_pm ON ( ywsbs_p.ID = ywsbs_pm.post_id )
                 INNER JOIN  {$wpdb->prefix}postmeta as ywsbs_pm2 ON ( ywsbs_p.ID = ywsbs_pm2.post_id )
                 INNER JOIN  {$wpdb->prefix}postmeta as ywsbs_pm3 ON ( ywsbs_p.ID = ywsbs_pm3.post_id )
                 WHERE ( ywsbs_pm.meta_key='status' AND  ywsbs_pm.meta_value = 'active' )
                 AND ( ( ywsbs_pm3.meta_key='renew_order' AND ywsbs_pm3.meta_value = 0 ) OR ( ywsbs_pm3.meta_key='_renew_order' AND ywsbs_pm3.meta_value = 0 ) )
                 AND ywsbs_p.post_type = %s
                 AND ywsbs_p.post_status = 'publish'
                 AND ( ywsbs_pm2.meta_key='payment_due_date' AND ywsbs_pm2.meta_value < %d ) 
                 GROUP BY ywsbs_p.ID ORDER BY ywsbs_p.ID DESC
                ",
				YITH_YWSBS_POST_TYPE,
				$to
			);

			$subscriptions = $wpdb->get_col( $query ); //phpcs:ignore
			if ( ! empty( $subscriptions ) ) {
				foreach ( $subscriptions as $subscription_id ) {
					YWSBS_Subscription_Order()->renew_order( $subscription_id );
				}
			}
		}

		/**
		 * Pay the pending renew orders
		 *
		 * @since 4.0.0
		 */
		public function pay_renew_orders() {

			$status       = 'wc-' . YWSBS_Subscription_Order()->get_renew_order_status();
			$current_time = current_time( 'timestamp' ); // phpcs:ignore
			$from         = $current_time - DAY_IN_SECONDS;

			yith_subscription_log( '=======================', 'subscription_payment' );
			yith_subscription_log( 'Start Payment Renews cron at ' . current_time( 'mysql' ), 'subscription_payment' );
			yith_subscription_log( 'Search renew order with date < ' . gmdate( 'Y-m-d H:i:s', $from ), 'subscription_payment' );

			$renew_orders_for_first_time = wc_get_orders(
				array(
					'return'       => 'ids',
					'orderby'      => 'ID',
					'order'        => 'DESC',
					'status'       => $status,
					'date_created' => '<' . $from,
					'meta_query'   => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						array(
							'key'   => 'failed_attemps',
							'value' => '0',
						),
						array(
							'key'   => 'is_a_renew',
							'value' => 'yes',
						),
					),
				)
			);

			yith_subscription_log( 'Found ' . count( $renew_orders_for_first_time ) . ' new renew orders', 'subscription_payment' );

			$renew_failed_orders = wc_get_orders(
				array(
					'return'     => 'ids',
					'orderby'    => 'ID',
					'order'      => 'DESC',
					'status'     => $status,
					'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						array(
							'key'     => 'failed_attemps',
							'value'   => '0',
							'compare' => '>',
						),
						array(
							'key'   => 'is_a_renew',
							'value' => 'yes',
						),
						array(
							'key'     => 'next_payment_attempt',
							'value'   => $current_time,
							'compare' => '<=',
						),
					),
				)
			);

			yith_subscription_log( 'Found ' . count( $renew_failed_orders ) . ' failed renew orders', 'subscription_payment' );

			$renew_orders = array_merge( $renew_orders_for_first_time, $renew_failed_orders );

			foreach ( $renew_orders as $renew_order ) {
				YWSBS_Subscription_Order()->pay_renew_order( $renew_order );
			}

			yith_subscription_log( '=======================', 'subscription_payment' );
		}

		/**
		 * Trash pending subscriptions after a specific time.
		 *
		 * @since 1.4.0
		 */
		public function ywsbs_trash_pending_subscriptions() {
			global $wpdb;
			$trash_pending = get_option( 'ywsbs_trash_pending_subscriptions' );
			if ( ! ywsbs_delete_cancelled_pending_enabled() || ! isset( $trash_pending['number'] ) || empty( $trash_pending['number'] ) ) {
				return;
			}

			$time = strtotime( '-' . $trash_pending['number'] . ' ' . $trash_pending['unit'] );

			$subscriptions = $wpdb->get_results( //phpcs:ignore
				$wpdb->prepare(
					"SELECT ywsbs_p.ID FROM {$wpdb->prefix}posts as ywsbs_p
                 INNER JOIN  {$wpdb->prefix}postmeta as ywsbs_pm ON ( ywsbs_p.ID = ywsbs_pm.post_id )
                 WHERE ( ywsbs_pm.meta_key='status' AND  ywsbs_pm.meta_value = 'pending' )
                 AND ywsbs_p.post_type = %s
                 AND ywsbs_p.post_status = 'publish'
                 AND ywsbs_p.post_date < %s 
                 GROUP BY ywsbs_p.ID ORDER BY ywsbs_p.ID DESC
                ",
					YITH_YWSBS_POST_TYPE,
					gmdate( 'Y-m-d H:i:s', $time )
				)
			);

			if ( ! empty( $subscriptions ) ) {
				foreach ( $subscriptions as $subscription ) {
					$subscription_id = $subscription->ID;
					wp_trash_post( $subscription_id );
					do_action( 'ywsbs_subscription_trashed', $subscription_id );
				}
			}
		}

		/**
		 * Trash cancelled subscriptions after a specific time.
		 *
		 * @since 1.4.0
		 */
		public function ywsbs_trash_cancelled_subscriptions() {
			global $wpdb;
			$trash_cancelled = get_option( 'ywsbs_trash_cancelled_subscriptions' );
			if ( ! ywsbs_delete_cancelled_pending_enabled() || ! isset( $trash_cancelled['number'] ) || empty( $trash_cancelled['number'] ) ) {
				return;
			}

			$time = strtotime( '-' . $trash_cancelled['number'] . ' ' . $trash_cancelled['unit'] );

			$subscriptions = $wpdb->get_results( //phpcs:ignore
				$wpdb->prepare(
					"SELECT ywsbs_p.ID FROM {$wpdb->prefix}posts as ywsbs_p
                 INNER JOIN  {$wpdb->prefix}postmeta as ywsbs_pm ON ( ywsbs_p.ID = ywsbs_pm.post_id )
                 INNER JOIN  {$wpdb->prefix}postmeta as ywsbs_pm2 ON ( ywsbs_p.ID = ywsbs_pm2.post_id )
                 WHERE ( ywsbs_pm.meta_key='status' AND  ywsbs_pm.meta_value = 'cancelled' )
                 AND ywsbs_p.post_type = %s
                 AND ywsbs_p.post_status = 'publish'
                 AND ( ywsbs_pm2.meta_key='cancelled_date' AND  ywsbs_pm2.meta_value  < %d )
                 GROUP BY ywsbs_p.ID ORDER BY ywsbs_p.ID DESC
                ",
					YITH_YWSBS_POST_TYPE,
					$time
				)
			);

			if ( ! empty( $subscriptions ) ) {
				foreach ( $subscriptions as $subscription ) {
					$subscription_id = $subscription->ID;
					wp_trash_post( $subscription_id );
					do_action( 'ywsbs_subscription_trashed', $subscription_id );
				}
			}
		}
	}
}

/**
 * Unique access to instance of YWSBS_Subscription_Cron class
 *
 * @return YWSBS_Subscription_Cron
 */
function YWSBS_Subscription_Cron() { //phpcs:ignore
	return YWSBS_Subscription_Cron::get_instance();
}
