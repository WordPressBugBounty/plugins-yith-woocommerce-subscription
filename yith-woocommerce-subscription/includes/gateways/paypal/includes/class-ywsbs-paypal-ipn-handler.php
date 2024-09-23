<?php
/**
 * Implements YWSBS_PayPal_IPN_Handler Class
 *
 * @class   YWSBS_PayPal_IPN_Handler
 * @since   1.0.1
 * @author  YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! class_exists( 'YWSBS_PayPal_IPN_Handler' ) ) {
	/**
	 * Class YWSBS_PayPal_IPN_Handler
	 */
	class YWSBS_PayPal_IPN_Handler extends WC_Gateway_Paypal_IPN_Handler {

		/**
		 * PayPal Transaction type
		 *
		 * @var array
		 */
		protected $transaction_types = array(
			'subscr_signup',
			'subscr_payment',
			'subscr_modify',
			'subscr_failed',
			'subscr_eot',
			'subscr_cancel',
			'recurring_payment_suspended_due_to_max_failed_payment',
			'recurring_payment_skipped',
			'recurring_payment_outstanding_payment',
		);

		/**
		 * Constructor
		 *
		 * Initialize plugin and registers actions and filters to be used
		 *
		 * @since  1.0.0
		 * @param bool   $sandbox        Sandbox.
		 * @param string $receiver_email Receiver email.
		 */
		public function __construct( $sandbox = false, $receiver_email = '' ) {
			add_action( 'valid-paypal-standard-ipn-request', array( $this, 'valid_response' ), 0 );
			$this->receiver_email = $receiver_email;
		}


		/**
		 * There was a valid response
		 *
		 * @param array $posted Post data after wp_unslash.
		 *
		 * @throws Exception Trigger an error.
		 */
		public function valid_response( $posted ) {
			$order = false;

			if ( ! empty( $posted['custom'] ) ) {
				$order = $this->get_paypal_order( $posted['custom'] );
			} elseif ( ! empty( $posted['invoice'] ) ) {
				$order = $this->get_paypal_order_from_invoice( $posted['invoice'] );
			}

			if ( $order ) {
				$order_id = $order->get_id();
				WC_Gateway_Paypal::log( 'PayPal IPN: ' . print_r( $posted, true ) ); // phpcs:ignore
				WC_Gateway_Paypal::log( 'YWSBS - Found order #' . $order_id );

				if ( isset( $posted['payment_status'] ) ) {
					$posted['payment_status'] = strtolower( $posted['payment_status'] );

					if ( 'refunded' === $posted['payment_status'] ) {
						$this->check_subscription_child_refunds( $order, $posted );
					}
				}

				WC_Gateway_Paypal::log( 'YWSBS - Txn Type: ' . $posted['txn_type'] );
				$this->process_paypal_request( $order, $posted );

			} else {
				WC_Gateway_Paypal::log( 'YWSBS - 404 Order Not Found.' );
			}
		}

		/**
		 * Return the order from the invoice.
		 *
		 * @param string $invoice Invoice.
		 * @return bool|WC_Order|WC_Order_Refund
		 */
		public function get_paypal_order_from_invoice( $invoice ) {
			$extract      = explode( '-', $invoice );
			$order_number = false;
			$order        = false;

			if ( is_array( $extract ) ) {
				$order_number = end( $extract );
			}

			if ( empty( $order_number ) ) {
				return false;
			}

			$query_args = array(
				'numberposts' => 1,
				'meta_key'    => '_order_number', //phpcs:ignore
				'meta_value'  => $order_number, //phpcs:ignore
				'post_type'   => 'shop_order',
				'post_status' => 'any',
				'fields'      => 'ids',
			);

			$posts            = get_posts( $query_args );
			list( $order_id ) = ! empty( $posts ) ? $posts : null;

			// order was found.
			if ( ! is_null( $order_id ) ) {
				$order = wc_get_order( $order_id );
			}

			return $order;
		}

		/**
		 * Handle a completed payment
		 *
		 * @param WC_Order $order  Order.
		 * @param array    $posted Posted arguments.
		 *
		 * @throws Exception Trigger an error.
		 */
		protected function process_paypal_request( $order, $posted ) {

			if ( isset( $posted['txn_type'] ) && ! $this->validate_transaction_type( $posted['txn_type'] ) ) {
				return;
			}

			if ( isset( $posted['mc_currency'] ) ) {
				$this->validate_currency( $order, $posted['mc_currency'] );
			}

			WC_Gateway_Paypal::log( 'YWSBS - Validate currency OK' );

			if ( isset( $posted['receiver_email'] ) ) {
				$this->validate_receiver_email( $order, $posted['receiver_email'] );
			}

			WC_Gateway_Paypal::log( 'YWSBS - Receiver Email OK' );

			$this->save_paypal_meta_data( $order, $posted );

			$this->paypal_ipn_request( $posted );
		}

		/**
		 * Save important data from the IPN to the order.
		 *
		 * @param WC_Order $order  Order object.
		 * @param array    $posted Posted data.
		 */
		protected function save_paypal_meta_data( $order, $posted ) {
			if ( ! empty( $posted['payment_type'] ) ) {
				$order->update_meta_data( 'Payment type', wc_clean( $posted['payment_type'] ) );
			}

			if ( ! empty( $posted['txn_id'] ) ) {
				// Avoid overwrite parent order transaction_id.
				if ( ! $order->get_transaction_id() || 'yes' === $order->get_meta( 'is_a_renew' ) || ! $order->has_status( array( 'completed', 'processing' ) ) ) {
					$order->set_transaction_id( wc_clean( $posted['txn_id'] ) );
				}
			}

			if ( ! empty( $posted['payment_status'] ) ) {
				$order->update_meta_data( '_paypal_status', wc_clean( $posted['payment_status'] ) );
			}

			$order->save();
		}

		/**
		 * Get order info
		 *
		 * @param array $args Arguments.
		 *
		 * @return mixed
		 */
		protected function get_order_info( $args ) {
			$order_info = array();
			if ( isset( $args['custom'] ) ) {
				$order_info = json_decode( $args['custom'], true );
			}

			return $order_info;
		}

		/**
		 * Catch the paypal ipn request for subscription
		 *
		 * @param array $ipn_args IPN arguments.
		 *
		 * @throws Exception Trigger an error.
		 */
		protected function paypal_ipn_request( $ipn_args ) {

			WC_Gateway_Paypal::log( 'YSBS - Paypal IPN Request Start' );

			// check if the order has the same order_key.
			$order_info = $this->get_order_info( $ipn_args );
			$order      = wc_get_order( $order_info['order_id'] );
			$order_id   = $order_info['order_id'];

			if ( $order->get_order_key() !== $order_info['order_key'] ) {
				WC_Gateway_Paypal::log( 'YSBS - Order keys do not match' );

				return;
			}

			// check if the transaction has been processed.
			$order_transaction_ids = $order->get_meta( '_paypal_transaction_ids' );
			$order_transactions    = $this->is_a_valid_transaction( $order_transaction_ids, $ipn_args );
			if ( $order_transactions ) {
				$order->update_meta_data( '_paypal_transaction_ids', $order_transactions );
				$order->save();
			} else {
				WC_Gateway_Paypal::log( 'YSBS - Transaction ID already processed' );

				return;
			}

			// get the subscriptions of the order.
			$subscriptions = $order->get_meta( 'subscriptions' );

			if ( empty( $subscriptions ) ) {
				WC_Gateway_Paypal::log( 'YSBS - IPN subscription payment error - ' . $order_id . ' haven\'t subscriptions' );
				return;
			}

			// Log found subscriptions.
			WC_Gateway_Paypal::log( 'YSBS - Subscription ' . print_r( $subscriptions, true ) ); // phpcs:ignore

			$valid_order_statuses = array( 'on-hold', 'pending', 'failed', 'cancelled' );

			switch ( $ipn_args['txn_type'] ) {
				case 'subscr_signup':
					$args = array(
						'Subscriber ID'         => $ipn_args['subscr_id'],
						'Subscriber first name' => $ipn_args['first_name'],
						'Subscriber last name'  => $ipn_args['last_name'],
						'Subscriber address'    => $ipn_args['payer_email'],
						'Payment type'          => isset( $ipn_args['payment_type'] ) ? $ipn_args['payment_type'] : '',
					);

					$order->add_order_note( __( 'IPN subscription started', 'yith-woocommerce-subscription' ) );
					if ( ! isset( $ipn_args['txn_id'] ) ) {
						exit;
					} else {
						$txn_id = $ipn_args['txn_id'];
					}

					foreach ( $subscriptions as $subscription_id ) {
						$subscription = ywsbs_get_subscription( $subscription_id );
						if ( empty( $subscription->post ) ) {
							continue;
						}

						$subscription->set( 'paypal_transaction_id', $txn_id );
						$subscription->set( 'paypal_subscriber_id', $ipn_args['subscr_id'] );
					}

					foreach ( $args as $key => $value ) {
						$order->update_meta_data( $key, $value );
					}
					$order->save();

					if ( isset( $ipn_args['mc_amount1'] ) && 0 === (int) $ipn_args['mc_amount1'] ) {
						$order->payment_complete( $txn_id );
						exit;
					}

					break;
				case 'recurring_payment_outstanding_payment':
				case 'subscr_payment':
					if ( 'completed' === strtolower( $ipn_args['payment_status'] ) ) {

						foreach ( $subscriptions as $subscription_id ) {
							$subscription = ywsbs_get_subscription( $subscription_id );
							if ( empty( $subscription->post ) ) {
								continue;
							}

							$transaction_ids = $subscription->get( '_paypal_transaction_ids' );
							$transactions    = $this->is_a_valid_transaction( $transaction_ids, $ipn_args );
							if ( $transactions ) {
								$subscription->set( '_paypal_transaction_ids', $transactions );
							} else {
								break;
							}

							$pending_order = $subscription->get_renew_order_id();
							$last_order    = $pending_order ? wc_get_order( $pending_order ) : false;

							// Validate the amount.
							if ( isset( $ipn_args['mc_gross'] ) ) {
								if ( $last_order ) {
									$this->validate_amount( $last_order, $ipn_args['mc_gross'] );
								} elseif ( $order->has_status( $valid_order_statuses ) ) {
									$this->validate_amount( $order, $ipn_args['mc_gross'] );
								}
							}

							$sub_id = 0;
							if ( isset( $ipn_args['subscr_id'] ) ) {
								$sub_id = $ipn_args['subscr_id'];
							} elseif ( isset( $ipn_args['recurring_payment_id'] ) ) {
								$sub_id = $ipn_args['recurring_payment_id'];
							}

							isset( $ipn_args['txn_id'] ) && $subscription->set( 'paypal_transaction_id', $ipn_args['txn_id'] );
							isset( $ipn_args['subscr_id'] ) && $subscription->set( 'paypal_subscriber_id', $ipn_args['subscr_id'] );
							$subscription->set( 'payment_method', 'paypal' );
							$subscription->set( 'payment_method_title', 'PayPal' );

							if ( 'pending' === $subscription->get_status() || ( ! $last_order && $order->has_status( $valid_order_statuses ) ) ) {

								$args = array(
									'Subscriber ID'        => $sub_id,
									'Subscriber first name' => $ipn_args['first_name'],
									'Subscriber last name' => $ipn_args['last_name'],
									'Subscriber address'   => $ipn_args['payer_email'],
									'Subscriber payment type' => wc_clean( $ipn_args['payment_type'] ),
									'Payment type'         => wc_clean( $ipn_args['payment_type'] ),
								);

								foreach ( $args as $key => $value ) {
									$order->update_meta_data( $key, $value );
								}
								$order->save();
								$order->add_order_note( __( 'IPN subscription payment completed.', 'yith-woocommerce-subscription' ) );
								$order->payment_complete( $ipn_args['txn_id'] );
								exit;

							} elseif ( $last_order ) {

								$args = array(
									'Subscriber ID'        => $sub_id,
									'Subscriber first name' => $ipn_args['first_name'],
									'Subscriber last name' => $ipn_args['last_name'],
									'Subscriber address'   => $ipn_args['payer_email'],
									'Subscriber payment type' => wc_clean( $ipn_args['payment_type'] ),
									'Payment type'         => wc_clean( $ipn_args['payment_type'] ),
								);

								foreach ( $args as $key => $value ) {
									$last_order->update_meta_data( $key, $value );
								}
								$last_order->save();
								$last_order->add_order_note( __( 'IPN subscription payment completed.', 'yith-woocommerce-subscription' ) );
								$last_order->payment_complete( $ipn_args['txn_id'] );
								exit;

							} else {

								// if the renew_order is not created try to create it.
								$new_order_id = YWSBS_Subscription_Order()->renew_order( $subscription->get_id() );
								$new_order    = $new_order_id ? wc_get_order( $new_order_id ) : false;
								if ( ! $new_order ) {
									return;
								}

								if ( isset( $ipn_args['mc_gross'] ) ) {
									$this->validate_amount( $new_order, $ipn_args['mc_gross'] );
								}

								$subscription->set( 'renew_order', $new_order_id );
								$args = array(
									'Subscriber ID'        => $sub_id,
									'Subscriber first name' => $ipn_args['first_name'],
									'Subscriber last name' => $ipn_args['last_name'],
									'Subscriber address'   => $ipn_args['payer_email'],
									'Subscriber payment type' => wc_clean( $ipn_args['payment_type'] ),
									'Payment type'         => wc_clean( $ipn_args['payment_type'] ),
								);

								foreach ( $args as $key => $value ) {
									$new_order->update_meta_data( $key, $value );
								}
								$new_order->save();
								$new_order->add_order_note( __( 'IPN subscription payment completed.', 'yith-woocommerce-subscription' ) );
								$new_order->payment_complete( $ipn_args['txn_id'] );
								exit;

							}
						}
					}

					// pending payment echeck.
					if ( isset( $ipn_args['subscr_id'] ) && 'pending' === strtolower( $ipn_args['payment_status'] ) && 'echeck' === strtolower( $ipn_args['payment_type'] ) ) {

						foreach ( $subscriptions as $subscription_id ) {
							$subscription = ywsbs_get_subscription( $subscription_id );
							if ( empty( $subscription->post ) ) {
								continue;
							}

							$transaction_ids = $subscription->get( '_paypal_transaction_ids' );
							$transactions    = $this->is_a_valid_transaction( $transaction_ids, $ipn_args );
							if ( $transactions ) {
								$subscription->set( '_paypal_transaction_ids', $transactions );
							} else {
								break;
							}

							// check if is a renewal.
							$pending_order = $subscription->get_renew_order_id();
							$last_order    = $pending_order ? wc_get_order( $pending_order ) : false;

							isset( $ipn_args['txn_id'] ) && $subscription->set( 'paypal_transaction_id', $ipn_args['txn_id'] );
							isset( $ipn_args['subscr_id'] ) && $subscription->set( 'paypal_subscriber_id', $ipn_args['subscr_id'] );
							$subscription->set( 'payment_method', 'paypal' );
							$subscription->set( 'payment_method_title', 'PayPal' );

							if ( $subscription->get_status() === 'pending' || ( ! $last_order && $order->has_status( $valid_order_statuses ) ) ) {
								// first payment.
								$subscription->set( 'start_date', current_time( 'timestamp' ) ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
								$subscription->set( 'payment_type', $ipn_args['payment_type'] );

								// in this case change the status of order in on-hold waiting the paypal payment.
								$order->update_status( 'on-hold', __( 'Paypal echeck payment', 'yith-woocommerce-subscription' ) );
								$order->update_meta_data( 'Payment type', $ipn_args['payment_type'] );
								$order->save();

								wc_reduce_stock_levels( $order_id );
								WC()->cart->empty_cart();

							} elseif ( $last_order ) {
								// renew order.
								$last_order->add_order_note( __( 'YSBS - IPN Pending payment for echeck payment type', 'yith-woocommerce-subscription' ) );
								// if the renewal is payed with echeck and it is in pending, the subscription is suspended.
								$subscription->update_status( 'suspended', 'paypal' );
								$last_order->add_order_note( __( 'YSBS - Subscription has been suspended because in pending payment for echeck payment type', 'yith-woocommerce-subscription' ) );

							} else {
								// if the renew_order is not created try to create it.
								$new_order_id = YWSBS_Subscription_Order()->renew_order( $subscription->get_id() );
								$new_order    = $new_order_id ? wc_get_order( $new_order_id ) : false;
								if ( ! $new_order ) {
									return;
								}

								$new_order->add_order_note( __( 'YSBS - IPN Pending payment for echeck payment type', 'yith-woocommerce-subscription' ) );
							}
						}
					}

					// failed payment.
					if ( isset( $ipn_args['subscr_id'] ) && 'failed' === strtolower( $ipn_args['payment_status'] ) ) {
						if ( isset( $ipn_args['subscr_id'] ) ) {
							$paypal_sub_id = $ipn_args['subscr_id'];
							$order_sub_id  = $order->get_meta( 'Subscriber ID' );

							if ( $paypal_sub_id != $order_sub_id ) { // phpcs:ignore
								WC_Gateway_Paypal::log( 'YSBS - IPN subscription failed request ignored - new PayPal Profile ID linked to this subscription, for order ' . $order_id );
							} else {
								$subscriptions = $order->get_meta( 'subscriptions' );

								if ( empty( $subscriptions ) ) {
									WC_Gateway_Paypal::log( 'YSBS - IPN subscription failed payment request ignored - order ' . $order_id . ' doesn\'t not subscriptions' );
								}

								// let's remove woocommerce default IPN handling, that would switch parent order to Failed.
								remove_all_actions( 'valid-paypal-standard-ipn-request', 10 );

								foreach ( $subscriptions as $subscription_id ) {
									$subscription = ywsbs_get_subscription( $subscription_id );
									if ( empty( $subscription->post ) ) {
										continue;
									}

									$transaction_ids = $subscription->get( '_paypal_transaction_ids' );
									$transactions    = $this->is_a_valid_transaction( $transaction_ids, $ipn_args );
									if ( $transactions ) {
										$subscription->set( '_paypal_transaction_ids', $transactions );
									} else {
										break;
									}

									isset( $ipn_args['txn_id'] ) && $subscription->set( 'paypal_transaction_id', $ipn_args['txn_id'] );
									isset( $ipn_args['subscr_id'] ) && $subscription->set( 'paypal_subscriber_id', $ipn_args['subscr_id'] );
									$subscription->set( 'payment_method', 'paypal' );
									$subscription->set( 'payment_method_title', 'PayPal' );

									$pending_order = $subscription->get_renew_order_id();
									$last_order    = $pending_order ? wc_get_order( $pending_order ) : false;

									if ( $subscription->get_status() === 'pending' || ( ! $last_order && $order->has_status( $valid_order_statuses ) ) ) {
										continue;
									} elseif ( $last_order ) {
										$last_order->add_order_note( __( 'YSBS - IPN Failed payment', 'yith-woocommerce-subscription' ) );
									} else {
										// if the renew_order is not created try to create it.
										$new_order_id = YWSBS_Subscription_Order()->renew_order( $subscription->get_id() );
										$new_order    = $new_order_id ? wc_get_order( $new_order_id ) : false;
										if ( ! $new_order ) {
											return;
										}

										$new_order->add_order_note( __( 'YSBS - IPN Failed payment', 'yith-woocommerce-subscription' ) );
									}

									// update the number of failed attempt.
									$subscription->register_failed_attempt();

									if ( isset( $ipn_args['retry_at'] ) ) {
										$order->update_meta_data( 'next_payment_attempt', strtotime( $ipn_args['retry_at'] ) );
									}
									$suspend_subscription = apply_filters( 'ywsbs_suspend_for_failed_recurring_payment', get_option( 'ywsbs_suspend_for_failed_recurring_payment', 'no' ) );
									if ( 'yes' === $suspend_subscription ) {
										$subscription->update_status( 'suspended', 'paypal' );
									}

									$order->add_order_note( __( 'YSBS - IPN Failed payment', 'yith-woocommerce-subscription' ) );
									// Subscription Cancellation Completed.
									WC_Gateway_Paypal::log( 'YSBS -IPN Failed payment' . $order_id );
								}
							}
						}
					}
					break;

				case 'subscr_modify':
					break;
				case 'subscr_failed':
					if ( isset( $ipn_args['subscr_id'] ) ) {
						$paypal_sub_id = $ipn_args['subscr_id'];
						$order_sub_id  = $order->get_meta( 'Subscriber ID' );

						if ( $paypal_sub_id != $order_sub_id ) { // phpcs:ignore
							WC_Gateway_Paypal::log( 'YSBS - IPN subscription failed request ignored - new PayPal Profile ID linked to this subscription, for order ' . $order_id );
						} else {
							$subscriptions = $order->get_meta( 'subscriptions' );

							if ( empty( $subscriptions ) ) {
								WC_Gateway_Paypal::log( 'YSBS - IPN subscription failed payment request ignored - order ' . $order_id . ' doesn\'t not subscriptions' );
							}

							foreach ( $subscriptions as $subscription_id ) {
								$subscription = ywsbs_get_subscription( $subscription_id );
								if ( empty( $subscription->post ) ) {
									continue;
								}

								$transaction_ids = $subscription->get( '_paypal_transaction_ids' );
								$transactions    = $this->is_a_valid_transaction( $transaction_ids, $ipn_args );
								if ( $transactions ) {
									$subscription->set( '_paypal_transaction_ids', $transactions );
								} elseif ( isset( $ipn_args['retry_at'] ) ) {
									$retry_at_meta = $subscription->get( '_retry_at' );
									if ( $retry_at_meta == $ipn_args['retry_at'] ) { // phpcs:ignore
										break;
									} else {
										$subscription->set( '_retry_at', $ipn_args['retry_at'] );
									}
								} else {
									break;
								}

								isset( $ipn_args['txn_id'] ) && $subscription->set( 'paypal_transaction_id', $ipn_args['txn_id'] );
								isset( $ipn_args['subscr_id'] ) && $subscription->set( 'paypal_subscriber_id', $ipn_args['subscr_id'] );
								$subscription->set( 'payment_method', 'paypal' );
								$subscription->set( 'payment_method_title', 'PayPal' );

								$pending_order = $subscription->get_renew_order_id();
								$last_order    = $pending_order ? wc_get_order( $pending_order ) : false;

								if ( $subscription->get_status() === 'pending' || ( ! $last_order && $order->has_status( $valid_order_statuses ) ) ) {
									continue;
								} elseif ( $last_order ) {
									$last_order->add_order_note( __( 'YSBS - IPN Failed payment', 'yith-woocommerce-subscription' ) );
								} else {
									// if the renew_order is not created try to create it.
									$new_order_id = YWSBS_Subscription_Order()->renew_order( $subscription->get_id() );
									$new_order    = $new_order_id ? wc_get_order( $new_order_id ) : false;
									if ( ! $new_order ) {
										return;
									}

									$new_order->add_order_note( __( 'YSBS - IPN Failed payment', 'yith-woocommerce-subscription' ) );
								}

								// update the number of failed attempt.
								$subscription->register_failed_attempt();
								if ( isset( $ipn_args['retry_at'] ) ) {
									$order->update_meta_data( 'next_payment_attempt', strtotime( $ipn_args['retry_at'] ) );
									$order->save();
								}

								$suspend_subscription = apply_filters( 'ywsbs_suspend_for_failed_recurring_payment', get_option( 'ywsbs_suspend_for_failed_recurring_payment', 'no' ) );
								if ( 'yes' === $suspend_subscription ) {
									$subscription->update_status( 'suspended', 'paypal' );
								}

								$order->add_order_note( __( 'YSBS - IPN Failed payment', 'yith-woocommerce-subscription' ) );

								// Subscription Cancellation Completed.
								WC_Gateway_Paypal::log( 'YSBS -IPN Failed payment' . $order_id );
							}
						}
					}
					break;
				case 'recurring_payment_skipped':
					if ( isset( $ipn_args['recurring_payment_id'] ) ) {

						$paypal_sub_id = $ipn_args['recurring_payment_id'];
						$order_sub_id  = $order->get_meta( 'Subscriber ID' );

						if ( $paypal_sub_id != $order_sub_id ) {  // phpcs:ignore
							WC_Gateway_Paypal::log( 'YSBS - IPN subscription failed payment - new PayPal Profile ID linked to this subscription, for order ' . $order_id );
						} else {
							$subscriptions = $order->get_meta( 'subscriptions' );

							if ( empty( $subscriptions ) ) {
								WC_Gateway_Paypal::log( 'YSBS - IPN subscription failed payment request ignored - order ' . $order_id . ' doesn\'t not subscriptions' );
							}

							foreach ( $subscriptions as $subscription_id ) {
								$subscription = ywsbs_get_subscription( $subscription_id );
								if ( empty( $subscription->post ) ) {
									continue;
								}

								$transaction_ids = $subscription->get( '_paypal_transaction_ids' );
								$transactions    = $this->is_a_valid_transaction( $transaction_ids, $ipn_args );
								if ( $transactions ) {
									$subscription->set( '_paypal_transaction_ids', $transactions );
								} else {
									break;
								}

								$pending_order = $subscription->get_renew_order_id();
								$last_order    = $pending_order ? wc_get_order( $pending_order ) : false;

								if ( $subscription->get_status() === 'pending' || ( ! $last_order && $order->has_status( $valid_order_statuses ) ) ) {
									continue;
								} elseif ( $last_order ) {
									$last_order->add_order_note( __( 'YSBS - IPN Failed payment', 'yith-woocommerce-subscription' ) );
								} else {
									// if the renew_order is not created try to create it.
									$new_order_id = YWSBS_Subscription_Order()->renew_order( $subscription->get_id() );
									$new_order    = $new_order_id ? wc_get_order( $new_order_id ) : false;
									if ( ! $new_order ) {
										return;
									}

									$new_order->add_order_note( __( 'YSBS - IPN Failed payment', 'yith-woocommerce-subscription' ) );
								}

								// update the number of failed attempt.
								$subscription->register_failed_attempt();
								if ( isset( $ipn_args['retry_at'] ) ) {
									$order->update_meta_data( 'next_payment_attempt', strtotime( $ipn_args['retry_at'] ) );
									$order->save();
								}

								$suspend_subscription = apply_filters( 'ywsbs_suspend_for_failed_recurring_payment', get_option( 'ywsbs_suspend_for_failed_recurring_payment', 'no' ) );
								if ( 'yes' === $suspend_subscription ) {
									$subscription->update_status( 'suspended', 'paypal' );
								}

								$order->add_order_note( __( 'YSBS - IPN Failed payment', 'yith-woocommerce-subscription' ) );

								// Subscription Cancellation Completed.
								WC_Gateway_Paypal::log( 'YSBS -IPN Failed payment' . $order_id );

							}
						}
					}
					break;

				case 'subscr_eot':
					/*subscription expired*/
					break;

				case 'recurring_payment_suspended_due_to_max_failed_payment':
					if ( isset( $ipn_args['recurring_payment_id'] ) ) {
						$paypal_sub_id = $ipn_args['recurring_payment_id'];
						$order_sub_id  = $order->get_meta( 'Subscriber ID' );

						if ( $paypal_sub_id != $order_sub_id ) { // phpcs:ignore
							WC_Gateway_Paypal::log( 'YSBS - IPN subscription failed request ignored - new PayPal Profile ID linked to this subscription, for order ' . $order_id );
						} else {
							$subscriptions = $order->get_meta( 'subscriptions' );
							if ( empty( $subscriptions ) ) {
								WC_Gateway_Paypal::log( 'YSBS - IPN subscription cancellation for failed request ignored - order ' . $order_id . ' doesn\'t not subscriptions' );
							}

							foreach ( $subscriptions as $subscription_id ) {
								$subscription = ywsbs_get_subscription( $subscription_id );
								if ( empty( $subscription->post ) ) {
									continue;
								}

								$transaction_ids = $subscription->get( '_paypal_transaction_ids' );
								$transactions    = $this->is_a_valid_transaction( $transaction_ids, $ipn_args );
								if ( $transactions ) {
									$subscription->set( '_paypal_transaction_ids', $transactions );
								} else {
									break;
								}

								// check if the subscription has max num of attempts.
								$failed_attemp       = $order->get_meta( 'failed_attemps' );
								$max_failed_attempts = ywsbs_get_max_failed_attempts_by_gateway( 'paypal' );

								if ( $failed_attemp >= ( $max_failed_attempts - 1 ) ) {
									$subscription->cancel( false );
									$order->add_order_note( __( 'YSBS - Subscription cancelled max failed attemps: recurring_payment_suspended_due_to_max_failed_payment', 'yith-woocommerce-subscription' ) );
									// Subscription Cancellation Completed.
									WC_Gateway_Paypal::log( 'YSBS -Subscription cancelled max failed attempts: recurring_payment_suspended_due_to_max_failed_payment' . $order_id );
								} else {
									$subscription->update_status( 'suspended', 'paypal' );

									$pending_order = $subscription->get_renew_order_id();
									$last_order    = $pending_order ? wc_get_order( $pending_order ) : false;

									if ( $last_order ) {
										$last_order->add_order_note( __( 'YSBS - IPN message: recurring_payment_suspended_due_to_max_failed_payment', 'yith-woocommerce-subscription' ) );
									} else {
										$order->add_order_note( __( 'YSBS - IPN message: recurring_payment_suspended_due_to_max_failed_payment', 'yith-woocommerce-subscription' ) );
									}
								}
							}
						}
					}
					break;

				case 'subscr_cancel':
					/*subscription cancelled*/
					$paypal_sub_id = $ipn_args['subscr_id'];
					$order_sub_id  = $order->get_meta( 'Subscriber ID' );

					if ( $paypal_sub_id != $order_sub_id ) { // phpcs:ignore
						WC_Gateway_Paypal::log( 'YSBS - IPN subscription cancellation request ignored - new PayPal Profile ID linked to this subscription, for order ' . $order_id );
					} else {
						$subscriptions = $order->get_meta( 'subscriptions' );
						if ( empty( $subscriptions ) ) {
							WC_Gateway_Paypal::log( 'YSBS - IPN subscription cancellation request ignored - order ' . $order_id . ' doesn\'t not subscriptions' );
						}

						foreach ( $subscriptions as $subscription_id ) {
							$subscription = ywsbs_get_subscription( $subscription_id );
							if ( empty( $subscription->post ) ) {
								continue;
							}

							$transaction_ids = $subscription->get( '_paypal_transaction_ids' );
							$transactions    = $this->is_a_valid_transaction( $transaction_ids, $ipn_args );
							if ( $transactions ) {
								$subscription->set( '_paypal_transaction_ids', $transactions );
							} else {
								break;
							}

							$subscription->cancel( false );
							$order->add_order_note( __( 'YSBS - IPN subscription cancelled for the order.', 'yith-woocommerce-subscription' ) );

							// Subscription Cancellation Completed.
							WC_Gateway_Paypal::log( 'YSBS -IPN subscription cancelled for order ' . $order_id );
						}
					}

					break;
				default:
			}
		}

		/**
		 * Check is the transaction is valid.
		 *
		 * @param array $transaction_ids Transaction ids.
		 * @param array $ipn_args        IPN arguments.
		 *
		 * @return array|bool
		 */
		protected function is_a_valid_transaction( $transaction_ids, $ipn_args ) {
			$transaction_ids = empty( $transaction_ids ) ? array() : $transaction_ids;

			// check if the ipn request as been processed.
			if ( isset( $ipn_args['txn_id'] ) ) {
				$transaction_id = $ipn_args['txn_id'] . '-' . $ipn_args['txn_type'];

				if ( isset( $ipn_args['payment_status'] ) ) {
					$transaction_id .= '-' . $ipn_args['payment_status'];
				}
				if ( empty( $transaction_ids ) || ! in_array( $transaction_id, $transaction_ids ) ) { //phpcs:ignore
					$transaction_ids[] = $transaction_id;
				} else {
					WC_Gateway_Paypal::log( 'YSBS - Subscription IPN Error: IPN ' . $transaction_id . ' message has already been correctly handled.' );
					return false;
				}
			} elseif ( isset( $ipn_args['ipn_track_id'] ) ) {
				$track_id = $ipn_args['txn_type'] . '-' . $ipn_args['ipn_track_id'];
				if ( empty( $transaction_ids ) || ! in_array( $track_id, $transaction_ids ) ) { //phpcs:ignore
					$transaction_ids[] = $track_id;
				} else {
					WC_Gateway_Paypal::log( 'YSBS - Subscription IPN Error: IPN ' . $track_id . ' message has already been correctly handled.' );
					return false;
				}
			}

			return $transaction_ids;
		}

		/**
		 * Check for a valid transaction type
		 *
		 * @param string $txn_type Type of transaction.
		 *
		 * @return bool|void
		 */
		protected function validate_transaction_type( $txn_type ) {
			return in_array( strtolower( $txn_type ), $this->transaction_types ); //phpcs:ignore
		}

		/**
		 * Check payment amount from IPN matches the order.
		 *
		 * @param WC_Order $order  Order.
		 * @param int      $amount Amount.
		 */
		protected function validate_amount( $order, $amount ) {
			if ( number_format( $order->get_total(), 2, '.', '' ) !== number_format( $amount, 2, '.', '' ) ) {
				WC_Gateway_Paypal::log( 'Payment error: Amounts do not match (gross ' . $amount . ')' );

				// Put this order on-hold for manual checking.
				// translators:Placeholder amount value.
				$order->update_status( 'on-hold', sprintf( __( 'Validation error: PayPal amounts do not match (gross %s).', 'yith-woocommerce-subscription' ), $amount ) );
				exit;
			}
		}

		/**
		 * Check if the refund is of a renew order.
		 *
		 * @param WC_Order $order  Order.
		 * @param array    $posted Posted.
		 */
		private function check_subscription_child_refunds( $order, $posted ) {
			$subscriptions = $order->get_meta( 'subscriptions' );
			if ( ! $subscriptions ) {
				return;
			}

			$parent_txn_id   = isset( $posted['parent_txn_id'] ) ? $posted['parent_txn_id'] : false;
			$child_order_ids = wc_get_orders(
				array(
					'return'         => 'ids',
					'transaction_id' => $parent_txn_id,
				)
			);

			if ( empty( $child_order_id ) ) {
				return;
			}

			$child_order_id = array_shift( $child_order_ids ); // it must be only one order for transaction id.
			$child_order    = wc_get_order( $child_order_id );
			if ( ! $child_order ) {
				return;
			}

			remove_all_actions( 'valid-paypal-standard-ipn-request', 10 );

			$this->payment_status_refunded( $child_order, $posted );
		}


		/**
		 * Handle a refunded order.
		 *
		 * @param WC_Order $order  Order object.
		 * @param array    $posted Posted data.
		 */
		protected function payment_status_refunded( $order, $posted ) {

			// Only handle full refunds, not partial.
			if ( floatval( $order->get_total() ) === ( (float) $posted['mc_gross'] * -1 ) ) {
				/* translators: %s: payment status. */
				$order->update_status( 'refunded', sprintf( __( 'Payment %s via IPN.', 'yith-woocommerce-subscription' ), strtolower( $posted['payment_status'] ) ) );

				$this->send_ipn_email_notification(
				/* translators: %s: order link. */
					sprintf( __( 'Payment for order %s refunded', 'yith-woocommerce-subscription' ), '<a class="link" href="' . esc_url( $order->get_edit_order_url() ) . '">' . $order->get_order_number() . '</a>' ),
					/* translators: %1$s: order ID, %2$s: reason code. */
					sprintf( __( 'Order #%1$s has been marked as refunded - PayPal reason code: %2$s', 'yith-woocommerce-subscription' ), $order->get_order_number(), $posted['reason_code'] )
				);
			}
		}
	}

}
