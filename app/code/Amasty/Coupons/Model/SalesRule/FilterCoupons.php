<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Coupons
 */


declare(strict_types=1);

namespace Amasty\Coupons\Model\SalesRule;

use Amasty\Coupons\Model\IsAllowSameRuleCouponResolver;
use Amasty\Coupons\Model\ResourceModel\CustomerUsage;
use Magento\Checkout\Helper\Cart;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\SalesRule\Api\Data\CouponInterface;
use Magento\SalesRule\Model\Data\Rule as RuleData;
use Magento\SalesRule\Model\ResourceModel\Rule\Collection as SalesRuleCollection;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory as RuleCollectionFactory;
use Magento\SalesRule\Model\Rule;

/**
 * Coupon codes processor.
 */
class FilterCoupons
{
    /**
     * @var SalesRuleCollection[]
     */
    private $ruleCollectionStorage = [];

    /**
     * @var IsAllowSameRuleCouponResolver
     */
    private $allowSameRuleCouponResolver;

    /**
     * @var RuleCollectionFactory
     */
    private $ruleCollectionFactory;

    /**
     * @var CouponListProvider
     */
    private $couponListProvider;

    /**
     * @var CouponRegistry
     */
    private $registry;

    /**
     * @var CustomerUsage
     */
    private $customerUsage;

    public function __construct(
        IsAllowSameRuleCouponResolver $allowSameRuleCouponResolver,
        RuleCollectionFactory $ruleCollectionFactory,
        CouponListProvider $couponListProvider,
        CouponRegistry $registry,
        CustomerUsage $customerUsage
    ) {
        $this->allowSameRuleCouponResolver = $allowSameRuleCouponResolver;
        $this->ruleCollectionFactory = $ruleCollectionFactory;
        $this->couponListProvider = $couponListProvider;
        $this->registry = $registry;
        $this->customerUsage = $customerUsage;
    }

    /**
     * @param string[] $coupons
     * @param int[] $appliedRuleIds
     *
     * @return string[]
     */
    public function filterCouponsByRuleIds(array $coupons, array $appliedRuleIds): array
    {
        $this->couponListProvider->getItemsByCodes($coupons);

        $validCoupons = [];

        foreach ($coupons as $couponCode) {
            $coupon = $this->registry->getByCouponCode($couponCode);

            if ($coupon !== null && in_array((int)$coupon->getRuleId(), $appliedRuleIds)) {
                $validCoupons[] = $couponCode;
            }
        }

        return $validCoupons;
    }

    /**
     * Filter coupon codes through validation.
     *
     * @param string[] $coupons
     * @param int|null $customerId
     *
     * @return string[] valid coupon codes
     */
    public function validationFilter(array $coupons, ?int $customerId): array
    {
        $this->inputFilter($coupons);

        $couponItems = $this->couponListProvider->getItemsByCodes($coupons);

        $rulesIds = $couponIds = $couponCounters = [];
        foreach ($couponItems as $coupon) {
            $rulesIds[] = (int)$coupon->getRuleId();
            $couponIds[] = (int)$coupon->getId();
        }

        $ruleCollection = $this->getRuleCollection($rulesIds);

        if ($customerId) {
            $couponCounters = $this->customerUsage->getCouponsCounterForCustomer($customerId, $couponIds);
        }

        $appliedCouponRuleIds = $validCoupons = [];

        foreach ($coupons as $couponCode) {
            $coupon = $this->registry->getByCouponCode($couponCode);
            if ($coupon === null || $this->isCouponUsageExceeded($coupon, $couponCounters)) {
                continue;
            }

            $ruleId = (int)$coupon->getRuleId();
            if (!isset($appliedCouponRuleIds[$ruleId])) {
                //apply the coupon if it is the first coupon of the rule
                $validCoupons[] = $coupon->getCode();
                $appliedCouponRuleIds[$ruleId] = true;
                continue;
            }

            /** @var Rule $rule */
            $rule = $ruleCollection->getItemById($coupon->getRuleId());
            if ($this->allowSameRuleCouponResolver->isAllowedForSalesRule($rule)) {
                $validCoupons[] = $coupon->getCode();
            }
        }

        return $validCoupons;
    }

    /**
     * Filter input values from user.
     *
     * @param array $couponCodes
     */
    private function inputFilter(array &$couponCodes)
    {
        foreach ($couponCodes as $key => $couponCode) {
            if (strlen($couponCode) > Cart::COUPON_CODE_MAX_LENGTH) {
                unset($couponCodes[$key]);
            }
        }
    }

    /**
     * Check global coupon usage limit and coupon usage limit by current customer
     *
     * @param CouponInterface $coupon
     * @param array $couponCounters
     *
     * @return bool
     */
    private function isCouponUsageExceeded(CouponInterface $coupon, array &$couponCounters): bool
    {
        return ($coupon->getUsageLimit() && $coupon->getTimesUsed() >= $coupon->getUsageLimit())
            || ($coupon->getUsagePerCustomer()
                && isset($couponCounters[$coupon->getId()])
                && $couponCounters[$coupon->getId()] >= $coupon->getUsagePerCustomer());
    }

    /**
     * @param array $rulesIds
     *
     * @return SalesRuleCollection
     */
    private function getRuleCollection(array $rulesIds): AbstractCollection
    {
        $cacheKey = $this->getCacheKey($rulesIds);
        if (!isset($this->ruleCollectionStorage[$cacheKey])) {
            $this->ruleCollectionStorage[$cacheKey] = $this->ruleCollectionFactory->create()
                ->addFieldToFilter(RuleData::KEY_RULE_ID, ['in' => $rulesIds]);
        }

        return $this->ruleCollectionStorage[$cacheKey];
    }

    /**
     * @param array $keyParts
     *
     * @return int
     */
    private function getCacheKey(array $keyParts): int
    {
        sort($keyParts);

        return crc32(implode('_', $keyParts));
    }
}
