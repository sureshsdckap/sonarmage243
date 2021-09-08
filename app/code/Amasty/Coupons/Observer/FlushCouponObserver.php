<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Coupons
 */


namespace Amasty\Coupons\Observer;

use Magento\Framework\Event\ObserverInterface;
use Amasty\Coupons\Model\DiscountCollector;
use Magento\Framework\Event\Observer;

/**
 * Reset coupon discount registry on event sales_quote_collect_totals_before
 */
class FlushCouponObserver implements ObserverInterface
{
    /**
     * @var DiscountCollector
     */
    protected $discountCollector;

    public function __construct(
        DiscountCollector $discountCollector
    ) {
        $this->discountCollector = $discountCollector;
    }

    /**
     * @param Observer $observer
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(Observer $observer)
    {
        $this->discountCollector->flushAmount();
    }
}
