<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * YWSBS_Subscription_Order Legacy Abstract Class.
 *
 * @class   YWSBS_Subscription_Order_Legacy
 * @since   3.0.0
 * @author  YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

/**
 * Class YWSBS_Subscription_Legacy
 */
abstract class YWSBS_Subscription_Order_Legacy {
	/**
	 * Save the options of subscription in an array with order item id
	 *
	 * @access     public
	 *
	 * @param   int                   $item_id   Order item id.
	 * @param   WC_Order_Item_Product $item      Order Item object.
	 * @param   int                   $order_id  Order id.
	 *
	 * @return void
	 * @deprecated 3.0.0
	 */
	public function add_subscription_order_item_meta( $item_id, $item, $order_id ) {
		_deprecated_function( 'YWSBS_Subscription_Order::add_subscription_order_item_meta', '3.0.0', 'This method will not be used in the future because the logic to create a subscription when an order is submitted has changed.' );
		if ( isset( $item->legacy_cart_item_key ) ) {
			$this->cart_item_order_item[ $item->legacy_cart_item_key ] = $item_id;
		}
	}


	/**
	 * Save the options of subscription in an array with order item id
	 *
	 * @access public
	 *
	 * @param   int   $item_id        Item id.
	 * @param   array $values         Values.
	 * @param   int   $cart_item_key  Cart item key.
	 *
	 * @return void
	 */
	public function add_subscription_order_item_meta_before_wc3( $item_id, $values, $cart_item_key ) {
		_deprecated_function( 'YWSBS_Subscription_Order::add_subscription_order_item_meta_before_wc3', '3.0.0', 'This method will not be used in the future because the logic to create a subscription when an order is submitted has changed.' );
		$this->cart_item_order_item[ $cart_item_key ] = $item_id;
	}


	/**
	 * Save some info if a subscription is in the cart
	 *
	 * @access     public
	 *
	 * @param   int   $order_id  Order id.
	 * @param   array $posted    Post variable.
	 *
	 * @throws Exception Trigger error.
	 * @deprecated 3.0.0
	 */
	public function get_extra_subscription_meta( $order_id, $posted ) {
		_deprecated_function( 'get_formatted_recurring::get_extra_subscription_meta', '3.0.0', 'This method will not be used in the future because the logic to create a subscription when an order is submitted has changed.' );
	}


	/**
	 * Check in the order if there's a subscription and create it
	 *
	 * @param   int   $order_id  Order ID.
	 * @param   array $posted    $_POST variable.
	 *
	 * @return void
	 * @throws Exception Trigger an error.
	 * @depracated 3.0.0
	 */
	public function check_order_for_subscription( $order_id, $posted ) {
		_deprecated_function( 'YWSBS_Subscription_Order::check_order_for_subscription', '3.0.0', 'This method will not be used in the future because the logic to create a subscription when an order is submitted has changed.' );
	}

	/**
	 * Save some info if a subscription is in the cart
	 *
	 * @access public
	 *
	 * @param   int   $order_id  Order id.
	 * @param   array $posted    Posted.
	 *
	 * @throws Exception Throws an Exception.
	 */
	public function get_extra_subscription_meta_before_wc3( $order_id, $posted ) {
		_deprecated_function( 'YWSBS_Subscription_Order::get_extra_subscription_meta_before_wc3', '3.0.0', 'This method will not be used in the future because the logic to create a subscription when an order is submitted has changed.' );
	}

	/**
	 * Old function
	 *
	 * @param   int $subscription_id  Subscription id.
	 *
	 * @return mixed
	 * @throws Exception Throws an Exception.
	 */
	public function renew_order_old( $subscription_id ) {

		$subscription      = new YWSBS_Subscription( $subscription_id );
		$subscription_meta = $subscription->get_subscription_meta();

		$order = wc_create_order(
			array(
				'status'      => 'on-hold',
				'customer_id' => $subscription_meta['user_id'],
			)
		);
		$args  = array(
			'subscriptions'       => array( $subscription_id ),
			'billing_first_name'  => $subscription_meta['billing_first_name'],
			'billing_last_name'   => $subscription_meta['billing_last_name'],
			'billing_company'     => $subscription_meta['billing_company'],
			'billing_address_1'   => $subscription_meta['billing_address_1'],
			'billing_address_2'   => $subscription_meta['billing_address_2'],
			'billing_city'        => $subscription_meta['billing_city'],
			'billing_state'       => $subscription_meta['billing_state'],
			'billing_postcode'    => $subscription_meta['billing_postcode'],
			'billing_country'     => $subscription_meta['billing_country'],
			'billing_email'       => $subscription_meta['billing_email'],
			'billing_phone'       => $subscription_meta['billing_phone'],
			'shipping_first_name' => $subscription_meta['shipping_first_name'],
			'shipping_last_name'  => $subscription_meta['shipping_last_name'],
			'shipping_company'    => $subscription_meta['shipping_company'],
			'shipping_address_1'  => $subscription_meta['shipping_address_1'],
			'shipping_address_2'  => $subscription_meta['shipping_address_2'],
			'shipping_city'       => $subscription_meta['shipping_city'],
			'shipping_state'      => $subscription_meta['shipping_state'],
			'shipping_postcode'   => $subscription_meta['shipping_postcode'],
			'shipping_country'    => $subscription_meta['shipping_country'],
		);

		foreach ( $args as $key => $value ) {
			$order->update_meta_data( '_' . $key, $value );
		}
		$order->save();
		$_product = wc_get_product( ( isset( $subscription_meta['variation_id'] ) && ! empty( $subscription_meta['variation_id'] ) ) ? $subscription_meta['variation_id'] : $subscription_meta['product_id'] );

		$total     = 0;
		$tax_total = 0;

		$variations = array();

		$order_id = $order->get_id();
		$item_id  = $order->add_product(
			$_product,
			$subscription_meta['quantity'],
			array(
				'variation' => $variations,
				'totals'    => array(
					'subtotal'     => $subscription_meta['line_subtotal'],
					'subtotal_tax' => $subscription_meta['line_subtotal_tax'],
					'total'        => $subscription_meta['line_total'],
					'tax'          => $subscription_meta['line_tax'],
					'tax_data'     => maybe_unserialize( $subscription_meta['line_tax_data'] ),
				),
			)
		);

		if ( ! $item_id ) {
			throw new Exception( __( 'Error 404: unable to create the order. Please try again.', 'yith-woocommerce-subscription' ) );
		} else {
			$total     += $subscription_meta['line_total'];
			$tax_total += $subscription_meta['line_tax'];
		}

		$shipping_cost = 0;
		// Shipping.
		if ( ! empty( $subscription_meta['subscriptions_shippings'] ) ) {
			foreach ( $subscription_meta['subscriptions_shippings'] as $ship ) {
				$args             = array(
					'order_item_name' => $ship['method']->label,
					'order_item_type' => 'shipping',
				);
				$shipping_item_id = wc_add_order_item( $order_id, $args );

				$shipping_cost += $ship['method']->cost;
				wc_add_order_item_meta( $shipping_item_id, 'method_id', $ship['method']->method_id );
				wc_add_order_item_meta( $shipping_item_id, 'cost', wc_format_decimal( $ship['method']->cost ) );
				wc_add_order_item_meta( $shipping_item_id, 'taxes', $ship['method']->taxes );
			}

			$order->set_shipping_total( $shipping_cost );
			$order->set_shipping_tax( $subscription->subscriptions_shippings['taxes'] );
		}

		$order->calculate_taxes();
		$order->calculate_totals();

		// attach the new order to the subscription.
		$subscription_meta['order_ids'][] = $order_id;
		$subscription->set( 'order_ids', $subscription_meta['order_ids'] );
		// translators: placeholder is the subscription id.
		$order->add_order_note( sprintf( __( 'This order has been created to renew the subscription #%d', 'yith-woocommerce-subscription' ), $subscription_id ) );

		return $order_id;
	}
}
