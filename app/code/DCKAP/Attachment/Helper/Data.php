<?php

namespace Dckap\Attachment\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Data extends AbstractHelper
{
    protected $pdfsectionFactory;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Dckap\Attachment\Model\PdfsectionFactory $pdfsectionFactory
    ) {
        $this->pdfsectionFactory = $pdfsectionFactory;
        parent::__construct($context);
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

    public function getSectionOptionArray()
    {
        $sections = [];
        $collections = $this->pdfsectionFactory->create()->getCollection();
        if ($collections && !empty($collections)) {
            foreach ($collections as $item) {
                $sections[$item->getId()] = $item->getSectionName();
            }
        }
        return $sections;
    }
}
