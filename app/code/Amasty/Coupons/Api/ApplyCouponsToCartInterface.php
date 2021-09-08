<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Coupons
 */


namespace Amasty\Coupons\Api;

/**
 * Apply Coupons List to cart by cartId/quoteId.
 * @api
 */
interface ApplyCouponsToCartInterface
{
    /**
     * Try to apply list of coupons.
     * Return lists of applied and failed coupons.
     *
     * @param int $cartId The cart ID.
     * @param string[] $couponCodes The coupon code data.
     * @return \Amasty\Coupons\Api\Data\CouponApplyResultInterface[]
     */
    public function apply(int $cartId, array $couponCodes);
}
