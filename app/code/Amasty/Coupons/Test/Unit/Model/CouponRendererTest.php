<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Coupons
 */


namespace Amasty\Coupons\Test\Unit\Model;

use Amasty\Coupons\Test\Unit\Traits;
use Amasty\Coupons\Model\CouponRenderer;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class CouponRenderer
 *
 * @see CouponRenderer
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * phpcs:ignoreFile
 */
class CouponRendererTest extends \PHPUnit\Framework\TestCase
{
    use Traits\ObjectManagerTrait;
    use Traits\ReflectionTrait;

    const COUPON_STRING = 'test1,test2';

    const TEST_ARRAY_KEY = 'test_key';

    /**
     * @var CouponRenderer|MockObject
     */
    private $couponRenderer;

    public function setUp(): void
    {
        $this->couponRenderer = $this->createPartialMock(
            CouponRenderer::class,
            ['getUniqueCoupons']
        );
    }

    /**
     * @covers CouponRenderer::render
     * @dataProvider renderDataProvider
     */
    public function testRender($couponString, $uniqueCoupons, $expected)
    {
        $this->couponRenderer->expects($this->any())->method('getUniqueCoupons')
            ->willReturn($uniqueCoupons);

        $result = $this->couponRenderer->render($couponString);
        $this->assertEquals($expected, $result);
    }

    /**
     * @covers CouponRenderer::parseCoupon
     * @dataProvider parseCouponDataProvider
     */
    public function testParseCoupon($couponString, $expected)
    {
        $result = $this->couponRenderer->parseCoupon($couponString);

        $this->assertEquals($expected, $result);
    }

    /**
     * @covers CouponRenderer::findCouponInArray
     * @dataProvider findCouponDataProvider
     */
    public function testFindCouponInArray($coupon, $couponArray, $expected)
    {
        $result = $this->couponRenderer->findCouponInArray($coupon, $couponArray);

        $this->assertEquals($expected, $result);
    }

    /**
     * Data Provider for findCouponInArray test
     * @return array
     */
    public function findCouponDataProvider()
    {
        return [
            ['test', null, false],
            ['test', [], false],
            ['test', [self::TEST_ARRAY_KEY => 'test2'], false],
            ['test', [self::TEST_ARRAY_KEY => 'test'], self::TEST_ARRAY_KEY]
        ];
    }

    /**
     * Data Provider for parseCoupon test
     * @return array
     */
    public function parseCouponDataProvider()
    {
        return [
            ['', []],
            [null, []],
            [self::COUPON_STRING, ['test1', 'test2']],
            ['test1, ,test2', ['test1', 'test2']]
        ];
    }

    public function renderDataProvider()
    {
        return [
            ['', [], []],
            [self::COUPON_STRING, [], ['test1', 'test2']],
            [self::COUPON_STRING, ['test2'], ['test2']]
        ];
    }
}
