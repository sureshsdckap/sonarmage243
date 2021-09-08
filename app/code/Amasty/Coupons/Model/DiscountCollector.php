<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Coupons
 */


declare(strict_types=1);

namespace Amasty\Coupons\Model;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Discount amount registry
 */
class DiscountCollector
{
    /**
     * Key for DataPersistor.
     */
    const DISCOUNT_REGISTRY_DATA = 'amasty_coupons_discount_registry_data';

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var array
     */
    protected $amount = [];

    /**
     * @var DataPersistorInterface
     */
    private $dataPersistor;

    public function __construct(
        StoreManagerInterface $storeManager,
        DataPersistorInterface $dataPersistor
    ) {
        $this->storeManager = $storeManager;
        $this->dataPersistor = $dataPersistor;
    }

    /**
     * Collect amount of discount for each rule
     *
     * @param string $ruleCode
     * @param float|int $amount
     */
    public function applyRuleAmount($ruleCode, $amount)
    {
        if (!isset($this->amount[$ruleCode])) {
            $this->amount[$ruleCode] = 0;
        }

        $this->amount[$ruleCode] += $amount;

        $this->dataPersistor->set(self::DISCOUNT_REGISTRY_DATA, $this->amount);
    }

    /**
     * Return amount of discount for each rule
     *
     * @return array
     */
    public function getRulesWithAmount(): array
    {
        if (empty($this->amount)) {
            $this->restoreDataForBreakdown();
        }
        $totalAmount = [];
        foreach ($this->amount as $ruleCode => $ruleAmount) {
            $totalAmount[] = [
                'coupon_code' => (string)$ruleCode,
                'coupon_amount' =>
                    '-' . $this->storeManager->getStore()->getCurrentCurrency()->format($ruleAmount, [], false)
            ];
        }

        return $totalAmount;
    }

    /**
     * Delete stored data
     */
    public function flushAmount()
    {
        $this->amount = [];
        $this->dataPersistor->clear(self::DISCOUNT_REGISTRY_DATA);
    }

    /**
     * @return string[]
     */
    public function getCouponCodes()
    {
        if (empty($this->amount)) {
            $this->restoreDataForBreakdown();
        }

        $couponCodes = array_keys($this->amount);

        // fix coupon values like 1234
        foreach ($couponCodes as &$couponCode) {
            $couponCode = (string)$couponCode;
        }

        return $couponCodes;
    }

    /**
     * Restore calculated data for breakdown.
     * Return true if discountDataForBreakdown was set.
     *
     * @return bool
     */
    public function restoreDataForBreakdown(): bool
    {
        if (!$this->amount) {
            $this->amount = $this->dataPersistor->get(self::DISCOUNT_REGISTRY_DATA) ? : [];
        }

        return !empty($this->amount);
    }
}
