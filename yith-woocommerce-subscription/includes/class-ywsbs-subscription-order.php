<?php
/**
 * Implements YWSBS_Subscription_Order Class
 *
 * @class   YWSBS_Subscription_Order
 * @since   1.0.0
 * @author  YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

require_once YITH_YWSBS_INC . 'legacy/abstract-ywsbs-subscription-order-legacy.php';

if ( ! class_exists( 'YWSBS_Subscription_Order' ) ) {
	/**
	 * Class YWSBS_Subscription_Order
	 */
	class YWSBS_Subscription_Order extends YWSBS_Subscription_Order_Legacy {
		use YITH_WC_Subscription_Singleton_Trait;

		/**
		 * Constructor
		 *
		 * Initialize plugin and registers actions and filters to be used
		 *
		 * @since  1.0.0
		 */
		protected function __construct() {

			add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'check_order' ), 100 );
			add_action( 'woocommerce_checkout_order_processed', array( $this, 'check_order' ), 100, 2 );

			add_action( 'woocommerce_after_order_item_object_save', array( $this, 'save_cart_item_key_on_order_item' ), 10 );

			// Start subscription after payment received.
			add_action( 'woocommerce_payment_complete', array( $this, 'payment_complete' ) );
			add_action( 'woocommerce_order_status_completed', array( $this, 'payment_complete' ) );
			add_action( 'woocommerce_order_status_processing', array( $this, 'payment_complete' ) );

			add_filter( 'woocommerce_can_reduce_order_stock', array( $this, 'can_reduce_order_stock' ), 10, 2 );

			if ( get_option( 'ywsbs_delete_subscription_order_cancelled', 'yes' ) === 'yes' ) {
				add_action( 'woocommerce_order_status_cancelled', array( __CLASS__, 'trash_subscriptions' ), 10 );
			} else {
				add_action( 'woocommerce_order_status_cancelled', array( __CLASS__, 'cancel_subscriptions' ), 10 );
			}
		}

		/**
		 * Check if inside the current order there is a subscription
		 *
		 * @param   WC_Order|int $order  Order or order id.
		 *
		 * @return void
		 * @throws Exception
		 * @since 3.0.0
		 */
		public function check_order( $order ) {
			if ( ! YWSBS_Subscription_Cart::cart_has_subscriptions() || isset( $_REQUEST['cancel_order'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return;
			}

			$this->actual_cart = WC()->session->get( 'cart' );
			$order             = wc_get_order( $order );

			/**
			 * APPLY_FILTERS: ywsbs_pay_order_check
			 *
			 * Check the order.
			 *
			 * @param WC_Order $order .
			 */
			$order = apply_filters( 'ywsbs_pay_order_check', $order );

			add_filter( 'ywsbs_price_check', '__return_false' );
			remove_action( 'woocommerce_before_calculate_totals', array( YWSBS_Subscription_Cart(), 'add_change_prices_filter' ), 10 );
			remove_action( 'woocommerce_before_calculate_totals', array( YWSBS_Subscription_Cart(), 'before_calculate_totals' ), 200 );
			remove_action( 'woocommerce_before_checkout_process', array( YWSBS_Subscription_Cart(), 'sync_on_process_checkout' ), 200 );
			remove_filter( 'woocommerce_product_needs_shipping', array( YWSBS_Subscription_Cart(), 'maybe_not_shippable' ), 100 );
			remove_action( 'woocommerce_cart_needs_shipping', '__return_false', 200 );
			remove_filter( 'woocommerce_calculated_total', array( YWSBS_Subscription_Cart(), 'remove_shipping_cost_from_calculate_totals' ), 200 );
			remove_filter( 'woocommerce_cart_tax_totals', array( $this, 'remove_tax_shipping_cost_from_calculate_totals' ), 200 );

			WC()->cart->calculate_totals();

			$subscription_info = array();
			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				if ( ywsbs_is_subscription_product( $cart_item['data'] ) && isset( $cart_item['ywsbs-subscription-info'] ) ) {
					$subscription_info[] = $this->collect_subscription_meta( $cart_item_key, $cart_item, $order );
				}
			}

			if ( ! empty( $subscription_info ) ) {
				$this->save_subscriptions( $order, $subscription_info );
			}
		}

		/**
		 * Save cart item key if the product is a subscription
		 *
		 * @param   WC_Order_Item $order_item  Current Order Item.
		 *
		 * @return void
		 * @throws Exception
		 * @since 3.0.0
		 */
		public function save_cart_item_key_on_order_item( $order_item ) {
			if ( isset( $order_item->legacy_values['ywsbs-subscription-info'] ) ) {
				wc_add_order_item_meta( $order_item->get_id(), '_cart_item_key', $order_item->legacy_values['key'], true );
			}
		}

		/**
		 * Collect meta necessary to save the subscription
		 *
		 * @param   string        $cart_item_key    Cart item key.
		 * @param   array         $cart_item        Cart item.
		 * @param   null|WC_Order $order            Current order.
		 *
		 * @return mixed|null
		 * @throws Exception
		 */
		public function collect_subscription_meta( $cart_item_key, $cart_item, $order ) {
			$product           = $cart_item['data'];
			$subscription_info = array(
				'shipping'             => array(),
				'taxes'                => array(),
				'payment_method'       => '',
				'payment_method_title' => '',
			);

			$subscription_info = array_merge( $cart_item['ywsbs-subscription-info'], $subscription_info );
			// set the resubscribe subscription.
			$subscription_info['parent_subscription'] = isset( $cart_item['ywsbs-subscription-resubscribe'] ) ? $cart_item['ywsbs-subscription-resubscribe']['subscription_id'] : '';

			if ( isset( $cart_item['ywsbs-subscription-switch'] ) ) {
				$subscription_info['switched_from'] = $cart_item['ywsbs-subscription-switch']['subscription_id'];
			}

			// create new cart for this subscription.
			$new_cart = new WC_Cart();
			if ( defined( 'YITH_PAYPAL_PAYMENTS_VERSION' ) ) {
				$paypal_shipping = WC()->session->get( 'paypal_shipping_address', false );
				$paypal_billing  = WC()->session->get( 'paypal_billing_address', false );
				$ppwc            = WC()->session->get( 'paypal_order_id', false );
			}

			// set the variation id.
			if ( isset( $cart_item['variation'] ) ) {
				$subscription_info['variation'] = $cart_item['variation'];
			}

			$payment_method   = $order->get_payment_method();
			$enabled_gateways = WC()->payment_gateways()->get_available_payment_gateways();
			if ( isset( $enabled_gateways[ $payment_method ] ) ) {
				$payment_method = $enabled_gateways[ $payment_method ];
				$payment_method->validate_fields();
				$subscription_info['payment_method']       = $payment_method->id;
				$subscription_info['payment_method_title'] = $payment_method->get_title();
			}

			do_action( 'ywsbs_before_add_to_cart_subscription', $cart_item );
			add_filter( 'woocommerce_is_sold_individually', '__return_false', 200 );
			remove_filter( 'woocommerce_add_cart_item_data', array( YWSBS_Subscription_Cart(), 'set_subscription_meta_on_cart' ), 20 );
			remove_filter( 'woocommerce_is_purchasable', array( 'YITH_WC_Subscription_Limit', 'is_purchasable' ), 10 );
			$new_cart_item_key = $new_cart->add_to_cart(
				$cart_item['product_id'],
				$cart_item['quantity'],
				( isset( $cart_item['variation_id'] ) ? $cart_item['variation_id'] : '' ),
				( isset( $cart_item['variation'] ) ? $cart_item['variation'] : '' ),
				$cart_item
			);

			do_action( 'ywsbs_after_add_to_cart_subscription', $cart_item );
			remove_filter( 'woocommerce_is_sold_individually', '__return_false', 200 );

			$new_cart = apply_filters( 'ywsbs_add_cart_item_data', $new_cart, $new_cart_item_key, $cart_item );
			// set the same subscription product price.
			$current_price = isset( $subscription_info['recurring_price'] ) ? $subscription_info['recurring_price'] : $product->get_price();

			$new_cart->cart_contents[ $new_cart_item_key ]['data']->set_price( $current_price );
			$new_cart_item_keys     = array_keys( $new_cart->cart_contents );
			$ywsbs_shipping_methods = WC()->session->get( 'ywsbs_shipping_methods' );
			foreach ( $new_cart_item_keys as $new_cart_item_key ) {

				// Get the shipping method for this subscription product.
				if ( $new_cart->needs_shipping() && $product->needs_shipping() ) {

					if ( method_exists( WC()->shipping, 'get_packages' ) ) {
						$packages = WC()->shipping->get_packages();

						foreach ( $packages as $key => $package ) {
							if ( isset( $package['rates'][ $ywsbs_shipping_methods[ $key ] ] ) ) {
								if ( isset( $package['contents'][ $cart_item_key ] ) || isset( $package['contents'][ $new_cart_item_key ] ) ) {
									// This shipping method has the current subscription.
									$shipping['method']      = $ywsbs_shipping_methods[ $key ];
									$shipping['destination'] = $package['destination'];

									break;
								}
							}
						}

						if ( isset( $shipping ) ) {
							// Get packages based on renewal order details.
							$new_packages = apply_filters(
								'woocommerce_cart_shipping_packages',
								array(
									0 => array(
										'contents'      => $new_cart->get_cart(),
										'contents_cost' => isset( $new_cart->cart_contents[ $new_cart_item_key ]['line_total'] ) ? $new_cart->cart_contents[ $new_cart_item_key ]['line_total'] : 0,
										'destination'   => $shipping['destination'],
									),
								)
							);

							$save_temp_session_values = array(
								'shipping_method_counts'  => WC()->session->get( 'shipping_method_counts' ),
								'chosen_shipping_methods' => WC()->session->get( 'chosen_shipping_methods' ),
							);

							WC()->session->set( 'chosen_shipping_methods', array( $shipping['method'] ) );

							add_filter( 'woocommerce_shipping_chosen_method', array( $this, 'change_shipping_chosen_method_temp' ) );
							$this->subscription_shipping_method_temp = $shipping['method'];

							WC()->shipping->calculate_shipping( $new_packages );

							remove_filter( 'woocommerce_shipping_chosen_method', array( $this, 'change_shipping_chosen_method_temp' ) );

							unset( $this->subscription_shipping_method_temp );
						}
					}
				}

				add_filter( 'woocommerce_coupon_validate_user_usage_limit', '__return_false' );
				$new_cart->calculate_totals();
				remove_filter( 'woocommerce_coupon_validate_user_usage_limit', '__return_false' );
				// Recalculate totals.
				// save some order settings.
				$subscription_info['order_shipping']     = wc_format_decimal( $new_cart->shipping_total );
				$subscription_info['order_shipping_tax'] = wc_format_decimal( $new_cart->shipping_tax_total );
				$subscription_info['order_tax']          = wc_format_decimal( $new_cart->tax_total );
				$subscription_info['order_subtotal']     = wc_format_decimal( $new_cart->subtotal, get_option( 'woocommerce_price_num_decimals' ) );
				$subscription_info['order_total']        = wc_format_decimal( $new_cart->total, get_option( 'woocommerce_price_num_decimals' ) );
				$subscription_info['line_subtotal']      = wc_format_decimal( $new_cart->cart_contents[ $new_cart_item_key ]['line_subtotal'] );
				$subscription_info['line_subtotal_tax']  = wc_format_decimal( $new_cart->cart_contents[ $new_cart_item_key ]['line_subtotal_tax'] );
				$subscription_info['line_total']         = wc_format_decimal( $new_cart->cart_contents[ $new_cart_item_key ]['line_total'] );
				$subscription_info['line_tax']           = wc_format_decimal( $new_cart->cart_contents[ $new_cart_item_key ]['line_tax'] );
				$subscription_info['line_tax_data']      = $new_cart->cart_contents[ $new_cart_item_key ]['line_tax_data'];
			}

			// Get shipping details.
			if ( $product->needs_shipping() ) {
				if ( isset( $shipping['method'] ) ) {
					$method = null;
					foreach ( WC()->shipping->packages as $i => $package ) {
						if ( isset( $package['rates'][ $shipping['method'] ] ) ) {
							$method = $package['rates'][ $shipping['method'] ];
							break;
						}
					}

					if ( ! is_null( $method ) ) {
						$subscription_info['shipping'] = array(
							'name'      => $method->label,
							'method_id' => $method->id,
							'cost'      => wc_format_decimal( $method->cost ),
							'taxes'     => $method->taxes,
						);

						// Set session variables to original values and recalculate shipping for original order which is being processed now.
						isset( $save_temp_session_values['shipping_method_counts'] ) && WC()->session->set( 'shipping_method_counts', $save_temp_session_values['shipping_method_counts'] );
						isset( $save_temp_session_values['chosen_shipping_methods'] ) && WC()->session->set( 'chosen_shipping_methods', $save_temp_session_values['chosen_shipping_methods'] );
						WC()->shipping->calculate_shipping( WC()->shipping->packages );
					}
				}
			}

			// CALCULATE TAXES.
			$taxes          = $new_cart->get_cart_contents_taxes();
			$shipping_taxes = $new_cart->get_shipping_taxes();

			foreach ( $new_cart->get_tax_totals() as $rate_key => $rate ) {

				$rate_args = array(
					'name'     => $rate_key,
					'rate_id'  => $rate->tax_rate_id,
					'label'    => $rate->label,
					'compound' => absint( $rate->is_compound ? 1 : 0 ),

				);

				$rate_args['tax_amount']          = wc_format_decimal( isset( $taxes[ $rate->tax_rate_id ] ) ? $taxes[ $rate->tax_rate_id ] : 0 );
				$rate_args['shipping_tax_amount'] = wc_format_decimal( isset( $shipping_taxes[ $rate->tax_rate_id ] ) ? $shipping_taxes[ $rate->tax_rate_id ] : 0 );

				$subscription_info['taxes'][] = $rate_args;
			}

			// Get item from order.
			foreach ( $order->get_items() as $item ) {
				if ( $item->get_meta( '_cart_item_key' ) !== $cart_item_key ) {
					continue;
				}

				$order_item_id                      = $item->get_id();
				$subscription_info['order_item_id'] = $order_item_id;
			}

			// Third party filter subscription info before set.
			$subscription_info = apply_filters( 'ywsbs_subscription_info_meta', $subscription_info, $new_cart, $new_cart_item_key, $cart_item );
			wc_add_order_item_meta( $order_item_id ?? 0, '_subscription_info', $subscription_info, true );

			$new_cart->empty_cart( true );
			WC()->cart->empty_cart( true );
			WC()->session->set( 'cart', $this->actual_cart );

			if ( defined( 'YITH_PAYPAL_PAYMENTS_VERSION' ) ) {
				WC()->session->set( 'paypal_order_id', $ppwc );
				WC()->session->set( 'paypal_shipping_address', $paypal_shipping );
				WC()->session->set( 'paypal_billing_address', $paypal_billing );
			}
			WC()->cart->get_cart_from_session();
			WC()->cart->set_session();

			return $subscription_info;
		}


		/**
		 * Check in the order if there's a subscription and create it
		 *
		 * @param   WC_Order $order                   Current Order.
		 * @param   array    $subscription_list_info  List of subscription.
		 *
		 * @return void
		 * @throws Exception Trigger an error.
		 * @since 3.0.0
		 */
		public function save_subscriptions( $order, $subscription_list_info ) {

			$order_args     = array();
			$order_id       = $order->get_id();
			$user_id        = $order->get_customer_id();
			$order_currency = $order->get_currency();

			// check id the subscriptions are created.
			$subscriptions = $order->get_meta( 'subscriptions' );

			if ( ! empty( $subscriptions ) ) {
				return;
			}

			$subscriptions = array();
			foreach ( $subscription_list_info as $subscription_info ) {
				$order_item = $order->get_item( $subscription_info['order_item_id'] );
				$product    = $order_item->get_product();

				if ( ! $product || ! ywsbs_is_subscription_product( $product ) ) {
					continue;
				}

				$price_is_per = $subscription_info['price_is_per'];
				$max_length   = $subscription_info['max_length'];

				$args = array(
					'product_id'              => $order_item['product_id'],
					'variation_id'            => $order_item['variation_id'],
					'variation'               => $subscription_info['variation'] ?? '',
					'product_name'            => $order_item['name'],
					'quantity'                => $order_item['qty'],

					// order details.
					'order_id'                => $order_id,
					'order_item_id'           => $subscription_info['order_item_id'],
					'order_ids'               => array( $order_id ),

					'line_subtotal'           => $subscription_info['line_subtotal'],
					'line_total'              => $subscription_info['line_total'],
					'line_subtotal_tax'       => $subscription_info['line_subtotal_tax'],
					'line_tax'                => $subscription_info['line_tax'],
					'line_tax_data'           => $subscription_info['line_tax_data'],

					'order_total'             => $subscription_info['order_total'],
					'subscription_total'      => $subscription_info['order_total'],
					'order_tax'               => $subscription_info['order_tax'],
					'order_subtotal'          => $subscription_info['order_subtotal'],
					'prices_include_tax'      => $order->get_meta( 'prices_include_tax' ),

					'order_shipping'          => $subscription_info['order_shipping'],
					'order_shipping_tax'      => $subscription_info['order_shipping_tax'],
					'subscriptions_shippings' => $subscription_info['shipping'],

					'payment_method'          => $subscription_info['payment_method'],
					'payment_method_title'    => $subscription_info['payment_method_title'],

					'payment_due_date'        => $subscription_info['next_payment_due_date'],
					'order_currency'          => $order_currency,

					// user details.
					'user_id'                 => $user_id,

					// item subscription detail.
					'price_is_per'            => $price_is_per,
					'price_time_option'       => $subscription_info['price_time_option'],
					'max_length'              => $max_length,
					'num_of_rates'            => ( $max_length && $price_is_per ) ? $max_length / $price_is_per : '',
					'parent_subscription'     => $subscription_info['parent_subscription'],
				);

				if ( ! empty( $subscription_info['switched_from'] ) ) {
					$args['switched_from'] = $subscription_info['switched_from'];
				}

				$subscription    = new YWSBS_Subscription( '', array_filter( $args ) );
				$subscription_id = $subscription->get_id();

				// save the version of plugin in the order.
				$order_args['_ywsbs_order_version'] = YITH_YWSBS_VERSION;

				if ( ! empty( $subscription_info['parent_subscription'] ) ) {
					$order_args['_parent_subscription'] = $subscription_info['parent_subscription'];
				}

				if ( $subscription_id ) {
					$subscriptions[]             = $subscription_id;
					$order_args['subscriptions'] = $subscriptions;

					$order_item->update_meta_data( '_subscription_id', $subscription_id );
					$order_item->delete_meta_data( '_cart_item_key' );
					$order_item->save();

					YWSBS_Subscription_User::delete_user_cache( $user_id );

					do_action( 'ywsbs_subscription_created', $subscription_id );
					// translators: Placeholders: url of subscription, subscription number.
					$order->add_order_note(
						sprintf(
							_x( 'A new subscription <a href="%1$s">%2$s</a> has been created from this order', 'Placeholders: url of subscription, ID subscription', 'yith-woocommerce-subscription' ),
							admin_url( 'post.php?post=' . $subscription_id . '&action=edit' ),
							$subscription->get_number()
						)
					);

				}
			}

			if ( ! empty( $order_args ) ) {
				foreach ( $order_args as $key => $value ) {
					$order->update_meta_data( $key, $value );
				}
				$order->save();
				if ( apply_filters( 'ywsbs_calculate_order_totals_condition', true ) ) {
					$order->calculate_totals();
					WC()->session->set( 'ywsbs_order_args', $order_args );
				}
			}

			do_action( 'ywcsb_after_calculate_totals', $order );
		}


		/**
		 * After payment complete
		 *
		 * @param   int $order_id  Order id.
		 */
		public function payment_complete( $order_id ) {
			$order         = wc_get_order( $order_id );
			$subscriptions = $order->get_meta( 'subscriptions' );
			if ( ! empty( $subscriptions ) ) {
				foreach ( $subscriptions as $subscription_id ) {
					$subscription = ywsbs_get_subscription( $subscription_id );
					$renew_order  = $subscription->renew_order;
					if ( 0 !== $renew_order && $renew_order == $order_id ) { //phpcs:ignore
						$subscription->update_subscription( $order_id );
					} elseif ( empty( $renew_order ) ) {  //phpcs:ignore
						$subscription->start_subscription( $order_id );
					}

					do_action( 'ywsbs_subscription_payment_complete', $subscription, $order );
				}
			}
		}

		/**
		 * Create the renew order
		 *
		 * @param   int $subscription_id  Subscription id.
		 *
		 * @return false|int|null
		 * @throws WC_Data_Exception Throws an Exception.
		 */
		public function renew_order( $subscription_id ) {

			$subscription   = ywsbs_get_subscription( $subscription_id );
			$parent_order   = $subscription->get_order();
			$status         = $this->get_renew_order_status( $subscription );
			$renew_order_id = $subscription->can_be_create_a_renew_order();

			if ( $renew_order_id && is_int( $renew_order_id ) ) {
				return $renew_order_id;
			} elseif ( false === $renew_order_id ) {
				return false;
			}

			if ( ! $parent_order ) {
				// The renew order cannot created because the parent order not exist.
				$subscription->cancel();
				return false;
			}

			if ( apply_filters( 'ywsbs_skip_create_renew_order', false, $subscription ) ) {
				return false;
			}

			$args = array(
				'status'      => 'renew',
				'customer_id' => $subscription->user_id,
			);

			$order = wc_create_order( $args );

			do_action( 'ywsbs_after_create_renew_order', $order, $subscription );

			$args = array(
				'subscriptions'        => array( $subscription_id ),
				'payment_method'       => $subscription->get( 'payment_method' ),
				'payment_method_title' => $subscription->get( 'payment_method_title' ),
				'currency'             => $subscription->get_order_currency(),
			);

			$customer_note = $parent_order->get_customer_note();
			if ( $customer_note ) {
				$args['customer_note'] = $customer_note;
			}

			// get billing.
			$billing_fields = $subscription->get_address_fields( 'billing' );
			// get shipping.
			$shipping_fields = $subscription->get_address_fields( 'shipping' );

			$args = array_merge( $args, $shipping_fields, $billing_fields );

			foreach ( $args as $key => $field ) {
				$set = 'set_' . $key;
				if ( method_exists( $order, $set ) ) {
					$order->$set( $field );
				} else {
					$key = in_array( $key, apply_filters( 'yith_ywsbs_renew_order_custom_fields', array( 'billing_vat', 'billing_ssn', 'billing_yweu_vat' ), $order, $args ), true ) ? '_' . $key : $key;
					$order->update_meta_data( $key, $field );
				}
			}

			$order_id = $order->get_id();
			$_product = $subscription->get_product();

			$item_id = $order->add_product(
				$_product,
				$subscription->get( 'quantity' ),
				array(
					'variation' => array(),
					'totals'    => array(
						'subtotal'     => $subscription->get_line_subtotal(),
						'subtotal_tax' => $subscription->get_line_subtotal_tax(),
						'total'        => $subscription->get_line_total(),
						'tax'          => $subscription->get_line_tax(),
						'tax_data'     => $subscription->get_line_tax_data(),
					),
				)
			);

			if ( ! $item_id ) {
				throw new Exception( esc_html__( 'Error 402: unable to create the order. Please try again.', 'yith-woocommerce-subscription' ) );
			} else {
				$item             = $order->get_item( $item_id );
				$metadata         = get_metadata( 'order_item', $subscription->get( 'order_item_id' ) );
				$metadata_to_skip = apply_filters( 'ywsbs_itemmeta_to_skip_in_renew_order', array( '_reduced_stock' ) );
				if ( $metadata ) {
					foreach ( $metadata as $key => $value ) {
						if ( in_array( $key, $metadata_to_skip, true ) ) {
							continue;
						}

						if ( apply_filters( 'ywsbs_renew_order_item_meta_data', is_array( $value ) && count( $value ) === 1 && '_fee' !== $key, $subscription->get( 'order_item_id' ), $key, $value ) ) {
							$item->update_meta_data( $key, maybe_unserialize( $value[0] ) );
						}
					}

					$item->save();
				}
			}

			// Shipping.
			if ( apply_filters( 'ywsbs_add_shipping_cost_order_renew', ! empty( $subscription->subscriptions_shippings ) ) ) {

				$shipping_item_id = wc_add_order_item(
					$order_id,
					array(
						'order_item_name' => $subscription->subscriptions_shippings['name'],
						'order_item_type' => 'shipping',
					)
				);

				$shipping_cost     = isset( $subscription->subscriptions_shippings['cost'] ) ? $subscription->subscriptions_shippings['cost'] : 0;
				$shipping_cost_tax = 0;

				if ( isset( $subscription->subscriptions_shippings['method_id'] ) ) {
					wc_add_order_item_meta( $shipping_item_id, 'method_id', $subscription->subscriptions_shippings['method_id'] );
				}

				wc_add_order_item_meta( $shipping_item_id, 'cost', wc_format_decimal( $shipping_cost ) );
				if ( isset( $subscription->subscriptions_shippings['taxes'] ) ) {
					wc_add_order_item_meta( $shipping_item_id, 'taxes', $subscription->subscriptions_shippings['taxes'] );
				}

				if ( ! empty( $subscription->subscriptions_shippings['taxes'] ) ) {
					foreach ( $subscription->subscriptions_shippings['taxes'] as $tax_cost ) {
						$tx_cost = 0;
						if ( is_array( $tax_cost ) ) {
							foreach ( $tax_cost as $tc ) {
								$tx_cost += floatval( $tc );
							}
						} else {
							$tx_cost = $tax_cost;
						}
						$shipping_cost_tax += $tx_cost;
					}
				}

				$order->set_shipping_total( $shipping_cost );
				$order->set_shipping_tax( $shipping_cost_tax );
				$order->save();

			} else {
				do_action( 'ywsbs_add_custom_shipping_costs', $order, $subscription );
			}

			if ( isset( $subscription->subscriptions_shippings['taxes'] ) && $subscription->subscriptions_shippings['taxes'] ) {
				/**
				 * This fix the shipping taxes removed form WC settings
				 * if in a previous tax there was the taxes this will be forced
				 * even if they are disabled for the shipping
				 */
				add_action( 'woocommerce_find_rates', array( $this, 'add_shipping_tax' ), 10 );
			}

			$order->update_meta_data( 'is_a_renew', 'yes' );
			$order->update_taxes();
			$order->calculate_totals();

			$order->set_status( $status );
			$order->save();

			// translators: placeholder subscription id.
			$order->add_order_note( sprintf( __( 'This order has been created to renew subscription <a href="%1$s" target="_blank">#%2$s</a>', 'yith-woocommerce-subscription' ), admin_url( 'post.php?post=' . $subscription->id . '&action=edit' ), $subscription->id ) );

			// attach the new order to the subscription.
			$orders = $subscription->get( 'order_ids' );
			array_push( $orders, $order_id );
			$subscription->set( 'order_ids', $orders );

			$subscription->set( 'renew_order', $order_id );

			do_action( 'ywsbs_renew_subscription', $order_id, $subscription_id );

			return $order_id;
		}

		/**
		 * Pay renew order
		 *
		 * @since 4.0.0
		 * @param int $renew_order_id  Renew order id to pay.
		 * @return void
		 */
		public function pay_renew_order( $renew_order_id ) {

			$renew_order = wc_get_order( $renew_order_id );

			if ( ! $renew_order ) {
				yith_subscription_log( 'The renew order #' . $renew_order_id . ' does not exists.', 'subscription_payment' );
				return;
			}

			yith_subscription_log( 'Pay order #' . $renew_order_id, 'subscription_payment' );
			! defined( 'YITH_DOING_RENEWS' ) && define( 'YITH_DOING_RENEWS', true );

			if ( isset( WC()->cart ) && function_exists( 'YWSBS_Subscription_Cart' ) ) {
				remove_action( 'woocommerce_available_payment_gateways', array( YWSBS_Subscription_Cart(), 'disable_gateways' ), 100 );
			}

			$subscriptions = $renew_order->get_meta( 'subscriptions' );
			$subscription  = ( is_array( $subscriptions ) && ! empty( $subscriptions ) ) ? ywsbs_get_subscription( array_shift( $subscriptions ) ) : false;

			if ( ! $subscription || empty( $subscription->get_post() ) ) {
				yith_subscription_log( 'The renew order #' . $renew_order_id . ' cannot be processed because the related subscription does not exists.', 'subscription_payment' );
				return;
			}

			$gateway_id = $renew_order->get_payment_method();

			yith_subscription_log( 'The renew order ' . $renew_order . ' should be pay with ' . $renew_order->get_payment_method_title() . '( ' . $gateway_id . ' )', 'subscription_payment' );
			do_action( 'ywsbs_pay_renew_order_with_' . $gateway_id, $renew_order );
		}

		/**
		 * Get the renew order status
		 *
		 * @param   YWSBS_Subscription $subscription  Subscription.
		 *
		 * @return string
		 */
		public function get_renew_order_status( $subscription = null ) {

			$new_status = 'on-hold';

			if ( ! is_null( $subscription ) && 'bacs' === $subscription->payment_method ) {
				$new_status = 'pending';
			}

			// the status must be register as wc status.
			$status = apply_filters( 'ywsbs_renew_order_status', $new_status, $subscription );

			return $status;
		}

		/**
		 * This fix the shipping taxes removed form WC settings
		 * if in a previous tax there was the taxes this will be forced
		 * even if they are disabled for the shipping.
		 *
		 * @param   array $shipping_taxes  Shipping taxes.
		 *
		 * @return mixed
		 */
		public function add_shipping_tax( $shipping_taxes ) {

			foreach ( $shipping_taxes as &$shipping_tax ) {
				$shipping_tax['shipping'] = 'yes';

			}

			return $shipping_taxes;
		}

		/**
		 * Return false if the option reduce order stock is disabled for the renew order
		 *
		 * @param   bool     $result  Current filter value.
		 * @param   WC_Order $order   Order.
		 *
		 * @return bool
		 * @since  2.0.0
		 */
		public function can_reduce_order_stock( $result, $order ) {
			$is_a_renew = $order->get_meta( 'is_a_renew' );

			if ( 'yes' === $is_a_renew && 'yes' === get_option( 'ywsbs_disable_the_reduction_of_order_stock_in_renew' ) ) {
				$result = false;
			}

			return $result;
		}


		/**
		 * Delete all subscription if the main order in deleted.
		 *
		 * @param   int $order_id  Order id.
		 */
		public static function delete_subscriptions( $order_id ) {
			if ( 'shop_order' === get_post_type( $order_id ) ) {

				$order = wc_get_order( $order_id );

				if ( ! $order ) {
					return;
				}

				$is_a_renew    = $order->get_meta( 'is_a_renew' );
				$subscriptions = $order->get_meta( 'subscriptions' );

				if ( empty( $subscriptions ) || 'yes' === $is_a_renew ) {
					return;
				}

				foreach ( $subscriptions as $subscription_id ) {
					$subscription = ywsbs_get_subscription( $subscription_id );
					// check if the subscription exists.
					if ( is_null( $subscription->post ) ) {
						continue;
					}

					$subscription->delete();
				}
			}
		}

		/**
		 * Trash all subscriptions if the main order in trashed.
		 *
		 * @param   int $order_id  Order id.
		 *
		 * @return void
		 */
		public static function trash_subscriptions( $order_id ) {
			if ( 'shop_order' === get_post_type( $order_id ) ) {

				$order = wc_get_order( $order_id );

				if ( ! $order ) {
					return;
				}

				$is_a_renew    = $order->get_meta( 'is_a_renew' );
				$subscriptions = $order->get_meta( 'subscriptions' );

				if ( empty( $subscriptions ) || 'yes' === $is_a_renew ) {
					return;
				}

				foreach ( $subscriptions as $subscription_id ) {
					$subscription = ywsbs_get_subscription( $subscription_id );
					// check if the subscription exists.
					if ( is_null( $subscription->post ) ) {
						continue;
					}

					$subscription->delete();
				}
			}
		}
	}
}

/**
 * Unique access to instance of YWSBS_Subscription_Order class
 *
 * @return YWSBS_Subscription_Order
 */
function YWSBS_Subscription_Order() { //phpcs:ignore
	return YWSBS_Subscription_Order::get_instance();
}
