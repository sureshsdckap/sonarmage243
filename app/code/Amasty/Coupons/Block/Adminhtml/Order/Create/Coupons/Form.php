<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Coupons
 */


declare(strict_types=1);

namespace Amasty\Coupons\Block\Adminhtml\Order\Create\Coupons;

use Amasty\Coupons\Api\GetCouponsByCartIdInterface;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Model\Session\Quote;
use Magento\Sales\Model\AdminOrder\Create;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Admin block for order create
 */
class Form extends \Magento\Sales\Block\Adminhtml\Order\Create\Coupons\Form
{
    /**
     * @var GetCouponsByCartIdInterface
     */
    private $getCouponsByCartId;

    public function __construct(
        Context $context,
        Quote $sessionQuote,
        Create $orderCreate,
        PriceCurrencyInterface $priceCurrency,
        GetCouponsByCartIdInterface $getCouponsByCartId,
        array $data = []
    ) {
        parent::__construct($context, $sessionQuote, $orderCreate, $priceCurrency, $data);
        $this->getCouponsByCartId = $getCouponsByCartId;
    }

    /**
     * @return string[]
     */
    public function getCouponsCodes(): array
    {
        return $this->getCouponsByCartId->get((int)$this->getQuote()->getId());
    }

    /**
     * return onclick button js code
     *
     * @return string
     */
    public function getCouponJs(): string
    {
        $couponsString = '';
        $couponsCodes = implode(',', $this->getCouponsCodes());

        if ($couponsCodes) {
            $couponsString = '\'' . $couponsCodes . ',\' + ';
        }
        $couponsString .= '($F(\'coupons:code\')).split(\',\').map(function (i) {return i.trim()}).join(\',\')';

        return 'order.applyCoupon(' . $couponsString . ')';
    }

    /**
     * Create button and return its html
     *
     * @param string $label
     * @param string $onclick
     * @param string $class
     * @param string $buttonId
     * @param array $dataAttr
     * @return string
     */
    public function getButtonHtml($label, $onclick, $class = '', $buttonId = null, $dataAttr = [])
    {
        return $this->getLayout()->createBlock(
            \Amasty\Coupons\Block\Adminhtml\FormButton::class
        )->setData(
            ['label' => $label, 'onclick' => $onclick, 'class' => $class, 'type' => 'button', 'id' => $buttonId]
        )->setDataAttribute(
            $dataAttr
        )->toHtml();
    }
}
