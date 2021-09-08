<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Coupons
 */


namespace Amasty\Coupons\Test\Api;

use Magento\TestFramework\TestCase\WebapiAbstract;

class ApplyCouponsToCartTest extends WebapiAbstract
{
    const SERVICE_VERSION = 'V1';
    const SERVICE_NAME = 'amastyCouponsApplyCouponsToCartV1';
    const RESOURCE_PATH = '/V1/carts/';

    /**
     * @var \Magento\TestFramework\ObjectManager
     */
    protected $objectManager;

    protected function setUp(): void
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoApiDataFixture Magento/Checkout/_files/discount_10percent.php
     */
    public function testApply()
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $this->objectManager->create(\Magento\Quote\Model\Quote::class);
        $quote->load('test01', 'reserved_order_id');
        $cartId = (int)$quote->getId();
        if (!$cartId) {
            $this->fail('Failed to load fixture. Quote is undefined.');
        }
        $salesRule = $this->objectManager->create(\Magento\SalesRule\Model\Rule::class);
        $salesRuleId = $this->objectManager->get(\Magento\Framework\Registry::class)
            ->registry('Magento/Checkout/_file/discount_10percent');
        $salesRule->load($salesRuleId);

        $couponCodes = [$salesRule->getPrimaryCoupon()->getCode(), 'invalid_coupon', 'invalid_coupon2'];

        $expected = [
            ['applied' => true, 'code' => $couponCodes[0]],
            ['applied' => false, 'code' => $couponCodes[1]],
            ['applied' => false, 'code' => $couponCodes[2]]
        ];

        $serviceInfo = [
            'rest' => [
                'resourcePath' => self::RESOURCE_PATH . $cartId . '/multicoupons/apply/' ,
                'httpMethod' => \Magento\Framework\Webapi\Rest\Request::HTTP_METHOD_POST,
            ],
            'soap' => [
                'service' => self::SERVICE_NAME,
                'serviceVersion' => self::SERVICE_VERSION,
                'operation' => self::SERVICE_NAME . 'Apply',
            ],
        ];

        $requestData = ['cartId' => $cartId, 'couponCodes' => $couponCodes];
        $this->assertSame($expected, $this->_webApiCall($serviceInfo, $requestData));
    }
}
