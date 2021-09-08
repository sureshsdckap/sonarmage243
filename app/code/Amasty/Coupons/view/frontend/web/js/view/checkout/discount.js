/** Coupon codes field view for checkout page */
define([
    'Amasty_Coupons/js/view/abstract-discount',
    'Amasty_Coupons/js/model/checkout/apply-response-processor'
], function (Component, responseProcessor) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Amasty_Coupons/checkout/discount'
        },
        responseProcessor: responseProcessor
    });
});
