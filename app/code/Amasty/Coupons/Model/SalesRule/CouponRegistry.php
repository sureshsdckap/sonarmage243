<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Coupons
 */


declare(strict_types=1);

namespace Amasty\Coupons\Model\SalesRule;

use Magento\SalesRule\Api\Data\CouponInterface;

/**
 * Coupons items registry.
 * For local cache purposes - reduce quantity of requests to DB.
 */
class CouponRegistry
{
    /**
     * Null value is for checked but not exist coupons.
     *
     * @var CouponInterface[]|null[]
     */
    private $storageByCode = [];

    /**
     * Is coupon cached.
     *
     * @param string $coupon
     *
     * @return bool
     */
    public function isCouponSet(string $coupon): bool
    {
        return key_exists(strtoupper($coupon), $this->storageByCode);
    }

    /**
     * @param string $coupon
     *
     * @return CouponInterface|null
     */
    public function getByCouponCode(string $coupon): ?CouponInterface
    {
        return $this->storageByCode[strtoupper($coupon)] ?? null;
    }

    /**
     * @param string $code
     * @param CouponInterface|null $coupon
     */
    public function register(string $code, ?CouponInterface $coupon): void
    {
        $this->storageByCode[strtoupper($code)] = $coupon;
    }
}
