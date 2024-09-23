import parse from 'html-react-parser';
import {cartHasSubscription} from '../../../common';

document.addEventListener( "DOMContentLoaded", () => {

  if(  window?.wc?.blocksCheckout && cartHasSubscription() ){

    const { registerCheckoutFilters } = window.wc.blocksCheckout;

    /**
     * Change the cart item price adding the info about the subscription
     *
     * @param value
     * @param extensions
     * @param args
     * @returns {*|string}
     */
    registerCheckoutFilters( 'ywsbs-subscription-product-price', {
      subtotalPriceFormat: ( value, extensions, args ) => {

        if ( args?.context !== 'cart' &&   args?.context !== 'summary' ) {
          // Return early since this filter is not being applied in the Cart context.
          // We must return the original value we received here.
          return value;
        }

        const cartItem = args?.cartItem.item_data;
        if( !cartItem ){
          return value;
        }

        const ywsbsData = cartItem.find( item => item.name === 'ywsbs-price-html');
        if( !ywsbsData?.value){
          return value;
        }

        return '<price/> '+ parse(ywsbsData.value);
      },
    } );
  }
});
