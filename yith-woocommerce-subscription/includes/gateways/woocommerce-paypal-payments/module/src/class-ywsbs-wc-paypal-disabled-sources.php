<?php
/**
 * Creates the list of disabled funding sources.
 *
 * @package YITH/Subscription/Gateways
 */

declare( strict_types = 1 );


use WooCommerce\PayPalCommerce\Button\Helper\DisabledFundingSources;
use WooCommerce\PayPalCommerce\WcGateway\Exception\NotFoundException;
use WooCommerce\PayPalCommerce\WcGateway\Gateway\CreditCardGateway;
use WooCommerce\PayPalCommerce\WcGateway\Gateway\PayPalGateway;
use WooCommerce\PayPalCommerce\WcGateway\Settings\Settings;

/**
 * Class SubscriptionModule
 */
class YWSBS_WC_PayPal_Disabled_Sources extends DisabledFundingSources {

	/**
	 * All existing funding sources.
	 *
	 * @var array
	 */
	protected $funding_sources;

	/**
	 * DisabledFundingSources constructor.
	 *
	 * @param Settings $settings The settings.
	 * @param array    $all_funding_sources All existing funding sources.
	 */
	public function __construct( Settings $settings, array $all_funding_sources ) {
		$this->funding_sources = $all_funding_sources;
		parent::__construct( $settings, $all_funding_sources );
	}

	/**
	 * Returns the list of funding sources to be disabled.
	 *
	 * @param string $context The context.
	 * @return array|int[]|mixed|string[]
	 * @throws NotFoundException When the setting is not found.
	 */
	public function sources( string $context ) {
		if ( $this->disabled_sources() ) {
			unset( $this->funding_sources['paypal'] );
			return $this->funding_sources;
		}

		// Continue with parent method.
		return parent::sources( $context );
	}

	/**
	 * Check if all sources expect PayPal must be disabled.
	 *
	 * @return bool
	 */
	private function disabled_sources(): bool {
		if ( ! YWSBS_Subscription_Cart::cart_has_subscriptions() ) {
			return false;
		}

		$disabled = get_option( 'ywsbs_pp_force_disabled_sources', '' );
		if ( empty( $disabled ) ) {

			$subscriptions = get_posts(
				array(
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'post_type'      => YITH_YWSBS_POST_TYPE,
					'meta_query'     => array( // phpcs:ignore
						array(
							'key'     => 'payment_method',
							'value'   => array( PayPalGateway::ID, CreditCardGateway::ID ),
							'compare' => 'IN',
						),
					),
				)
			);

			$disabled = empty( $subscriptions ) ? 'yes' : 'no';
			update_option( 'ywsbs_pp_force_disabled_sources', $disabled );
		}

		return 'yes' === $disabled;
	}
}
