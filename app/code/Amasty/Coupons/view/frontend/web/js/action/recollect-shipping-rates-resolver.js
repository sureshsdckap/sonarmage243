/* Resolve recollect shipping action for different magento versions */
define([
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/action/select-shipping-address',
    'Magento_Checkout/js/model/shipping-rate-registry'
], function (quote, selectShippingAddress, rateRegistry) {
    'use strict';

    var recollectShippingRates;

    // eslint-disable-next-line global-require
    require([ 'Magento_Checkout/js/action/recollect-shipping-rates' ], function (component) {
        recollectShippingRates = component;
    }, function () {
        // magento <=2.3.3 compatibility
        recollectShippingRates = function () {
            var shippingAddress = null;

            if (!quote.isVirtual()) {
                shippingAddress = quote.shippingAddress();

                rateRegistry.set(shippingAddress.getCacheKey(), null);
                selectShippingAddress(shippingAddress);
            }
        };
    });

    return function () {
        return recollectShippingRates();
    };
});
