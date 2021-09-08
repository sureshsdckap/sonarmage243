<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Coupons
 */


declare(strict_types=1);

namespace Amasty\Coupons\Plugin;

use Amasty\Coupons\Model\CouponRenderer;
use Amasty\Coupons\Model\SalesRule\FilterCoupons;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\CouponManagementInterface;

/**
 * Coupon Management Service Plugin.
 * API compatible.
 */
class CouponManagement
{
    /**
     * @var CouponRenderer
     */
    private $couponRenderer;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var FilterCoupons
     */
    private $filterCoupons;

    /**
     * @var \Amasty\Coupons\Model\QuoteCouponStorage
     */
    private $quoteCouponStorage;

    public function __construct(
        CouponRenderer $couponRenderer,
        CartRepositoryInterface $quoteRepository,
        FilterCoupons $filterCoupons,
        \Amasty\Coupons\Model\QuoteCouponStorage $quoteCouponStorage
    ) {
        $this->couponRenderer = $couponRenderer;
        $this->quoteRepository = $quoteRepository;
        $this->filterCoupons = $filterCoupons;
        $this->quoteCouponStorage = $quoteCouponStorage;
    }

    /**
     * @param CouponManagementInterface $subject
     * @param int $cartId The cart ID.
     * @param string $couponCode The coupon code data.
     *
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSet(CouponManagementInterface $subject, $cartId, $couponCode)
    {
        $couponCode = $this->prepareCoupon((int)$cartId, (string)$couponCode);
        $this->quoteCouponStorage->setQuoteCoupons((int)$cartId, $couponCode);

        return [$cartId, $couponCode];
    }

    /**
     * Render and filter coupon codes.
     * Return as string
     *
     * @param int $cartId
     * @param string $couponCode
     *
     * @return string
     */
    private function prepareCoupon(int $cartId, string $couponCode): string
    {
        $renderedCodes = $this->couponRenderer->render($couponCode);

        if (!empty($renderedCodes)) {
            $quote = $this->quoteRepository->getActive($cartId);
            $renderedCodes = $this->filterCoupons->validationFilter($renderedCodes, (int)$quote->getCustomerId());
        }

        return implode(',', $renderedCodes);
    }

    /**
     * Temporary fix for checkout compatibility
     * Override return type, return accepted coupon codes.
     *
     * @param CouponManagementInterface $subject
     * @param bool $result
     * @param string|int $cartId
     *
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @deprecated 2.0.0
     * @see \Amasty\Coupons\Api\ApplyCouponsToCartInterface::apply
     */
    public function afterSet(CouponManagementInterface $subject, $result, $cartId)
    {
        return $subject->get($cartId);
    }
}
