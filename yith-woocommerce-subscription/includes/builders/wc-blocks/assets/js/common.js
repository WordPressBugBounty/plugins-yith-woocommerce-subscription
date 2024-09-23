import {select} from '@wordpress/data';

export const cartHasSubscription = () => {

  let hasSubscriptions = false;
  const store = select('wc/store/cart');
  const cartData = store.getCartData();

  if (cartData.items.length > 0) {
    const items = cartData.items;
    const sbs = items.find(item => {
      const data = item.item_data;
      if (data.length > 0) {
        const singleData = data.find(singleData => singleData.name === 'ywsbs-subscription-info');
        if (singleData) {
          return true;
        }
      }
    });
    hasSubscriptions = typeof sbs === 'object';
  }

  return hasSubscriptions;
};