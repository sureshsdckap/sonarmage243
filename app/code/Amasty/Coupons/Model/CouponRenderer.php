<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Coupons
 */


declare(strict_types=1);

namespace Amasty\Coupons\Model;

use Amasty\Coupons\Model\Config;

/**
 * Coupon codes processor
 */
class CouponRenderer
{
    /**
     * @var Config
     */
    private $configProvider;

    /**
     * Cache storage for parsed coupons from config
     *
     * @var null|string[]
     */
    private $uniqueCoupons = null;

    public function __construct(Config $configProvider)
    {
        $this->configProvider = $configProvider;
    }

    /**
     * @param string|null $couponString
     *
     * @return string[]
     */
    public function parseCoupon(?string $couponString): array
    {
        if (!$couponString) {
            return [];
        }

        $coupons = array_unique(explode(',', $couponString));
        $result = [];
        foreach ($coupons as &$coupon) {
            $coupon = trim($coupon);
            if ($coupon && $this->findCouponInArray($coupon, $result) === false) {
                $result[] = $coupon;
            }
        }

        return $result;
    }

    /**
     * @param string $couponString
     *
     * @return string[]
     */
    public function render(string $couponString): array
    {
        $coupons = $this->parseCoupon($couponString);

        return $this->filterUniqueCoupons($coupons);
    }

    /**
     * If unique coupon exist in input array,
     * then output should be only with the unique coupon
     *
     * @param array $coupons
     *
     * @return array
     */
    public function filterUniqueCoupons(array $coupons): array
    {
        $uniqueCoupon = null;
        foreach ($coupons as $userCoupon) {
            if ($this->isCouponUnique($userCoupon)) {
                $uniqueCoupon = $userCoupon;
            }
        }

        if ($uniqueCoupon !== null) {
            return [$uniqueCoupon];
        }

        return $coupons;
    }

    /**
     * @param string $coupon
     *
     * @return bool
     */
    public function isCouponUnique(string $coupon): bool
    {
        return $this->findCouponInArray($coupon, $this->getUniqueCoupons()) !== false;
    }

    /**
     * @param string|null $coupon
     * @param array|null $couponArray
     *
     * @return false|int
     */
    public function findCouponInArray(?string $coupon, ?array $couponArray)
    {
        if (!is_array($couponArray) || !$coupon) {
            return false;
        }
        foreach ($couponArray as $key => $code) {
            if (strcasecmp($coupon, $code) === 0) {
                return $key;
            }
        }

        return false;
    }

    /**
     * Get configured unique coupons.
     *
     * @return array
     */
    public function getUniqueCoupons(): array
    {
        if ($this->uniqueCoupons === null) {
            $this->uniqueCoupons = $this->parseCoupon($this->configProvider->getUniqueCoupons());
        }

        return $this->uniqueCoupons;
    }
}
