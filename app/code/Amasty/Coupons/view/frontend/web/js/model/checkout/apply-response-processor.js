/** Apply coupon codes response processor for checkout page */
define([
    'jquery',
    'Amasty_Coupons/js/model/abstract-apply-response-processor',
    'Magento_Checkout/js/action/get-payment-information',
    'Magento_Checkout/js/model/totals',
    'Amasty_Coupons/js/action/recollect-shipping-rates-resolver'
], function (
    $,
    abstractProcessor,
    getPaymentInformationAction,
    totals,
    recollectShippingRates
) {
    'use strict';

    return abstractProcessor.extend({
        onSuccess: function () {
            var deferred = $.Deferred();

            this._super();

            totals.isLoading(true);
            recollectShippingRates();
            getPaymentInformationAction(deferred);

            $.when(deferred).done(function () {
                totals.isLoading(false);
            });
        }
    });
});
