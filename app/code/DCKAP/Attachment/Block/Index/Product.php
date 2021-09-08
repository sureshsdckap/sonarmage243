<?php

namespace Dckap\Attachment\Block\Index;

class Product extends \Magento\Framework\View\Element\Template
{
    protected $registry;
    protected $productFactory;
    protected $pdfattachmentFactory;
    protected $pdfsectionFactory;
    protected $storeManager;

    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Dckap\Attachment\Model\PdfattachmentFactory $pdfattachmentFactory,
        \Dckap\Attachment\Model\PdfsectionFactory $pdfsectionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->registry = $registry;
        $this->productFactory = $productFactory;
        $this->pdfattachmentFactory = $pdfattachmentFactory;
        $this->pdfsectionFactory = $pdfsectionFactory;
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    protected function _prepareLayout()
    {
        return parent::_prepareLayout();
    }

    public function getPdfSections()
    {
        $sections = [];
        $collections = $this->pdfsectionFactory->create()->getCollection();
        if ($collections && !empty($collections)) {
            foreach ($collections as $item) {
                $sections[] = $item->getData();
            }
        }
        return $sections;
    }

    public function getAttachments()
    {
        $attachments = [];
        $product = $this->registry->registry('current_product');
        $sku = $product->getData('sku');
        $collections = $this->pdfattachmentFactory->create()->getCollection()
            ->addFieldToFilter('sku', ['eq' => $sku]);
        if ($collections && !empty($collections)) {
            foreach ($collections as $item) {
                $attachments[$item->getData('section_id')][] = $item->getData();
//                $attachments[] = $item->getData();
            }
        }
        return $attachments;
    }

    public function getMediaUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
    }

    public function getCurrentProduct()
    {
        return $this->registry->registry('current_product');
    }
}
