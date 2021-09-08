/** Apply coupon codes response processor for cart page */
define([
    'Amasty_Coupons/js/model/abstract-apply-response-processor',
    'Magento_Customer/js/customer-data',
    'Magento_Checkout/js/model/totals',
    'Amasty_Coupons/js/action/recollect-shipping-rates-resolver',
    'Magento_Checkout/js/model/cart/cache'
], function (
    abstractProcessor,
    customerData,
    totals,
    recollectShippingRates,
    cartCache
) {
    'use strict';

    return abstractProcessor.extend({
        onSuccess: function () {
            this._super();

            cartCache.clear('rates');

            customerData.invalidate([ 'cart-data' ]);
            customerData.reload([ 'cart' ]);
            totals.isLoading(true);
            recollectShippingRates();
        }
    });
});
