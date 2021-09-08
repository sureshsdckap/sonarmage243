define([
    'jquery'
], function ($) {
    'use strict';

    var coupons = {
        amCancelCoupon: function (codeElement) {
            var elements = $('.amCouponsCode'),
                codeToRemove = codeElement.val(),
                couponCodes = [];

            for (var i = 0; i < elements.length; i++) {
                if (elements[i].value !== codeToRemove) {
                    couponCodes.push(elements[i].value);
                }
            }
            order.applyCoupon(couponCodes.join());
        }
    };

    $('#order-items').on('click', '.action-remove', function () {
        coupons.amCancelCoupon($(this).parent().children('.amCouponsCode'));
    });
});
