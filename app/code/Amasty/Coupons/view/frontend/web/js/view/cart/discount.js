/** Coupon codes field view for cart page */
define([
    'Amasty_Coupons/js/view/abstract-discount',
    'Amasty_Coupons/js/model/cart/apply-response-processor'
], function (Component, responseProcessor) {
    'use strict';

    return Component.extend({
        defaults: {
            isLoading: true,
            template: 'Amasty_Coupons/cart/discount',
            selectors: {
                form: '#discount-coupon-form'
            }
        },
        responseProcessor: responseProcessor,
        initialize: function () {
            this._super();

            this.isLoading(false);

            return this;
        }
    });
});
