<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Coupons
 */


declare(strict_types=1);

namespace Amasty\Coupons\Model\Coupon;

class ApplyCouponsToCart implements \Amasty\Coupons\Api\ApplyCouponsToCartInterface
{
    /**
     * @var \Magento\Quote\Model\QuoteRepository
     */
    private $quoteRepository;

    /**
     * @var \Amasty\Coupons\Api\Data\CouponApplyResultInterfaceFactory
     */
    private $couponResultFactory;

    /**
     * @var \Magento\Quote\Api\CouponManagementInterface
     */
    private $couponManagement;

    /**
     * @var \Amasty\Coupons\Model\CouponRenderer
     */
    private $couponRenderer;

    public function __construct(
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
        \Amasty\Coupons\Api\Data\CouponApplyResultInterfaceFactory $couponResultFactory,
        \Magento\Quote\Api\CouponManagementInterface $couponManagement,
        \Amasty\Coupons\Model\CouponRenderer $couponRenderer
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->couponResultFactory = $couponResultFactory;
        $this->couponManagement = $couponManagement;
        $this->couponRenderer = $couponRenderer;
    }

    /**
     * Try to apply list of coupons.
     * Return lists of applied and failed coupons.
     *
     * @param int $cartId The cart ID.
     * @param string[] $couponCodes The coupon code data.
     *
     * @return \Amasty\Coupons\Api\Data\CouponApplyResultInterface[]
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function apply(int $cartId, array $couponCodes): array
    {
        $couponCodes = $this->filterCoupons($couponCodes);
        $quote = $this->quoteRepository->getActive($cartId);
        try {
            $this->couponManagement->set($cartId, implode(',', $couponCodes));
        } catch (\Magento\Framework\Exception\NoSuchEntityException $exception) {
            if (!$quote->getItemsCount() || !$quote->getStoreId()) {
                throw $exception;
            }
        }

        $appliedCodes = $this->couponRenderer->render($quote->getCouponCode());

        $result = [];
        foreach ($couponCodes as $code) {
            $couponKey = $this->couponRenderer->findCouponInArray($code, $appliedCodes);
            $isApplied = false;
            if ($couponKey !== false) {
                $code = $appliedCodes[$couponKey];
                $isApplied = true;
            }

            $result[] = $this->couponResultFactory->create(
                ['isApplied' => $isApplied, 'code' => $code]
            );
        }

        return $result;
    }

    /**
     * @param array $couponCodes
     *
     * @return array
     */
    private function filterCoupons(array $couponCodes): array
    {
        $inputCoupons = [];

        foreach ($couponCodes as $code) {
            if ($this->couponRenderer->findCouponInArray($code, $inputCoupons) === false) {
                $inputCoupons[] = $code;
            }
        }

        return $inputCoupons;
    }
}
