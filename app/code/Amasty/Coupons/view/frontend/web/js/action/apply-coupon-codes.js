/** Apply provided coupon. */
define([
    'ko',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/resource-url-manager',
    'Magento_Checkout/js/model/error-processor',
    'Magento_SalesRule/js/model/payment/discount-messages',
    'mage/storage'
], function (ko, quote, urlManager, errorProcessor, messageContainer, storage) {
    'use strict';

    var dataModifiers = [],
        action;

    /**
     * Apply provided coupon.
     *
     * @param {array} couponCodes
     * @param {CouponApplyReportProcessor} responseProcessor
     * @returns {Deferred}
     */
    action = function (couponCodes, responseProcessor) {
        var url,
            data = { 'couponCodes': couponCodes },
            headers = {},
            params = {},
            urls = {
                'guest': '/guest-carts/:cartId/multicoupons/apply/',
                'customer': '/carts/mine/multicoupons/apply/'
            };

        if (urlManager.getCheckoutMethod() === 'guest') {
            params = { cartId: quote.getQuoteId() };
        }

        url = urlManager.getUrl(urls, params);

        // Allowing to modify coupon-apply request
        dataModifiers.forEach(function (modifier) {
            modifier(headers, data);
        });

        return storage.post(
            url,
            JSON.stringify(data),
            false,
            null,
            headers
        ).done(
            responseProcessor.onSuccess
        ).fail(
            function (response) {
                responseProcessor.onFailure(response);
                errorProcessor.process(response, messageContainer);
            }
        ).always(responseProcessor.always);
    };

    /**
     * Modifying data to be sent.
     *
     * @param {Function} modifier
     * @returns {void}
     */
    action.registerDataModifier = function (modifier) {
        dataModifiers.push(modifier);
    };

    return action;
});
