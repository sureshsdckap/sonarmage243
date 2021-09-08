<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Coupons
 */


namespace Amasty\Coupons\Model;

/**
 * Coupon code storage per quote for proper filtering by applied rules
 * @see \Amasty\Coupons\Plugin\SalesRule\Model\RulesApplierPlugin::afterSetAppliedRuleIds
 */
class QuoteCouponStorage
{
    /**
     * @var string[]
     */
    private $storage = [];

    /**
     * @param int $quoteId
     * @param string|null $coupons
     */
    public function setQuoteCoupons(int $quoteId, ?string $coupons): void
    {
        $this->storage[$quoteId] = $coupons;
    }

    /**
     * @param int $quoteId
     *
     * @return string|null
     */
    public function getForQuote(int $quoteId): ?string
    {
        return $this->storage[$quoteId] ?? null;
    }
}
