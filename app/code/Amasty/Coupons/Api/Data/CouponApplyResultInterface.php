<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Coupons
 */


declare(strict_types=1);

namespace Amasty\Coupons\Api\Data;

interface CouponApplyResultInterface
{
    /**
     * Is coupon valid and applied to quote.
     *
     * @return bool
     */
    public function isApplied(): bool;

    /**
     * Coupon code.
     *
     * @return string
     */
    public function getCode(): string;
}
