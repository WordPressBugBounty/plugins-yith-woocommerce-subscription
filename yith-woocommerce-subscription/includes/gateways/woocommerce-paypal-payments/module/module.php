<?php
/**
 * The module.
 *
 * @package WooCommerce\PayPalCommerce\Subscription
 */

declare(strict_types=1);

return static function (): YWSBS_WC_PayPal_Payments_Module {
	return new YWSBS_WC_PayPal_Payments_Module();
};
