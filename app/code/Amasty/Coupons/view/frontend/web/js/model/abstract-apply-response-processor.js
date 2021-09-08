/** Apply coupon codes response processor */
define([
    'ko',
    'underscore',
    'mage/utils/wrapper',
    'Amasty_Coupons/js/model/coupon'
], function (ko, _, wrapper, couponModel) {
    'use strict';

    /**
     * @typedef {Object} couponApplyResult
     * @property {boolean} applied - is coupons code applied
     * @property {string} code - coupon code string
     */

    /**
     * @class CouponApplyReportProcessor
     * @api
     */
    function CouponApplyReportProcessor() {
        this._bindEvents();
    }

    CouponApplyReportProcessor.prototype = {
        appliedCoupons: [],
        canceledCoupons: [],
        errorCoupons: [],
        notChangedCoupons: [],

        /**
         * Extend current object with _super
         *
         * @param {Object} extender
         * @returns {CouponApplyReportProcessor}
         */
        extend: function (extender) {
            var parent = this;

            _.each(extender, function (method, name) {
                parent[name] = wrapper.wrapSuper(parent[name], method);
            });

            this._bindEvents();

            return this;
        },

        /**
         * Bind all event functions
         * @private
         * @returns {void}
         */
        _bindEvents: function () {
            _.bindAll(
                this,
                'onSuccess',
                'onFailure',
                'always'
            );
        },

        /**
         * @param {couponApplyResult[]} response
         * @returns {void}
         */
        onSuccess: function (response) {
            this.renderResponseCoupon(response);

            couponModel.couponsArray(this.notChangedCoupons.concat(this.appliedCoupons));
        },

        /**
         * @returns {void}
         */
        onFailure: function () {},

        /**
         * @returns {void}
         */
        always: function () {},

        /**
         * Parse response codes and fill result array.
         *
         * @param {couponApplyResult[]} response
         * @returns {void}
         */
        renderResponseCoupon: function (response) {
            this.appliedCoupons = [];
            this.notChangedCoupons = [];
            this.canceledCoupons = [];
            this.errorCoupons = [];

            _.each(response, function (couponItem) {
                if (couponItem.applied) {
                    if (!couponModel.couponsArray().includes(couponItem.code)) {
                        this.appliedCoupons.push(couponItem.code);
                    } else {
                        this.notChangedCoupons.push(couponItem.code);
                    }
                } else if (couponItem.applied === false) {
                    this.errorCoupons.push(couponItem.code);
                }
            }, this);

            _.each(couponModel.couponsArray(), function (cachedCoupon) {
                if (!this.appliedCoupons.includes(cachedCoupon)
                    && !this.errorCoupons.includes(cachedCoupon)
                    && !this.notChangedCoupons.includes(cachedCoupon)
                ) {
                    this.canceledCoupons.push(cachedCoupon);
                }
            }, this);
        }
    };

    return new CouponApplyReportProcessor();
});
