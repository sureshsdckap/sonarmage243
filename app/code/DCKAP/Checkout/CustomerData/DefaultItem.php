<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Dckap\Checkout\CustomerData;

use Magento\Catalog\Model\Product\Configuration\Item\ItemResolverInterface;

class DefaultItem extends \Magento\Checkout\CustomerData\DefaultItem
{

    private $serializer;

    public function __construct(\Magento\Catalog\Helper\Image $imageHelper, \Magento\Msrp\Helper\Data $msrpHelper, \Magento\Framework\UrlInterface $urlBuilder, \Magento\Catalog\Helper\Product\ConfigurationPool $configurationPool, \Magento\Checkout\Helper\Data $checkoutHelper, \Magento\Framework\Escaper $escaper = null, ItemResolverInterface $itemResolver = null, \Magento\Framework\Serialize\Serializer\Json $serializer)
    {
        $this->serializer = $serializer;
        parent::__construct(
            $imageHelper,
            $msrpHelper,
            $urlBuilder,
            $configurationPool,
            $checkoutHelper,
            $escaper,
            $itemResolver
        );
    }

    /**
     * Get item configure url
     *
     * @return string
     */
    protected function getConfigureUrl()
    {
//        return $this->urlBuilder->getUrl('checkout/cart/configure');
        return $this->getProductUrl();
    }

    protected function doGetItemData()
    {
        $result = parent::doGetItemData();
        $result['isCustomizedEnabledQtyBox'] = $this->isQtyBoxEnabled($additionalOptions = $this->item->getOptionByCode('additional_options'));
        return $result;
    }

    protected function isQtyBoxEnabled($additionalOptions)
    {
        $additionalOption = (array)$this->serializer->unserialize($additionalOptions->getValue());
        $price_quote ="";
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/additionalOption.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        if (isset($additionalOption['quote'])) {
            $price_quote = $additionalOption['quote']['value'];
        }
        if ($price_quote) {
            return 1;
        } else {
            return 0;
        }

        $logger->info("uom");
        $logger->info(print_r($price_quote, true));
    }

    /**
     * Retrieve URL to item Product
     *
     * @return string
     */
    protected function getProductUrl()
    {
        if ($this->item->getRedirectUrl()) {
            return $this->item->getRedirectUrl();
        }

        $product = $this->item->getProduct();
        $option = $this->item->getOptionByCode('product_type');
        if ($option) {
            $product = $option->getProduct();
        }

        return $product->getUrlModel()->getUrl($product);
    }
}
