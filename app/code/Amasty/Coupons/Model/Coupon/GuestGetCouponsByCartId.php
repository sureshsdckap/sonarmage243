<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Coupons
 */


declare(strict_types=1);

namespace Amasty\Coupons\Model\Coupon;

use Amasty\Coupons\Api\Data\CouponApplyResultInterface;
use Amasty\Coupons\Api\GetCouponsByCartIdInterface;
use Amasty\Coupons\Api\GuestGetCouponsByCartIdInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;

class GuestGetCouponsByCartId implements GuestGetCouponsByCartIdInterface
{
    /**
     * @var MaskedQuoteIdToQuoteIdInterface
     */
    private $maskedQuoteIdToQuoteId;

    /**
     * @var GetCouponsByCartIdInterface
     */
    private $getCouponsByCartId;

    public function __construct(
        MaskedQuoteIdToQuoteIdInterface $maskedQuoteIdToQuoteId,
        GetCouponsByCartIdInterface $getCouponsByCartId
    ) {
        $this->maskedQuoteIdToQuoteId = $maskedQuoteIdToQuoteId;
        $this->getCouponsByCartId = $getCouponsByCartId;
    }

    /**
     * Return list of applied coupons in a specified cart.
     *
     * @param string $cartId The cart mask ID.
     *
     * @return CouponApplyResultInterface[]
     */
    public function get(string $cartId): array
    {
        return $this->getCouponsByCartId->get($this->maskedQuoteIdToQuoteId->execute($cartId));
    }
}
