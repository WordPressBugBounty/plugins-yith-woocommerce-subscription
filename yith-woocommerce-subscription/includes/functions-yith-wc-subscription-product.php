<?php
/**
 * Implements helper functions for YITH WooCommerce Subscription related to subscription product
 *
 * @since   2.0.0
 * @author  YITH
 * @package YITH\Subscription
 */

defined( 'YITH_YWSBS_INIT' ) || exit; // Exit if accessed directly.

if ( ! function_exists( 'ywsbs_is_subscription_product' ) ) {
	/**
	 * Check if a product is a subscription.
	 *
	 * @param WC_Product|int $product Product Object or Product ID.
	 * @return bool
	 * @since  2.0.0
	 */
	function ywsbs_is_subscription_product( $product ) {

		if ( is_numeric( $product ) ) {
			$product = wc_get_product( $product );
		}

		if ( ! $product ) {
			return false;
		}

		$is_subscription = $product->get_meta( '_ywsbs_subscription' );
		$price_is_per    = $product->get_meta( '_ywsbs_price_is_per' );

		$is_subscription = ( 'yes' === $is_subscription && '' !== $price_is_per );
		return apply_filters( 'ywsbs_is_subscription', $is_subscription, $product->get_id() );
	}
}


if ( ! function_exists( 'ywsbs_wp_radio' ) ) {
	/**
	 * Output a radio input box.
	 *
	 * @param array $field Field.
	 */
	function ywsbs_wp_radio( $field ) {
		global $thepostid, $post;

		$thepostid              = empty( $thepostid ) ? $post->ID : $thepostid;
		$field['class']         = isset( $field['class'] ) ? $field['class'] : 'select short';
		$field['style']         = isset( $field['style'] ) ? $field['style'] : '';
		$field['wrapper_class'] = isset( $field['wrapper_class'] ) ? $field['wrapper_class'] : '';
		$field['value']         = isset( $field['value'] ) ? $field['value'] : get_post_meta( $thepostid, $field['id'], true );
		$field['name']          = isset( $field['name'] ) ? $field['name'] : $field['id'];
		$field['desc_tip']      = isset( $field['desc_tip'] ) ? $field['desc_tip'] : false;

		echo '<fieldset class="form-field ' . esc_attr( $field['id'] ) . '_field ' . esc_attr( $field['wrapper_class'] ) . '"><legend>' . wp_kses_post( $field['label'] ) . '</legend>';

		if ( ! empty( $field['description'] ) && false !== $field['desc_tip'] ) {
			echo wp_kses_post( wc_help_tip( $field['description'] ) );
		}

		echo '<ul class="wc-radios">';

		foreach ( $field['options'] as $key => $value ) {

			echo '<li><label><input
				name="' . esc_attr( $field['name'] ) . '"
				value="' . esc_attr( $key ) . '"
				type="radio"
				class="' . esc_attr( $field['class'] ) . '"
				style="' . esc_attr( $field['style'] ) . '"
				' . checked( esc_attr( $field['value'] ), esc_attr( $key ), false ) . '
				/> ' . wp_kses_post( $value ) . '</label>
		</li>';
		}
		echo '</ul>';

		if ( ! empty( $field['description'] ) && false === $field['desc_tip'] ) {
			echo '<span class="description">' . wp_kses_post( $field['description'] ) . '</span>';
		}

		echo '</fieldset>';
	}
}


if ( ! function_exists( 'ywsbs_is_limited_product' ) ) {
	/**
	 * Check if a the subscription product is limited.
	 *
	 * @param WC_Product|int $product Product Object or Product ID.
	 * @return bool|string The value can be false or 'one-active'|'one'.
	 * @since  2.0.0
	 */
	function ywsbs_is_limited_product( $product ) {

		if ( is_numeric( $product ) ) {
			$product = wc_get_product( $product );
		}

		if ( ! $product || ! ywsbs_is_subscription_product( $product ) ) {
			return false;
		}

		$enable_limit = $product->get_meta( '_ywsbs_enable_limit' );
		$is_limited   = $product->get_meta( '_ywsbs_limit' );

		$is_limited = 'yes' === $enable_limit ? $is_limited : false;

		return apply_filters( 'ywsbs_is_limited_product', $is_limited, $product->get_id() );
	}
}



if ( ! function_exists( 'yith_ywsbs_get_product_meta' ) ) {
	/**
	 * Return the product meta of a variation product.
	 *
	 * @param YWSBS_Subscription $subscription Subscription.
	 * @param array              $attributes Attributes.
	 * @param bool               $echo Print or return meta flag.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	function yith_ywsbs_get_product_meta( $subscription, $attributes = array(), $echo = true ) {

		$item_data = array();

		// APPLY_FILTER: ywsbs_item_data: the meta data of a variation product can be filtered : YWSBS_Subscription is passed as argument.
		$item_data = apply_filters( 'ywsbs_item_data', $item_data, $subscription );
		$out       = '';
		// Output flat or in list format.
		if ( count( $item_data ) > 0 ) {
			foreach ( $item_data as $data ) {
				if ( $echo ) {
					echo esc_html( $data['key'] ) . ': ' . wp_kses_post( $data['value'] ) . "\n";
				} else {
					$out .= ' - ' . esc_html( $data['key'] ) . ': ' . wp_kses_post( $data['value'] ) . ' ';
				}
			}
		}

		return $out;
	}
}
