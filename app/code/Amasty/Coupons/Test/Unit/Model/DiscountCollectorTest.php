<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Coupons
 */


namespace Amasty\Coupons\Test\Unit\Model;

use Amasty\Coupons\Test\Unit\Traits;
use Amasty\Coupons\Model\DiscountCollector;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class DiscountCollector
 *
 * @see DiscountCollector
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * phpcs:ignoreFile
 */
class DiscountCollectorTest extends \PHPUnit\Framework\TestCase
{
    use Traits\ObjectManagerTrait;
    use Traits\ReflectionTrait;

    /**
     * @var DiscountCollector|MockObject
     */
    private $discountCollector;

    public function setUp(): void
    {
        $dataPersistor = $this->createMock(
            \Magento\Framework\App\Request\DataPersistorInterface::class
        );

        $this->discountCollector = $this->getObjectManager()->getObject(
            DiscountCollector::class,
            ['dataPersistor' => $dataPersistor]
        );
    }

    /**
     * @covers DiscountCollector::applyRuleAmount
     */
    public function testApplyRuleAmount()
    {
        $ruleCode = 'test';
        $amount = 5;

        $this->discountCollector->applyRuleAmount($ruleCode, $amount);
        $amountProp = $this->getProperty($this->discountCollector, 'amount');
        $this->assertEquals($amount, $amountProp[$ruleCode]);

        $amount2 = $amount + 5;

        $this->discountCollector->applyRuleAmount($ruleCode, $amount2);
        $amountProp = $this->getProperty($this->discountCollector, 'amount');
        $this->assertEquals($amount + $amount2, $amountProp[$ruleCode]);
    }

    /**
     * @covers DiscountCollector::flushAmount
     */
    public function testFlushAmount()
    {
        $this->initAmount(5);

        $this->discountCollector->flushAmount();
        $amountProp = $this->getProperty($this->discountCollector, 'amount');

        $this->assertEquals([], $amountProp);
    }


    /**
     * @covers DiscountCollector::getRulesWithAmount
     * @dataProvider getRulesWithAmountDataProvider
     */
    public function testGetRulesWithAmount($value, $expected)
    {
        if ($value) {
            $this->initAmount($value);
            $this->initStoreManager($value);

        }
        $result = $this->discountCollector->getRulesWithAmount();

        $this->assertEquals($expected, $result);
    }

    /**
     * Init amount property
     */
    public function initAmount($value)
    {
        $amount = [
            'test' => $value
        ];

        $this->setProperty(
            $this->discountCollector,
            'amount',
            $amount,
            DiscountCollector::class
        );
    }

    /**
     * Init storeManager property
     */
    public function initStoreManager($value)
    {
        $currency = $this->createMock(\Magento\Directory\Model\Currency::class);
        $currency->expects($this->once())->method('format')
            ->with($value, [], false)
            ->willReturn($value);

        $store = $this->createMock(\Magento\Store\Model\Store::class);
        $store->expects($this->any())->method('getCurrentCurrency')
            ->willReturn($currency);

        $storeManager = $this->createMock(\Magento\Store\Model\StoreManager::class);
        $storeManager->expects($this->any())->method('getStore')
            ->willReturn($store);

        $this->setProperty(
            $this->discountCollector,
            'storeManager',
            $storeManager,
            DiscountCollector::class
        );
    }

    /**
     * Data Provider for getRulesWithAmount test
     * @return array
     */
    public function getRulesWithAmountDataProvider()
    {
        return [
            [5, [['coupon_amount' => '-5', 'coupon_code' => 'test']]],
            [null, []]
        ];
    }
}
