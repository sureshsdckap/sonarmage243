/** Summary totals block with collapsable items */
define([
    'Magento_Checkout/js/view/summary/abstract-total',
    'Magento_Checkout/js/model/quote',
    'jquery'
], function (Component, quote, $) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Amasty_Coupons/totals',
            rules: false,
            visible: false,
            style: 'display: none;',
            discountTotalsSelector: '.cart-summary tr.totals',
            listens: {
                visible: 'onVisibilityChange'
            }
        },

        /**
         * @return {Object}
         */
        initObservable: function () {
            this._super();
            this.observe(['rules', 'visible', 'style']);

            return this;
        },

        /**
         * initialize
         * @return {Object}
         */
        initialize: function () {
            this._super();
            this.initCollapseBreakdown();
            quote.totals.subscribe(this.getDiscountDataFromTotals.bind(this));
            this.getDiscountDataFromTotals(quote.totals());

            return this;
        },

        /**
         * @returns {void}
         */
        initCollapseBreakdown: function () {
            $(document).on('click', this.discountTotalsSelector, this.collapseBreakdown.bind(this));
        },

        /**
         * @returns {void}
         */
        collapseBreakdown: function () {
            this.visible(!this.visible());
            $(this.discountTotalsSelector + ' .title').toggleClass('-active', this.visible());
        },

        /**
         * @param {Array} totals
         * @returns {void}
         */
        getDiscountDataFromTotals: function (totals) {
            if (totals.extension_attributes && totals.extension_attributes.amcoupon_discount_breakdown) {
                this.rules(totals.extension_attributes.amcoupon_discount_breakdown);
            } else {
                this.rules(null);
            }
        },

        /**
         * hide/show table row
         * @returns {Object}
         */
        onVisibilityChange: function () {
            if (this.visible()) {
                return this.style('display: table-row;');
            }

            return this.style('display: none;');
        }
    });
});
