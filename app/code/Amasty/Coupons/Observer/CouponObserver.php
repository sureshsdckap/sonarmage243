<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Coupons
 */


declare(strict_types=1);

namespace Amasty\Coupons\Observer;

use Amasty\Coupons\Model\CouponRenderer;
use Amasty\Coupons\Model\DiscountCollector;
use Amasty\Coupons\Model\IsAllowSameRuleCouponResolver;
use Amasty\Coupons\Model\SalesRule\CouponListProvider;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote\Address;
use Magento\SalesRule\Model\Rule as SalesRule;
use Magento\SalesRule\Model\Rule\Action\Discount\Data as DiscountData;

/**
 * Class CouponObserver for salesrule_validator_process event
 */
class CouponObserver implements ObserverInterface
{
    /**
     * @var DiscountCollector
     */
    protected $discountCollector;

    /**
     * @var CouponRenderer
     */
    private $couponRenderer;

    /**
     * @var IsAllowSameRuleCouponResolver
     */
    private $isAllowSameRuleCouponResolver;

    /**
     * @var CouponListProvider
     */
    private $couponListProvider;

    public function __construct(
        DiscountCollector $discountCollector,
        CouponRenderer $couponRenderer,
        IsAllowSameRuleCouponResolver $isAllowSameRuleCouponResolver,
        CouponListProvider $couponListProvider
    ) {
        $this->discountCollector = $discountCollector;
        $this->couponRenderer = $couponRenderer;
        $this->isAllowSameRuleCouponResolver = $isAllowSameRuleCouponResolver;
        $this->couponListProvider = $couponListProvider;
    }

    /**
     * event salesrule_validator_process
     *
     * @param Observer $observer
     *
     * @throws NoSuchEntityException
     */
    public function execute(Observer $observer)
    {
        /** @var SalesRule $rule */
        $rule = $observer->getEvent()->getRule();
        if ($rule->getCouponType() == SalesRule::COUPON_TYPE_NO_COUPON) {
            return;
        }

        /** @var DiscountData $discountData */
        $discountData = $observer->getData('result');
        $appliedCodes = $this->couponRenderer->render($observer->getData('quote')->getCouponCode());
        $discount = $baseDiscount = 0;
        $amount = $discountData->getAmount();
        $baseAmount = $discountData->getBaseAmount();
        $couponItems = $this->couponListProvider->getItemsByCodes($appliedCodes);
        foreach ($couponItems as $couponRule) {
            if ($rule->getRuleId() == $couponRule->getRuleId()) {
                $this->discountCollector->applyRuleAmount($couponRule->getCode(), $amount);
                $discount += $amount;
                $baseDiscount += $baseAmount;
            }
        }

        if ($this->isAllowSameRuleCouponResolver->isAllowedForSalesRule($rule)) {
            /** @var Address $address */
            $address = $observer->getAddress();
            $availableShippingDiscountAmount = $discount - $address->getSubtotal();

            if ($availableShippingDiscountAmount > 0) {
                $cartRules = $address->getCartFixedRules();
                $cartRules[$rule->getRuleId()] = $availableShippingDiscountAmount;
                $address->setCartFixedRules($cartRules);
            }

            $discountData->setAmount($discount);
            $discountData->setBaseAmount($baseDiscount);
        }
    }
}
