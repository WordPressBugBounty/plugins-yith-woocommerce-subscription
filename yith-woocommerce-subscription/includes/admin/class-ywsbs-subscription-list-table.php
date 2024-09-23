<?php
/**
 * YWSBS_Subscription_List_Table Class.
 *
 * Manage the subscription list table.
 *
 * @class   YWSBS_Subscription_List_Table
 * @since   1.0.0
 * @author YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YWSBS_Subscription_List_Table' ) ) {
	/**
	 * Class YWSBS_Subscription_List_Table
	 */
	class YWSBS_Subscription_List_Table {
		use YITH_WC_Subscription_Singleton_Trait;

		/**
		 * Store the subscription status counter query results.
		 *
		 * @var array
		 */
		protected $subscription_status = array();

		/**
		 * Constructor
		 *
		 * Initialize actions and filters to be used
		 *
		 * @since 2.0.0
		 */
		private function __construct() {

			add_filter( 'manage_' . YITH_YWSBS_POST_TYPE . '_posts_columns', array( $this, 'manage_list_columns' ) );
			add_action( 'manage_' . YITH_YWSBS_POST_TYPE . '_posts_custom_column', array( $this, 'render_list_columns' ), 10, 2 );
			add_filter( 'manage_edit-' . YITH_YWSBS_POST_TYPE . '_sortable_columns', array( $this, 'sortable_columns' ) );
			add_filter( 'request', array( $this, 'sort_columns' ) );
			add_action( 'init', array( $this, 'set_counter' ), 30 );

			// filters.
			add_action( 'restrict_manage_posts', array( $this, 'render_filters' ), 10, 1 );
			add_action( 'pre_get_posts', array( $this, 'filter_subscriptions' ), 10, 1 );

			// filter subscription status links.
			add_filter( 'views_edit-' . YITH_YWSBS_POST_TYPE, array( $this, 'subscription_status_filter' ), 10 );

			// filter row action links.
			add_filter( 'post_row_actions', array( $this, 'manage_row_actions' ), 10, 2 );

			// bulk action.
			add_filter( 'bulk_actions-edit-' . YITH_YWSBS_POST_TYPE, array( $this, 'define_bulk_action' ), 100 );

			// blank page.
			add_action( 'manage_posts_extra_tablenav', array( $this, 'maybe_render_blank_state' ) );

			// export subscriptions.
			add_action( 'admin_action_ywsbs_export_subscription', array( $this, 'export_subscriptions_via_csv' ) );

			add_filter( 'default_hidden_columns', array( $this, 'default_hidden_columns' ), 10, 2 );
		}

		/**
		 * Set the counter of status.
		 */
		public function set_counter() {
			$this->subscription_status = $this->get_subscription_status_counter();
		}

		/**
		 * Manage the columns
		 *
		 * @param array $columns Columns.
		 *
		 * @return array
		 */
		public function manage_list_columns( $columns ) {

			$columns = array(
				'cb'                   => '<input type="checkbox" />',
				'info'                 => esc_html__( 'Subscription', 'yith-woocommerce-subscription' ),
				'start_date'           => esc_html__( 'Started on', 'yith-woocommerce-subscription' ),
				'customer'             => esc_html__( 'Customer', 'yith-woocommerce-subscription' ),
				'recurring_amount'     => esc_html__( 'Recurring', 'yith-woocommerce-subscription' ),
				'payment_due_date'     => esc_html__( 'Payment due', 'yith-woocommerce-subscription' ),
				'end_date'             => esc_html__( 'Ended on', 'yith-woocommerce-subscription' ),
				'expired_date'         => esc_html__( 'Expiry date', 'yith-woocommerce-subscription' ),
				'rates_payed'          => esc_html__( 'Renewals', 'yith-woocommerce-subscription' ),
				'payment_method_title' => esc_html__( 'Payment method', 'yith-woocommerce-subscription' ),
				'failed_attempts'      => esc_html__( 'Failed attempts', 'yith-woocommerce-subscription' ),
				'status'               => esc_html__( 'Status', 'yith-woocommerce-subscription' ),
			);
			return $columns;
		}


		/**
		 * Render the columns
		 *
		 * @param array $column          Column.
		 * @param int   $subscription_id Subscription id.
		 *
		 * @return void
		 */
		public function render_list_columns( $column, $subscription_id ) {

			$subscription = ywsbs_get_subscription( $subscription_id );

			switch ( $column ) {
				case 'cb':
					printf(
						wp_kses_post( '<input type="checkbox" name="ywsbs_subscription_ids[]" class="check_subscription" value="%s" />' ),
						esc_html( $subscription_id )
					);
					break;
				case 'info':
					$url = ywsbs_get_view_subscription_url( $subscription_id, true );
					printf( '<a href="%1$s">%2$s - %3$s</a>', esc_url( $url ), esc_html( $subscription->get_number() ), esc_html( $subscription->get( 'product_name' ) ) );
					break;
				case 'start_date':
					$start_date = $subscription->get( 'start_date' );
					echo esc_html( ( $start_date ) ? date_i18n( wc_date_format(), $start_date ) : '-' );
					break;
				case 'recurring_amount':
					$recurring = apply_filters( 'ywsbs_get_recurring_totals', $subscription->get( 'subscription_total' ), $subscription_id );
					echo wp_kses_post( wc_price( $recurring, array( 'currency' => $subscription->get( 'order_currency' ) ) ) );
					break;
				case 'customer':
					$customer = YWSBS_Subscription_User::get_user_info_for_subscription_list( $subscription );
					echo wp_kses_post( $customer );
					break;
				case 'payment_due_date':
					$payment_due_date = $subscription->get( 'payment_due_date' );
					echo esc_html( ( $payment_due_date ) ? date_i18n( wc_date_format(), $payment_due_date ) : '-' );
					break;
				case 'end_date':
					$end_date = $subscription->get( 'end_date' );
					echo esc_html( ( $end_date ) ? date_i18n( wc_date_format(), $end_date ) : '-' );
					break;
				case 'expired_date':
					$expired_date = $subscription->get( 'expired_date' );
					echo esc_html( ( $expired_date ) ? date_i18n( wc_date_format(), $expired_date ) : '-' );
					break;
				case 'rates_payed':
					$paid_rates = $subscription->get_paid_rates();
					echo esc_html( empty( $paid_rates ) ? '-' : $paid_rates );
					break;
				case 'payment_method_title':
					echo esc_html( $subscription->get( 'payment_method_title' ) );
					break;
				case 'failed_attempts':
					$parent_order    = $subscription->get_order();
					$failed_attempts = $parent_order ? $parent_order->get_meta( 'failed_attemps' ) : false;
					$failed_attempts = $failed_attempts ? $failed_attempts : 0;
					$payment_method  = $subscription->get( 'payment_method' );
					$attempts_list   = ywsbs_get_max_failed_attempts_list();

					$failed_attempts .= isset( $attempts_list[ $payment_method ] ) ? '/' . $attempts_list[ $payment_method ] : '';
					echo esc_html( $failed_attempts );
					break;
				case 'status':
					$subscription_status_list = ywsbs_get_status();
					$status                   = $subscription->get_status();
					$subscription_status      = $subscription_status_list[ $status ];
					printf( '<span class="status %1$s">%2$s</span>', esc_attr( $subscription->get_status() ), esc_html( $subscription_status ) );
					break;
				default:
					echo '';
			}
		}

		/**
		 * Render the columns
		 *
		 * @param array $columns Column.
		 *
		 * @return array
		 */
		public function sortable_columns( $columns ) {

			$columns = array(
				'info'                 => 'product_name',
				'start_date'           => 'start_date',
				'recurring_amount'     => 'line_total',
				'payment_due_date'     => 'payment_due_date',
				'end_date'             => 'end_date',
				'expired_date'         => 'expired_date',
				'payment_method_title' => 'payment_method_title',
				'status'               => 'status',
				'rates_payed'          => 'rates_payed',
			);

			return $columns;
		}

		/**
		 * Sort columns
		 *
		 * @param array $vars Array of vars.
		 *
		 * @return array
		 */
		public function sort_columns( $vars ) {
			if ( isset( $vars['post_type'] ) && YITH_YWSBS_POST_TYPE === $vars['post_type'] ) {
				switch ( $vars['orderby'] ) {
					case 'product_name':
					case 'payment_method_title':
					case 'status':
						$vars['meta_key'] = $vars['orderby']; //phpcs:ignore
						$vars['orderby']  = 'meta_value';
						break;
					case 'start_date':
					case 'line_total':
					case 'payment_due_date':
					case 'end_date':
					case 'expired_date':
					case 'rates_payed':
						$vars['meta_key'] = $vars['orderby']; //phpcs:ignore
						$vars['orderby']  = 'meta_value_num';
						break;
				}
			}

			return $vars;
		}

		/**
		 * Add filter.
		 *
		 * @param string $post_type Post Type.
		 */
		public function render_filters( $post_type ) {
			if ( YITH_YWSBS_POST_TYPE === $post_type ) {
				$this->render_payment_method_filter();
				$this->render_customer_filter();
				$this->render_reset_filter();
			}
		}

		/**
		 * Render reset filter.
		 *
		 * @return void
		 */
		protected function render_reset_filter() {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			// Check if a filter is applied.
			if ( empty( $_GET['m'] ) && empty( $_GET['payment'] ) && empty( $_GET['customer_user'] ) ) {
				return;
			}

			printf( '<a href="%1$s" class="button action reset">%2$s</a>', esc_url( admin_url( 'edit.php?post_type=' . YITH_YWSBS_POST_TYPE ) ), esc_html_x( 'Reset filters', 'Button label', 'yith-woocommerce-subscription' ) );
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
		}

		/**
		 * Render customer filter.
		 */
		protected function render_customer_filter() {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			echo wp_kses_post( '<div class="alignleft actions yith-search-customer-wrapper">' );

			$user_string = '';
			$customer_id = '';

			if ( ! empty( $_REQUEST['customer_user'] ) ) {
				$customer_id = absint( $_REQUEST['customer_user'] );
				$user        = get_user_by( 'id', $customer_id );
				$user_string = $user ? esc_html( $user->display_name ) . ' (#' . absint( $user->ID ) . ' &ndash; ' . esc_html( $user->user_email ) : '';
			}

			$args = array(
				'type'             => 'hidden',
				'class'            => 'wc-customer-search',
				'id'               => 'customer_user',
				'name'             => 'customer_user',
				'data-placeholder' => esc_html__( 'Show all customers', 'yith-woocommerce-subscription' ),
				'data-allow_clear' => true,
				'data-selected'    => array( $customer_id => esc_attr( $user_string ) ),
				'data-multiple'    => false,
				'value'            => $customer_id,
				'style'            => 'width:200px',
			);

			yit_add_select2_fields( $args );

			echo '</div>';
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
		}

		/**
		 * Render payment method filter.
		 */
		protected function render_payment_method_filter() {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			global $wpdb;

			$current_payment = ! empty( $_GET['payment'] ) ? sanitize_text_field( wp_unslash( $_GET['payment'] ) ) : '';
			$gateways        = WC()->payment_gateways()->get_available_payment_gateways();
			$query           = $wpdb->prepare(
				"SELECT count(*) as counter, ywsbs_pm.meta_value as payment_method FROM {$wpdb->posts} as ywsbs_p INNER JOIN {$wpdb->postmeta} as ywsbs_pm ON ( ywsbs_p.ID = ywsbs_pm.post_id )
WHERE ywsbs_p.post_type = %s AND ywsbs_pm.meta_key = 'payment_method' GROUP BY ywsbs_pm.meta_value", //phpcs:ignore
				YITH_YWSBS_POST_TYPE //phpcs:ignore
			);

			$subscription_gateways = $wpdb->get_results( apply_filters( 'ywsbs_payment_method_filter_query', $query ) );  //phpcs:ignore

			?>
			<div class="alignleft actions">
				<select name="payment" id="subscription_payment">
					<option
						value=""><?php esc_html_e( 'All payment methods', 'yith-woocommerce-subscription' ); ?></option>
					<?php
					foreach ( $subscription_gateways as $subscription_gateway ) :
						$payment_key = $subscription_gateway->payment_method;
						$counter     = $subscription_gateway->counter;
						$gateway     = isset( $gateways[ $payment_key ] ) ? $gateways[ $payment_key ]->title : $payment_key;
						?>
						<option
							value="<?php echo esc_attr( $payment_key ); ?>" <?php selected( $payment_key, $current_payment ); ?> >
							<?php printf( '%s (%d)', esc_html( $gateway ), esc_html( $counter ) ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>

			<?php
			// phpcs:enabled WordPress.Security.NonceVerification.Recommended
		}

		/**
		 * Filter subscriptions
		 *
		 * @param WP_Query $query WP_Query.
		 */
		public function filter_subscriptions( $query ) {

			global $wpdb;
			if ( $query->is_main_query() && isset( $query->query['post_type'] ) && YITH_YWSBS_POST_TYPE === $query->query['post_type'] ) {
				$meta_query = ! ! $query->get( 'meta_query' ) ? $query->get( 'meta_query' ) : array();
				$changed    = false;
				$posted     = $_REQUEST; //phpcs:ignore

				if ( ! empty( $posted['status'] ) && 'all' !== $posted['status'] ) {
					$changed      = true;
					$meta_query[] = array(
						'key'   => 'status',
						'value' => $posted['status'],
					);
				}

				if ( ! empty( $posted['payment'] ) ) {
					$changed        = true;
					$payment_method = wc_clean( $posted['payment'] );

					$meta_query[] = array(
						'key'   => 'payment_method',
						'value' => $payment_method,
					);
				}

				if ( ! empty( $posted['customer_user'] ) ) {
					$changed       = true;
					$customer_user = abs( $posted['customer_user'] );

					$meta_query[] = array(
						'key'   => 'user_id',
						'value' => $customer_user,
					);
				}

				if ( ! empty( $posted['s'] ) && is_numeric( $posted['s'] ) ) {
					$changed = true;
					$search  = abs( $posted['s'] );

					$search_query = $wpdb->prepare(
						"SELECT p.ID FROM $wpdb->posts p
                            LEFT JOIN $wpdb->postmeta pm2 ON ( pm2.post_id = p.ID)
                            WHERE p.post_type = %s
                            AND pm2.meta_key='id' AND pm2.meta_value LIKE %s
                            GROUP BY p.ID",
						YITH_YWSBS_POST_TYPE,
						$search
					);

					$results = $wpdb->get_col( $search_query ); //phpcs:ignore
					$query->set( 'post__in', $results );
					$query->set( 's', '' );
				}

				if ( $changed ) {
					$query->set( 'meta_query', $meta_query );
				}
			}
		}

		/**
		 * Return the subscription status
		 *
		 * @return array|object|null
		 */
		protected function get_subscription_status_counter() {
			global $wpdb;

			$query = $wpdb->prepare(
				"SELECT count(*) as counter, ywsbs_pm.meta_value as status FROM {$wpdb->posts} as ywsbs_p LEFT JOIN {$wpdb->postmeta} as ywsbs_pm ON ( ywsbs_p.ID = ywsbs_pm.post_id )
WHERE ywsbs_p.post_type = %s AND ywsbs_pm.meta_key = 'status' GROUP BY ywsbs_pm.meta_value ORDER BY counter DESC",
				YITH_YWSBS_POST_TYPE
			);
			$query = apply_filters( 'ywsbs_subscription_status_counter_query', $query );

			return $wpdb->get_results( $query ); //phpcs:ignore
		}

		/**
		 * Subscription status filters.
		 *
		 * @param array $views Array if view.
		 */
		public function subscription_status_filter( $views ) {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			$current = ! empty( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : 'all';

			echo '<ul class="subsubsub">';

			$statuses = array(
				'all' => array(
					'label'   => _x( 'All', 'Subscription table filter status', 'yith-woocommerce-subscription' ),
					'counter' => 0,
				),
			);

			foreach ( $this->subscription_status as $status ) {
				$statuses['all']['counter'] += $status->counter;

				$statuses[ $status->status ] = array(
					'label'   => ywsbs_get_status_label( $status->status ),
					'counter' => $status->counter,
				);
			}

			foreach ( $statuses as $key => $status ) {
				printf( '<li class="%1$s"><a href="%2$s" class="%3$s">%4$s <span class="count">(%5$d)</span></a></li>', esc_attr( $key ), esc_url( admin_url( 'edit.php?post_type=' . YITH_YWSBS_POST_TYPE . '&status=' . $key ) ), ( $current === $key ? 'current' : '' ), esc_html( ucfirst( $status['label'] ) ), esc_html( $status['counter'] ) );
			}

			echo wp_kses_post( isset( $views['trash'] ) ? '<li class="trash"> ' . $views['trash'] . '</li>' : '' );
			echo wp_kses_post( '</ul>' );
			// empty the default list of post status filter.
			return array();
			// phpcs:enabled WordPress.Security.NonceVerification.Recommended
		}

		/**
		 * Manage the row actions in the Subscription List
		 *
		 * @param array   $actions Actions.
		 * @param WP_Post $post    Current Post.
		 * @return array
		 */
		public function manage_row_actions( $actions, $post ) {

			if ( YITH_YWSBS_POST_TYPE !== get_post_type( $post ) ) {
				return $actions;
			}

			$subscription = ywsbs_get_subscription( $post->ID );

			$is_valid_to_trash = apply_filters( 'ywsbs_valid_status_to_trash', array( 'pending', 'cancelled', 'expired' ) );

			if ( ! in_array( $subscription->get( 'status' ), $is_valid_to_trash, true ) ) {
				unset( $actions['trash'] );
			}

			unset( $actions['inline hide-if-no-js'] );
			unset( $actions['edit'] );

			return $actions;
		}

		/**
		 * Show blank slate.
		 *
		 * @since 2.1.
		 * @param string $which String which tablenav is being shown.
		 */
		public function maybe_render_blank_state( $which ) {
			global $post_type;
			if ( YITH_YWSBS_POST_TYPE === $post_type && 'top' === $which && ! $this->there_are_subscriptions() ) {
				$this->render_blank_state();

				$selectors_to_hide = array(
					'.subsubsub',
					'#posts-filter .wp-list-table',
					'#posts-filter .yith-plugin-ui__wp-list-auto-h-scroll__wrapper',
					'#posts-filter .tablenav.bottom',
					'.tablenav.top > *:not(.ywsbs-admin-no-posts)',
				);

				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo '<style type="text/css">' . implode( ', ', $selectors_to_hide ) . '{ display: none !important; } #posts-filter .tablenav.top { height: auto; display: block } </style>';
			}
		}

		/**
		 * Check if there are subscriptions in table
		 *
		 * @since 3.0.0
		 * @return bool True if there are subscriptions, false otherwise.
		 */
		protected function there_are_subscriptions() {
			static $counts;
			if ( is_null( $counts ) ) {
				$counts = (array) wp_count_posts( YITH_YWSBS_POST_TYPE );
				unset( $counts['auto-draft'] );

				$counts = array_sum( $counts );
			}

			return 0 < $counts;
		}


		/**
		 * Show the blank page when the subscription list is empty.
		 *
		 * @since 2.1.0
		 */
		public function render_blank_state() {

			?>
			<div class="ywsbs-admin-no-posts">
				<div class="ywsbs-admin-no-posts-container">
					<div class="ywsbs-admin-no-posts-logo"><img width="100"
							src="<?php echo esc_url( YITH_YWSBS_ASSETS_URL . '/images/dollar.svg' ); ?>"></div>
					<div class="ywsbs-admin-no-posts-text">
									<span>
										<strong><?php echo esc_html_x( 'You don\'t have any active subscriptions yet.', 'Text showed when the list of email is empty.', 'yith-woocommerce-subscription' ); ?></strong>
									</span>
						<p><?php echo esc_html_x( 'But don\'t worry, your first subscription will appear here soon!', 'Text showed when the list of email is empty.', 'yith-woocommerce-subscription' ); ?></p>
					</div>
					<div class="ywsbs-admin-no-posts-button">
						<a href="<?php echo esc_url( add_query_arg( array( 'post_type' => YITH_YWSBS_POST_TYPE ), admin_url( 'post-new.php' ) ) ); ?>"
							class="page-title-action"><?php echo esc_html_x( 'Add a new subscription', 'Button showed when the list of email is empty.', 'yith-woocommerce-subscription' ); ?></a>
					</div>
				</div>
			</div>
			<?php
		}

		/**
		 * Define bulk actions.
		 *
		 * @param array $actions Existing actions.
		 *
		 * @return array
		 */
		public function define_bulk_action( $actions ) {

			$actions = array(
				'trash' => esc_html__( 'Trash', 'yith-woocommerce-subscription' ),
			);

			return $actions;
		}

		/**
		 * Gets the current action selected from the bulk actions dropdown.
		 *
		 * @since 3.1.0
		 *
		 * @return string|false The action name. False if no action was selected.
		 */
		public function current_action() {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			if ( isset( $_REQUEST['filter_action'] ) && ! empty( $_REQUEST['filter_action'] ) ) {
				return false;
			}

			$action = sanitize_text_field( wp_unslash( $_REQUEST['action'] ?? '' ) );

			return '-1' !== $action ? $action : false;
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
		}

		/**
		 * Set default hidden columns
		 *
		 * @param array  $hidden Hidden list.
		 * @param string $screen Screen.
		 *
		 * @return array
		 */
		public function default_hidden_columns( $hidden, $screen ) {
			$hidden = array_merge(
				$hidden,
				array(
					'end_date',
				)
			);

			return $hidden;
		}
	}
}
