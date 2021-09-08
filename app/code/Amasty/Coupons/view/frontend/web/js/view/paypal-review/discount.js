/** Coupon codes field view for paypal review page */
define([
    'Amasty_Coupons/js/view/abstract-discount',
    'Amasty_Coupons/js/model/abstract-apply-response-processor'
], function (Component, responseProcessor) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Amasty_Coupons/cart/discount',
            selectors: {
                form: '#discount-coupon-form'
            }
        },
        responseProcessor: responseProcessor.extend({
            onSuccess: function () {
                this._super();

                window.location.reload();
            }
        })
    });
});
