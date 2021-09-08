/** Observable array of coupons */
define([
    'ko'
], function (ko) {
    'use strict';

    return {
        couponsArray: ko.observableArray([]),

        /**
         * Parse string coupon into array coupons
         * @param {string} couponsString
         * @returns {string[]}
         */
        renderCoupons: function (couponsString) {
            var coupons = [];

            if (typeof couponsString != 'string') {
                return coupons;
            }

            coupons = couponsString.split(',');
            coupons = _.map(coupons, function (coupon) {
                return coupon.trim();
            });
            coupons = _.filter(coupons, function (coupon) {
                return !!coupon;
            });

            return coupons;
        },
    };
});