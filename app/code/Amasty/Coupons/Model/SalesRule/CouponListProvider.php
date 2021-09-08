<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Coupons
 */


declare(strict_types=1);

namespace Amasty\Coupons\Model\SalesRule;

use Magento\SalesRule\Model\ResourceModel\Coupon\CollectionFactory;

class CouponListProvider
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var CouponRegistry
     */
    private $registry;

    public function __construct(CollectionFactory $collectionFactory, CouponRegistry $registry)
    {
        $this->collectionFactory = $collectionFactory;
        $this->registry = $registry;
    }

    /**
     * @param array $coupons
     *
     * @return \Magento\SalesRule\Model\Coupon[]
     */
    public function getItemsByCodes(array $coupons): array
    {
        $result = $queryCodes = [];
        foreach ($coupons as $code) {
            if (!$this->registry->isCouponSet($code)) {
                $queryCodes[] = $code;
            }
        }

        if (!empty($queryCodes)) {
            $this->loadItemsByCodes($queryCodes);
        }

        foreach ($coupons as $code) {
            $coupon = $this->registry->getByCouponCode($code);

            if ($coupon) {
                $result[$code] = $coupon;
            }
        }

        return $result;
    }

    /**
     * Load coupon items and set to registry.
     *
     * @param array $coupons
     */
    private function loadItemsByCodes(array $coupons): void
    {
        $collection = $this->collectionFactory->create()
            ->addFieldToFilter(\Magento\SalesRule\Model\Coupon::KEY_CODE, ['in' => $coupons]);

        foreach ($coupons as $code) {
            $this->registry->register($code, null);
        }

        /** @var \Magento\SalesRule\Model\Coupon $coupon */
        foreach ($collection->getItems() as $coupon) {
            $this->registry->register($coupon->getCode(), $coupon);
        }
    }
}
