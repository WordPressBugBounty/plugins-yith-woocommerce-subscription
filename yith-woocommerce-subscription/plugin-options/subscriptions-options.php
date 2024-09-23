<?php
/**
 * Subscription Options
 *
 * @package YITH WooCommerce Subscription
 * @since   1.0.0
 * @author  YITH <plugins@yithemes.com>
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) || ! defined( 'YITH_YWSBS_VERSION' ) ) {
	exit;
}

$section = array(
	'subscription_list_table' => array(
		'type'                  => 'post_type',
		'post_type'             => YITH_YWSBS_POST_TYPE,
		'wp-list-style'         => 'classic',
		'wp-list-auto-h-scroll' => true,
	),
);


return apply_filters( 'ywsbs_subscriptions_options', array( 'subscriptions' => $section ) );
