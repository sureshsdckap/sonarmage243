<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Coupons
 */


declare(strict_types=1);

namespace Amasty\Coupons\Model\Coupon;

use Amasty\Coupons\Api\GetCouponsByCartIdInterface;
use Amasty\Coupons\Model\CouponRenderer;
use Magento\Quote\Model\QuoteRepository;

class GetCouponsByCartId implements GetCouponsByCartIdInterface
{
    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    /**
     * @var CouponRenderer
     */
    private $couponRenderer;

    public function __construct(
        QuoteRepository $quoteRepository,
        CouponRenderer $couponRenderer
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->couponRenderer = $couponRenderer;
    }

    /**
     * Return list of applied coupons in a specified cart.
     *
     * @param int $cartId The cart ID.
     *
     * @return string[]
     */
    public function get(int $cartId): array
    {
        $quote = $this->quoteRepository->get($cartId);
        if (!$quote->getCouponCode()) {
            return [];
        }

        return $this->couponRenderer->parseCoupon($quote->getCouponCode());
    }
}
