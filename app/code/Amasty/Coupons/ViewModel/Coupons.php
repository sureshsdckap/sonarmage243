<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Coupons
 */


declare(strict_types=1);

namespace Amasty\Coupons\ViewModel;

use Amasty\Coupons\Api\GetCouponsByCartIdInterface;
use Magento\Checkout\Model\CompositeConfigProvider;
use Magento\Checkout\Model\Session;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class Coupons implements ArgumentInterface
{
    /**
     * @var GetCouponsByCartIdInterface
     */
    private $getCouponsByCartId;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var CompositeConfigProvider
     */
    private $configProvider;

    public function __construct(
        GetCouponsByCartIdInterface $getCouponsByCartId,
        Session $session,
        CompositeConfigProvider $configProvider
    ) {
        $this->getCouponsByCartId = $getCouponsByCartId;
        $this->session = $session;
        $this->configProvider = $configProvider;
    }

    /**
     * @return array
     */
    public function getCurrentCoupons(): array
    {
        return $this->getCouponsByCartId->get((int)$this->session->getQuoteId());
    }

    /**
     * @return bool
     */
    public function isCouponsSet(): bool
    {
        return !empty($this->getCurrentCoupons());
    }
}
