<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Coupons
 */


declare(strict_types=1);

namespace Amasty\Coupons\Model;

use Amasty\Coupons\Api\Data\RuleInterface;

/**
 * Resolve rule configuration "Allow Several Coupons from the Same Rule".
 * Configuration can be set for rule, or globally by system config.
 */
class IsAllowSameRuleCouponResolver
{
    /**
     * @var RuleResolver
     */
    private $ruleResolver;

    /**
     * @var Config
     */
    private $config;

    public function __construct(RuleResolver $ruleResolver, Config $config)
    {
        $this->ruleResolver = $ruleResolver;
        $this->config = $config;
    }

    /**
     * @param \Magento\SalesRule\Model\Rule $salesRule
     *
     * @return bool
     */
    public function isAllowedForSalesRule(\Magento\SalesRule\Model\Rule $salesRule): bool
    {
        $couponRule = $this->ruleResolver->getCouponRule($salesRule);

        return $this->isAllowedForCoupon($couponRule);
    }

    /**
     * @param RuleInterface|null $couponRule
     *
     * @return bool
     */
    public function isAllowedForCoupon(?RuleInterface $couponRule): bool
    {
        if ($couponRule === null || $couponRule->getUseConfigValue()) {
            return $this->config->isAllowCouponsSameRule();
        }

        return (bool)$couponRule->getAllowCouponsSameRule();
    }
}
