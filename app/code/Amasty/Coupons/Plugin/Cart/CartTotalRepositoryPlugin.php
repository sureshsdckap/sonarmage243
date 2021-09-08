<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Coupons
 */


declare(strict_types=1);

namespace Amasty\Coupons\Plugin\Cart;

use Amasty\Coupons\Api\Data\DiscountBreakdownLineInterface;
use Amasty\Coupons\Api\Data\DiscountBreakdownLineInterfaceFactory;
use Amasty\Coupons\Model\DiscountCollector;
use Amasty\Coupons\Model\SalesRule\CouponListProvider;
use Magento\Quote\Api\CartTotalRepositoryInterface;
use Magento\Quote\Api\Data\TotalsExtensionFactory;
use Magento\Quote\Api\Data\TotalsInterface;

/**
 * Insert coupons discount breakdown data.
 */
class CartTotalRepositoryPlugin
{
    /**
     * @var TotalsExtensionFactory
     */
    private $totalsExtensionFactory;

    /**
     * @var DiscountCollector
     */
    private $discountRegistry;

    /**
     * @var DiscountBreakdownLineInterfaceFactory
     */
    private $discountBreakdownFactory;

    /**
     * @var CouponListProvider
     */
    private $couponListProvider;

    public function __construct(
        DiscountCollector $discountRegistry,
        TotalsExtensionFactory $totalsExtensionFactory,
        DiscountBreakdownLineInterfaceFactory $discountBreakdownFactory,
        CouponListProvider $couponListProvider
    ) {
        $this->totalsExtensionFactory = $totalsExtensionFactory;
        $this->discountRegistry = $discountRegistry;
        $this->discountBreakdownFactory = $discountBreakdownFactory;
        $this->couponListProvider = $couponListProvider;
    }

    /**
     * Set extension attributes.
     *
     * @param CartTotalRepositoryInterface $subject
     * @param TotalsInterface $quoteTotals
     *
     * @return TotalsInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGet(
        CartTotalRepositoryInterface $subject,
        TotalsInterface $quoteTotals
    ) {
        $couponCodes = $this->discountRegistry->getCouponCodes();
        if (empty($couponCodes)) {
            return $quoteTotals;
        }

        $couponModels = $this->couponListProvider->getItemsByCodes($couponCodes);

        $extensionAttributes = $quoteTotals->getExtensionAttributes();

        if (!$extensionAttributes) {
            $extensionAttributes = $this->totalsExtensionFactory->create();
        }

        $discounts = [];

        foreach ($this->discountRegistry->getRulesWithAmount() as $couponData) {
            $couponModel = $couponModels[$couponData['coupon_code']] ?? null;

            if (!$couponModel) {
                continue;
            }

            $discounts[] = $this->discountBreakdownFactory->create(
                [
                    'data' => [
                        DiscountBreakdownLineInterface::RULE_ID => (int)$couponModel->getRuleId(),
                        DiscountBreakdownLineInterface::RULE_NAME => $couponData['coupon_code'],
                        DiscountBreakdownLineInterface::RULE_AMOUNT => $couponData['coupon_amount']
                    ]
                ]
            );
        }

        $extensionAttributes->setAmcouponDiscountBreakdown($discounts);
        $quoteTotals->setExtensionAttributes($extensionAttributes);

        return $quoteTotals;
    }
}
