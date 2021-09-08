<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Coupons
 */


declare(strict_types=1);

namespace Amasty\Coupons\Model\Coupon;

use Amasty\Coupons\Api\Data\CouponApplyResultInterface;

class CouponApplyResult implements CouponApplyResultInterface
{
    /**
     * @var bool
     */
    private $isApplied;

    /**
     * @var string
     */
    private $code;

    /**
     * @param bool $isApplied
     * @param string $code
     */
    public function __construct(bool $isApplied, string $code)
    {
        $this->isApplied = $isApplied;
        $this->code = $code;
    }

    /**
     * Is coupon valid and applied to quote.
     *
     * @return bool
     */
    public function isApplied(): bool
    {
        return $this->isApplied;
    }

    /**
     * Coupon code.
     *
     * @return string
     */
    public function getCode(): string
    {
        return $this->code;
    }
}
