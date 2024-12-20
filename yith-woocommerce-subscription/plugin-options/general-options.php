<?php
/**
 * Subscription Options
 *
 * @since   1.0.0
 * @author  YITH <plugins@yithemes.com>
 * @package YITH WooCommerce Subscription
 */

$settings = array(

	'general' => array(

		'section_general_settings'                      => array(
			'name' => __( 'General settings', 'yith-woocommerce-subscription' ),
			'type' => 'title',
			'id'   => 'ywsbs_section_general',
		),

		'enable_manual_renews_gateways'                 => array(
			'name'      => esc_html__( 'Allow user to manually renew a subscription', 'yith-woocommerce-subscription' ),
			'desc'      => esc_html__( 'Choose whether a user can renew a subscription if the payment gateway does not support automatic payments.', 'yith-woocommerce-subscription' ),
			'id'        => 'ywsbs_enable_manual_renews',
			'type'      => 'yith-field',
			'yith-type' => 'radio',
			'options'   => array(
				'yes' => esc_html__( 'Yes, the customer will be able to pay the renewal order on My Account page, if the payment gateway does not support automatic payments.', 'yith-woocommerce-subscription' ),
				'no'  => esc_html__( 'No, only use the supported gateways enabled for automatic payments.', 'yith-woocommerce-subscription' ),
			),
			'default'   => 'no',
		),

		'disable_the_reduction_of_order_stock_in_renew' => array(
			'name'      => esc_html__( 'Stock management with recurring payments', 'yith-woocommerce-subscription' ),
			'desc'      => esc_html__( 'Choose if the recurring payments will reduce the stock count of a subscription product.', 'yith-woocommerce-subscription' ),
			'id'        => 'ywsbs_disable_the_reduction_of_order_stock_in_renew',
			'type'      => 'yith-field',
			'yith-type' => 'radio',
			'options'   => array(
				'no'  => esc_html__( 'Reduce stock of subscription products', 'yith-woocommerce-subscription' ),
				'yes' => esc_html__( 'Do not reduce stock of subscription products', 'yith-woocommerce-subscription' ),
			),
			'default'   => 'no',
		),

		'delete_subscription_order_cancelled'           => array(
			'name'      => esc_html__( 'Delete subscription if the main order is cancelled', 'yith-woocommerce-subscription' ),
			'desc'      => esc_html__( 'Enable if you want to delete a subscription when the main order is cancelled.', 'yith-woocommerce-subscription' ),
			'id'        => 'ywsbs_delete_subscription_order_cancelled',
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'default'   => 'yes',
		),

		'enable_log'                                    => array(
			'name'      => esc_html__( 'Enable log', 'yith-woocommerce-subscription' ),
			'desc'      => esc_html__( 'Enable to generate a list of plugin actions. Note: This is a useful option to develop improvements and to provide support.', 'yith-woocommerce-subscription' ),
			'id'        => 'ywsbs_enable_log',
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'default'   => 'yes',
		),

		'section_end_form'                              => array(
			'type' => 'sectionend',
			'id'   => 'ywsbs_section_general_end_form',
		),


		'section_extra_settings'                        => array(
			'name' => esc_html__( 'Extra settings', 'yith-woocommerce-subscription' ),
			'type' => 'title',
			'id'   => 'ywsbs_section_extra',
		),

		'enable_shop_manager'                           => array(
			'name'      => esc_html__( 'Shop manager can manage subscription settings', 'yith-woocommerce-subscription' ),
			'desc'      => esc_html__( 'Enable to allow the shop manager to access and edit the plugin options.', 'yith-woocommerce-subscription' ),
			'id'        => 'ywsbs_enable_shop_manager',
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'default'   => 'yes',
		),

		'section_extra_end_form'                        => array(
			'type' => 'sectionend',
			'id'   => 'ywsbs_section_extra_end_form',
		),


		// GDPR.

		'privacy_settings'                              => array(
			'name' => esc_html__( 'GPDR & Privacy', 'yith-woocommerce-subscription' ),
			'type' => 'title',
			'id'   => 'ywsbs_privacy_settings',
		),

		'erasure_request'                               => array(
			'name'      => esc_html__( 'Delete personal info after an account erasure requests', 'yith-woocommerce-subscription' ),
			'desc'      => sprintf( '%s <br> %s', esc_html__( 'Enable to erase the personal information of a subscription if an account erasure request is made.', 'yith-woocommerce-subscription' ), esc_html__( 'Note: all affected subscription status\' will be changed to \'cancelled\'.', 'yith-woocommerce-subscription' ) ),
			'id'        => 'ywsbs_erasure_request',
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'default'   => 'no',
		),

		'delete_unused_subscription'                    => array(
			'name'      => esc_html__( 'Delete pending and cancelled subscriptions', 'yith-woocommerce-subscription' ),
			'desc'      => esc_html__( 'Choose if pending and/or cancelled subscriptions can be trashed after the specified duration.', 'yith-woocommerce-subscription' ),
			'id'        => 'ywsbs_delete_personal_info',
			'type'      => 'yith-field',
			'yith-type' => 'onoff',
			'default'   => 'no',
		),

		'trash_pending_subscriptions'                   => array(
			'title'     => esc_html__( 'Delete pending subscriptions after', 'yith-woocommerce-subscription' ),
			'desc'      => esc_html__( 'Choose when to delete pending subscriptions.', 'yith-woocommerce-subscription' ),
			'id'        => 'ywsbs_trash_pending_subscriptions',
			'type'      => 'yith-field',
			'yith-type' => 'inline-fields',
			'fields'    => array(
				'number' => array(
					'type'              => 'number',
					'class'             => 'short-field',
					'custom_attributes' => 'style="width:100px"',
				),
				'unit'   => array(
					'type'              => 'select',
					'class'             => 'short-field',
					'custom_attributes' => 'style="width: 150px!important;"',
					'options'           => array(
						'days'   => esc_html__( 'days', 'yith-woocommerce-subscription' ),
						'weeks'  => esc_html__( 'weeks', 'yith-woocommerce-subscription' ),
						'months' => esc_html__( 'months', 'yith-woocommerce-subscription' ),
						'years'  => esc_html__( 'years', 'yith-woocommerce-subscription' ),
					),
				),
			),
			'deps'      => array(
				'id'    => 'ywsbs_delete_personal_info',
				'value' => 'yes',
			),
		),

		'trash_cancelled_subscriptions'                 => array(
			'title'     => esc_html__( 'Delete cancelled subscriptions after', 'yith-woocommerce-subscription' ),
			'desc'      => esc_html__( 'Choose when to delete cancelled subscriptions.', 'yith-woocommerce-subscription' ),
			'id'        => 'ywsbs_trash_cancelled_subscriptions',
			'type'      => 'yith-field',
			'yith-type' => 'inline-fields',
			'fields'    => array(
				'number' => array(
					'type'              => 'number',
					'custom_attributes' => 'style="width:100px"',
				),
				'unit'   => array(
					'type'              => 'select',
					'class'             => 'short-field',
					'custom_attributes' => 'style="width: 150px!important;"',
					'options'           => array(
						'days'   => esc_html__( 'days', 'yith-woocommerce-subscription' ),
						'weeks'  => esc_html__( 'weeks', 'yith-woocommerce-subscription' ),
						'months' => esc_html__( 'months', 'yith-woocommerce-subscription' ),
						'years'  => esc_html__( 'years', 'yith-woocommerce-subscription' ),
					),
				),
			),
			'deps'      => array(
				'id'    => 'ywsbs_delete_personal_info',
				'value' => 'yes',
			),
		),

		'section_end_privacy_settings'                  => array(
			'type' => 'sectionend',
			'id'   => 'ywsbs_section_end_privacy_settings',
		),
	),

);

return apply_filters( 'yith_ywsbs_panel_settings_options', $settings );
