/** Add observable array of coupons */
define([
    'ko',
    'underscore',
    'mage/utils/wrapper',
    './coupon'
], function (ko, _, wrapper, coupon) {
    'use strict';

    return function (couponModel) {
        coupon.setCouponCode= function (origin, couponCode) {
            origin(couponCode);
            this.couponsArray(this.renderCoupons(couponCode));
        };

        return wrapper.extend(couponModel, coupon);
    };
});
