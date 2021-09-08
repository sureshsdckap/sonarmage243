<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Coupons
 */


declare(strict_types=1);

namespace Amasty\Coupons\Plugin\SalesRule\Model;

use Amasty\Coupons\Model\CouponRenderer;
use Amasty\Coupons\Model\QuoteCouponStorage;
use Amasty\Coupons\Model\SalesRule\CouponListProvider;
use Amasty\Coupons\Model\SalesRule\FilterCoupons;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\SalesRule\Model\Rule;
use Magento\SalesRule\Model\RulesApplier;

class RulesApplierPlugin
{
    /**
     * @var CouponRenderer
     */
    private $couponRender;

    /**
     * @var FilterCoupons
     */
    private $filterCoupons;

    /**
     * @var QuoteCouponStorage
     */
    private $quoteCouponStorage;

    /**
     * @var CouponListProvider
     */
    private $couponListProvider;

    public function __construct(
        CouponRenderer $couponRender,
        FilterCoupons $filterCoupons,
        QuoteCouponStorage $quoteCouponStorage,
        CouponListProvider $couponListProvider
    ) {
        $this->couponRender = $couponRender;
        $this->filterCoupons = $filterCoupons;
        $this->quoteCouponStorage = $quoteCouponStorage;
        $this->couponListProvider = $couponListProvider;
    }

    /**
     * Filter coupon codes by applied rules.
     *
     * Can be executed a few times per request. That is why coupon storage used.
     *
     * @param RulesApplier $subject
     * @param RulesApplier $result
     * @param AbstractItem $item
     * @param int[] $appliedRuleIds
     *
     * @return RulesApplier
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSetAppliedRuleIds(RulesApplier $subject, $result, AbstractItem $item)
    {
        $quote = $item->getQuote();

        $couponsString = $this->quoteCouponStorage->getForQuote((int)$quote->getId());
        if (!$couponsString || !$quote->getAppliedRuleIds()) {
            return $result;
        }

        $coupons = $this->couponRender->parseCoupon($couponsString);

        if (empty($coupons)) {
            return $result;
        }

        $appliedRuleIds = array_map('intval', explode(',', $quote->getAppliedRuleIds()));

        $coupons = $this->filterCoupons->filterCouponsByRuleIds($coupons, $appliedRuleIds);
        $coupons = $this->couponRender->filterUniqueCoupons($coupons);

        $quote->setCouponCode(implode(',', $coupons));

        return $result;
    }

    /**
     * @param RulesApplier $subject
     * @param Address $address
     * @param Rule $rule
     * @param string|null $couponCodesString
     *
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeMaintainAddressCouponCode(
        RulesApplier $subject,
        $address,
        $rule,
        $couponCodesString
    ) {
        $couponRules = [];
        if ($rule->getCouponType() != Rule::COUPON_TYPE_NO_COUPON) {
            $couponCodes = $this->couponRender->parseCoupon($address->getQuote()->getCouponCode());
            foreach ($this->couponListProvider->getItemsByCodes($couponCodes) as $coupon) {
                if ($coupon->getRuleId() == $rule->getRuleId()) {
                    $couponRules[] = $coupon->getCode();
                }
            }
        }

        $couponCodesString = implode(',', $couponRules);

        return [$address, $rule, $couponCodesString];
    }
}
