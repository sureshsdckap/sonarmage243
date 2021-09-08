<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Coupons
 */


declare(strict_types=1);

namespace Amasty\Coupons\Plugin\Sales\Model\AdminOrder;

use Amasty\Coupons\Model\CouponRenderer;
use Amasty\Coupons\Model\QuoteCouponStorage;
use Amasty\Coupons\Model\SalesRule\FilterCoupons;
use Magento\Sales\Model\AdminOrder\Create;

/**
 * Validate coupons before apply.
 * Plugin scope = adminhtml
 */
class CreatePlugin
{
    /**
     * @var CouponRenderer
     */
    private $couponRenderer;

    /**
     * @var FilterCoupons
     */
    private $filterCoupons;

    /**
     * @var QuoteCouponStorage
     */
    private $quoteCouponStorage;

    public function __construct(
        CouponRenderer $couponRenderer,
        FilterCoupons $filterCoupons,
        QuoteCouponStorage $quoteCouponStorage
    ) {
        $this->couponRenderer = $couponRenderer;
        $this->filterCoupons = $filterCoupons;
        $this->quoteCouponStorage = $quoteCouponStorage;
    }

    /**
     * @param Create $subject
     * @param string $code
     *
     * @return string[]
     */
    public function beforeApplyCoupon(Create $subject, string $code)
    {
        $parsedCoupon = $this->couponRenderer->render($code);
        $quote = $subject->getQuote();

        $code = implode(
            ',',
            $this->filterCoupons->validationFilter($parsedCoupon, (int)$quote->getCustomerId())
        );
        $this->quoteCouponStorage->setQuoteCoupons((int)$quote->getid(), $code);

        return [$code];
    }
}
