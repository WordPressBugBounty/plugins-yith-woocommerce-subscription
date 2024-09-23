<?php
/**
 * This file belongs to the YIT Framework.
 *
 * This source file is subject to the GNU GENERAL PUBLIC LICENSE (GPL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @package YITH WooCommerce Subscription
 * @since   1.0.0
 * @author  YITH <plugins@yithemes.com>
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) || ! defined( 'YITH_YWSBS_VERSION' ) ) {
	exit;
}


$section1 = array(

	'section_customization_settings'      => array(
		'name' => esc_html__( 'Product page', 'yith-woocommerce-subscription' ),
		'type' => 'title',
		'id'   => 'ywsbs_section_customization',
	),

	'add_to_cart_label'                   => array(
		'name'      => esc_html__( '"Add to cart" label in subscription products', 'yith-woocommerce-subscription' ),
		'desc'      => esc_html__( 'Replace the "Add to cart" button label in subscription products.', 'yith-woocommerce-subscription' ),
		'id'        => 'ywsbs_add_to_cart_label',
		'type'      => 'yith-field',
		'yith-type' => 'text',
		'default'   => esc_html__( 'Subscribe', 'yith-woocommerce-subscription' ),
	),


	'section_end_form'                    => array(
		'type' => 'sectionend',
		'id'   => 'ywsbs_section_customization_end_form',
	),

	'section_cart_settings'               => array(
		'name' => esc_html__( 'Cart and Checkout', 'yith-woocommerce-subscription' ),
		'type' => 'title',
		'id'   => 'ywsbs_section_cart_customization',
	),

	'place_order_label'                   => array(
		'name'      => esc_html__( '"Place Order" label in checkout page', 'yith-woocommerce-subscription' ),
		'desc'      => esc_html__( 'This text replaces the "Place order" button label if there is at least one subscription product added to the cart.', 'yith-woocommerce-subscription' ),
		'id'        => 'ywsbs_place_order_label',
		'type'      => 'yith-field',
		'yith-type' => 'text',
		'default'   => wp_kses_post( __( 'Signup now', 'yith-woocommerce-subscription' ) ),
	),


	'section_cart_customization_end_form' => array(
		'type' => 'sectionend',
		'id'   => 'ywsbs_section_cart_customization_end_form',
	),


);

$settings = array(
	'customization' => $section1,
);

return apply_filters( 'yith_ywsbs_panel_customization_options', $settings );
