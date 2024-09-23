/**
 * Change the name to the placeholder if the cart has susbcriptions
 */
import {cartHasSubscription} from '../../../common';

document.addEventListener( "DOMContentLoaded", () => {
	if (window?.wc?.blocksCheckout && cartHasSubscription()) {
		const {registerCheckoutFilters} = window.wc.blocksCheckout;
		const label = () => yith_ywsbs_wc_blocks.checkout_label;

		registerCheckoutFilters('ywsbs-place-order-button-label', {
			placeOrderButtonLabel: label,
		});
	}
});
