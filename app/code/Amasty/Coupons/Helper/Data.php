<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Coupons
 */


namespace Amasty\Coupons\Helper;

use Magento\Backend\Model\Session\Quote;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;

/**
 * @deprecated 2.0.0 @see \Amasty\Coupons\Api\GetCouponsByCartIdInterface::get
 */
class Data
{
    /**
     * @var CheckoutSession
     */
    private $session;

    /**
     * @var Quote
     */
    private $backendSession;

    /**
     * @var State
     */
    private $state;

    /**
     * @var \Amasty\Coupons\Api\GetCouponsByCartIdInterface
     */
    private $getCouponsByCartId;

    public function __construct(
        CheckoutSession $session,
        Quote $backendSession,
        State $state,
        \Amasty\Coupons\Api\GetCouponsByCartIdInterface $getCouponsByCartId
    ) {
        $this->session = $session;
        $this->backendSession = $backendSession;
        $this->state = $state;
        $this->getCouponsByCartId = $getCouponsByCartId;
    }

    /**
     * @param bool $isRuleApplied
     * @param Address|null $address
     *
     * @return array|bool
     * @deprecated
     * @see \Amasty\Coupons\Api\GetCouponsByCartIdInterface::get
     */
    public function getRealAppliedCodes($isRuleApplied = false, $address = null)
    {
        if ($address) {
            $quote = $address->getQuote();
        } else {
            $quote = $this->state->getAreaCode() === Area::AREA_ADMINHTML
                ? $this->backendSession->getQuote()
                : $this->session->getQuote();
        }

        return $this->getCouponsByCartId->get((int)$quote->getId());
    }
}
