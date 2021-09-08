<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Coupons
 */


declare(strict_types=1);

namespace Amasty\Coupons\ViewModel;

use Magento\Checkout\Model\CompositeConfigProvider;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class CheckoutConfig implements ArgumentInterface
{
    /**
     * @var CompositeConfigProvider
     */
    private $configProvider;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    public function __construct(CompositeConfigProvider $configProvider, SerializerInterface $serializer)
    {
        $this->configProvider = $configProvider;
        $this->serializer = $serializer;
    }

    public function getJsonConfig()
    {
        return $this->serializer->serialize($this->configProvider->getConfig());
    }
}
