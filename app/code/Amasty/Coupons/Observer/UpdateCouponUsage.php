<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Coupons
 */


declare(strict_types=1);

namespace Amasty\Coupons\Observer;

use Amasty\Coupons\Model\CouponRenderer;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Sales\Model\Order;
use Magento\SalesRule\Api\Data\CouponInterface;
use Magento\SalesRule\Model\Coupon as CouponModel;
use Magento\SalesRule\Model\CouponFactory;
use Magento\SalesRule\Model\ResourceModel\Coupon;
use Magento\SalesRule\Model\ResourceModel\Coupon\Usage;

/**
 * events sales_order_place_before|sales_order_place_after
 */
class UpdateCouponUsage implements ObserverInterface
{
    /**
     * @var Coupon
     */
    private $coupon;

    /**
     * @var Usage
     */
    private $couponUsage;

    /**
     * @var CouponRenderer
     */
    private $couponRenderer;

    /**
     * Save used coupon code ID
     *
     * @var
     */
    private $usedCodes = [];

    /**
     * @var CouponFactory
     */
    private $couponFactory;

    /**
     * Number of coupons used
     *
     * @var array
     */
    private $timesUsed = [];

    public function __construct(
        Coupon $coupon,
        Usage $couponUsage,
        CouponRenderer $couponRenderer,
        CouponFactory $couponFactory
    ) {
        $this->coupon = $coupon;
        $this->couponUsage = $couponUsage;
        $this->couponRenderer = $couponRenderer;
        $this->couponFactory = $couponFactory;
    }

    /**
     * events sales_order_place_before|sales_order_place_after
     *
     * @param Observer $observer
     *
     * @return $this
     * @throws AlreadyExistsException
     */
    public function execute(Observer $observer)
    {
        /** @var Order $order */
        $order = $observer->getEvent()->getOrder();
        if (!$order || !$order->getCouponCode()) {
            return $this;
        }
        // if order placement then increment else if order cancel then decrement
        $increment = (bool)$observer->getEventName() !== 'order_cancel_after';
        $placeBefore = $observer->getEvent()->getName() === 'sales_order_place_before';
        $customerId = $order->getCustomerId();
        $coupons = $this->couponRenderer->parseCoupon($order->getCouponCode());
        if (is_array($coupons) && count($coupons) > 1) {
            foreach ($coupons as $coupon) {
                if ($this->isUsed($coupon, $placeBefore)) {
                    continue;
                }

                /** @var CouponModel $couponEntity */
                $couponEntity = $this->couponFactory->create();
                $this->coupon->load($couponEntity, $coupon, 'code');

                if ($couponEntity->getId()) {
                    if (!$placeBefore) {
                        $couponEntity->setTimesUsed($this->getResultTimesUsed($couponEntity) + ($increment ? 1 : -1));
                        $this->coupon->save($couponEntity);
                        if ($customerId) {
                            $this->couponUsage->updateCustomerCouponTimesUsed(
                                $customerId,
                                $couponEntity->getId(),
                                $increment
                            );
                        }
                    } else {
                        $this->timesUsed['coupon_times_used'][$couponEntity->getId()] = $couponEntity->getTimesUsed();
                    }
                }
            }
        }

        return $this;
    }

    /**
     * @param string $code
     * @param bool $placeBefore
     *
     * @return bool
     */
    private function isUsed($code, $placeBefore): bool
    {
        if (!isset($this->usedCodes[$code])) {
            if (!$placeBefore) {
                $this->usedCodes[$code] = 1;
            }
            return false;
        }

        return true;
    }

    /**
     * Magento add value in column 'times_used' in DB. We also add value in column 'times_used'.
     * In this method we override this value on general solution
     *
     * @param CouponInterface|CouponModel $couponEntity
     *
     * @return int
     */
    private function getResultTimesUsed(CouponInterface $couponEntity): int
    {
        $timesUsed = $couponEntity->getTimesUsed();

        if (isset($this->timesUsed['coupon_times_used'][$couponEntity->getId()])
            && ($timesUsed !== $this->timesUsed['coupon_times_used'][$couponEntity->getId()])
        ) {
            $timesUsed = $this->timesUsed['coupon_times_used'][$couponEntity->getId()];
        }

        return (int)$timesUsed;
    }
}
