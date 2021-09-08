<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Dckap\QuickRFQ\Block\Customer;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use \Magento\Framework\App\ObjectManager;
use \Magento\Sales\Model\ResourceModel\Order\CollectionFactoryInterface;
use Magento\Setup\Exception;

/**
 * Sales order history block
 *
 * @api
 * @since 100.0.2
 */
class Index extends \Magento\Framework\View\Element\Template
{
    /**
     * @var string
     */
    protected $_template = 'Dckap_QuickRFQ::orderpad.phtml';

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    protected $productRepository;
    protected $themeHelper;
    protected $clorasHelper;
    protected $clorasDDIHelper;
    protected $shiptoModel;
    protected $collectionFactory;
    protected $_registry;
    protected $imageHelperFactory;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Sales\Model\Order\Config $orderConfig
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Dckap\Theme\Helper\Data $themeHelper,
        \Cloras\Base\Helper\Data $clorasHelper,
        \Cloras\DDI\Helper\Data $clorasDDIHelper,
        \DCKAP\Extension\Model\Shipto $shiptoModel,
        \Magento\Framework\Data\CollectionFactory $collectionFactory,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Helper\ImageFactory $imageHelperFactory,
        array $data = []
    ) {
        $this->_customerSession = $customerSession;
        $this->productRepository = $productRepository;
        $this->themeHelper = $themeHelper;
        $this->clorasHelper = $clorasHelper;
        $this->clorasDDIHelper = $clorasDDIHelper;
        $this->shiptoModel = $shiptoModel;
        $this->collectionFactory = $collectionFactory;
        $this->_registry = $registry;
        $this->imageHelperFactory = $imageHelperFactory;
        parent::__construct($context, $data);
    }

    protected function _construct()
    {
        parent::_construct();
        $this->pageConfig->getTitle()->set(__('Order Pad'));
    }

    public function isDisplayed()
    {
        return $this->themeHelper->getOrderPadView();
    }

    public function getOrderpadItems()
    {
        $orderPadItems = $this->_registry->registry('orderpad_items');
        return $orderPadItems;
    }

    public function getHandle()
    {
        $handle = $this->_registry->registry('handle');
        return $handle;
    }

    public function getShiptoConfig()
    {
        $shiptoConfig = $this->_registry->registry('config');
        return $shiptoConfig;
    }

    public function getShiptoItems()
    {
        $shiptoItems = $this->shiptoModel->toOptionArray();
        if ($shiptoItems && count($shiptoItems)) {
            return $shiptoItems;
        }
        return false;
    }

    public function getProductDetails($sku = false)
    {
        if ($sku) {
            return $this->productRepository->get($sku);
        }
        return false;
    }

    public function getBackUrl()
    {
        return $this->getUrl('customer/account/');
    }

    public function getProductImageUrl($sku)
    {
        $imageUrl = '';
        try {
//            $sku = 'RP12SP';
            $product = $this->productRepository->get($sku);
            if ($product) {
                $imageUrl = $this->imageHelperFactory->create()->init($product, 'product_small_image')->getUrl();
                return $imageUrl;
            }
        } catch (\Exception $e) {
//            return '';
        }
        if ($imageUrl == '') {
            $imageUrl = $this->imageHelperFactory->create()->getDefaultPlaceholderUrl('image');
        }
        return $imageUrl;
    }

    public function getProductUrl($sku)
    {
        $productUrl = "#";
        try {
            $product = $this->productRepository->get($sku);
            if ($product) {
                return $product->getProductUrl();
            }
        } catch (\Exception $e) {
            return "#";
        }
        return $productUrl;
    }
}
