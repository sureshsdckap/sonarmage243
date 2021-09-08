<?php

namespace DCKAP\Catalog\Controller\Configurable;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\LayoutInterface;

/**
 * Class Get
 * @package DCKAP\Catalog\Controller\Configurable
 */
class Get extends Action
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_resultJsonFactory;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Catalog\Model\Product
     */
    protected $productModel;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var \Cloras\Base\Helper\Data
     */
    protected $clorasHelper;

    /**
     * @var \Cloras\DDI\Helper\Data
     */
    protected $clorasDDIHelper;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Magento\ConfigurableProduct\Model\Product\Type\Configurable
     */
    protected $configurable;

    /**
     * @var \Magento\Framework\Pricing\Helper\Data
     */
    protected $priceHelper;

    /** @var LayoutInterface */
    protected $_layout;

    /**
     * @var \DCKAP\Extension\Helper\Data
     */
    protected $_helperoption;

    /**
     * @var \DCKAP\Catalog\Helper\View
     */
    protected $customHelper;

    /**
     * @var \Magento\Catalog\Helper\Output
     */
    protected $_helper;

    /**
     * Get constructor.
     * @param Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Catalog\Model\Product $productModel
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Cloras\Base\Helper\Data $clorasHelper
     * @param \Cloras\DDI\Helper\Data $clorasDDIHelper
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\ConfigurableProduct\Model\Product\Type\Configurable $configurable
     * @param \Magento\Framework\Pricing\Helper\Data $priceHelper
     * @param LayoutInterface $layout
     * @param \DCKAP\Catalog\Helper\View $customHelper
     * @param \DCKAP\Extension\Helper\Data $helperoption
     * @param \Magento\Catalog\Helper\Output $cataloghelper
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Catalog\Model\Product $productModel,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Cloras\Base\Helper\Data $clorasHelper,
        \Cloras\DDI\Helper\Data $clorasDDIHelper,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\ConfigurableProduct\Model\Product\Type\Configurable $configurable,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        LayoutInterface $layout,
        \DCKAP\Catalog\Helper\View $customHelper,
        \DCKAP\Extension\Helper\Data $helperoption,
        \Magento\Catalog\Helper\Output $cataloghelper
    ) {
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->customerSession = $customerSession;
        $this->productModel = $productModel;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->clorasHelper = $clorasHelper;
        $this->clorasDDIHelper = $clorasDDIHelper;
        $this->productRepository = $productRepository;
        $this->configurable = $configurable;
        $this->priceHelper = $priceHelper;
        $this->customHelper = $customHelper;
        $this->_helperoption = $helperoption;
        $this->_helper = $cataloghelper;
        $this->_layout = $layout;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        $resultJson = $this->_resultJsonFactory->create();
        /**
         * 1. get option, product_id and sku from params
         * 2. Find child product id or sku
         * 3. Get product details from Magento
         * 4. get product details from ERP
         * 5.return product details
         */
        $params = $this->getRequest()->getParams();

        if (isset($params['selected_option']) && $params['selected_option'] != '') {
            $parentProduct = $this->productRepository->getById($params['id']);
            $productTypeInstance = $parentProduct->getTypeInstance();

            $childIds = [];
            $usedProducts = $productTypeInstance->getUsedProducts($parentProduct);
            if ($usedProducts && !empty($usedProducts)) {
                foreach ($usedProducts as $child) {
                    $childIds[] = $child->getId();
                }
            }

            /*$data = $productTypeInstance->getConfigurableOptions($parentProduct);
            foreach($data as $attr){
                foreach($attr as $p){
                    $options[$p['sku']][$p['attribute_code']] = $p['option_title'];
                }
            }*/

            $productAttributeOptions = $this->configurable->getConfigurableAttributesAsArray($parentProduct);
            if ($productAttributeOptions && !empty($productAttributeOptions)) {
                foreach ($productAttributeOptions as $productAttributeOption) {
                    $attrCode = $productAttributeOption['attribute_code'];
                    $selectedOption = $params['selected_option'];
                    $productsCollection = $this->productCollectionFactory->create()
                        ->addAttributeToSelect('*')
                        ->addFieldToFilter($attrCode, $selectedOption)
                        ->addAttributeToFilter('entity_id', ['in' => $childIds]);
                }
            }

            if ($productsCollection && $productsCollection->getSize() > 0) {
                if ($this->customerSession->isLoggedIn()) {
                    $sessionProductData = $this->customerSession->getProductData();
                    list($status, $integrationData) = $this->clorasDDIHelper->isServiceEnabled('price_stock');
                    if ($status) {
                        $skuArr = [];
                        foreach ($productsCollection as $product) {
                            if (!isset($sessionProductData[$product->getSku()])) {
                                $skuArr[] = $product->getSku();
                            }
                        }
                        if (!empty($skuArr)) {
                            $responseData = $this->clorasDDIHelper->getBulkPriceStock($integrationData, $skuArr);
                            $itemData = [];
                            $itemData = $sessionProductData;
                            if ($responseData && !empty($responseData)) {
                                foreach ($responseData as $data) {
                                    $sku = (isset($data['lineItem']['stockNum'])) ? $data['lineItem']['stockNum'] : '';
                                    if ($sku != '') {
                                        $itemData[$sku] = $data;
                                    }
                                }
                            }
                            $sessionProductData = $itemData;
                        }
                    }
                } else {
                    $sessionProductData = $this->customerSession->getGuestProductData();
                    list($status, $integrationData) = $this->clorasDDIHelper->isServiceEnabled('guest_price_stock');
                    if ($status) {
                        $skuArr = [];
                        foreach ($productsCollection as $product) {
                            if (!isset($sessionProductData[$product->getSku()])) {
                                $skuArr[] = $product->getSku();
                            }
                        }
                        if (!empty($skuArr)) {
                            $responseData = $this->clorasDDIHelper->getGuestBulkPriceStock($integrationData, $skuArr);
                            $itemData = [];
                            $itemData = $sessionProductData;
                            if ($responseData && !empty($responseData)) {
                                foreach ($responseData as $data) {
                                    $sku = (isset($data['lineItem']['stockNum'])) ? $data['lineItem']['stockNum'] : '';
                                    if ($sku != '') {
                                        $itemData[$sku] = $data;
                                    }
                                }
                            }
                            $sessionProductData = $itemData;
                        }
                    }
                }
                foreach ($productsCollection as $product) {
                    $groups = $this->customHelper->getAttributeGroups($product->getAttributeSetId());
                    $detailTabTitle = $this->_helperoption->getDetailTabTitle();
                    $description = "";
                    if ($groups && !empty($groups)) {
                        foreach ($groups as $group) {
                            if ($group['attribute_group_code'] == 'productdetail') {
                                $attrs = $this->customHelper->getGroupAttributes($product, $group['attribute_group_id'], $product->getAttributes());
                                if ($attrs && !empty($attrs)) {
                                    $description .= "<div class='additional-attributes-wrapper table-wrapper'>";
                                    $description .= "<table class='data table additional-attributes' id='product-attribute-specs-table'>";
                                    $description .= "<caption class='table-caption'>More Information</caption>";
                                    $description .= "<tbody>";
                                    $description .= "<tr>";

                                    foreach ($attrs as $_data) {
                                        if ($_data->getFrontend()->getValue($product) != "") {
                                            $description .= "<td class='col data' data-th='" . $_data->getFrontendLabel() . "'>";
                                            if ($detailTabTitle) {
                                                $description .= "<h4>" . $_data->getFrontendLabel() . "</h4>";
                                            }
                                            $description .= $_data->getFrontend()->getValue($product);
                                            $description .= "</td>
                                ";
                                        }
                                    }
                                    $description .= "</tr>";
                                    $description .= "</tbody>";
                                    $description .= "</table>";
                                    $description .= "</div>";
                                } else {
                                    $description .= "<div class='additional-attributes-wrapper table-wrapper'>";
                                    $description .= "<p>No details available</p>";
                                    $description .= "</div>";
                                }
                            }
                        }
                    }

                    $resData[$product->getId()]['description'] = $description;

                    $specification = "";
                    $displayAttrs = [];
                    if ($groups && !empty($groups)) {
                        foreach ($groups as $group) {
                            if (($group['attribute_group_code'] == 'productfilters') || ($group['attribute_group_code'] == 'moreinformation')) {
                                $attrs = $this->customHelper->getGroupAttributes($product, $group['attribute_group_id'], $product->getAttributes());
                                foreach ($attrs as $_data) {
                                    $displayAttrs[$_data->getFrontendLabel()] = $_data->getFrontend()->getValue($product);
                                }
                            }
                        }
                        if (!empty($displayAttrs)) {
                            ksort($displayAttrs);
                        }
                    }
                    $specification .= '<div class="additional-attributes-wrapper table-wrapper">';
                    if (!empty($displayAttrs)) {
                        $specification .= '<table class="data table additional-attributes info-table" id="product-attribute-specs-table">';
                        $specification .= '<caption class="table-caption">Specifications</caption>';
                        $specification .= '<tbody>';
                        foreach ($displayAttrs as $attrKey => $attrVal) {
                            if ($attrVal != '') {
                                $specification .= '<tr>';
                                $specification .= '<th class="col label" scope="row">' . $attrKey . '</th>';
                                $specification .= '<td class="col data" data-th="' . $attrKey . '">' . $attrVal . '</td>';
                                $specification .= '</tr>';
                            }
                        }

                        $specification .= '</tbody>
                            </table>';
                    } else {
                        $specification .= '<p>No information available</p>';
                    }
                    $specification .= '</div>';
                    $resData[$product->getId()]['specification'] = $specification;

                    if ($groups && !empty($groups)) {
                        foreach ($groups as $group) {
                            if ($group['attribute_group_code'] == 'attachments') {
                                $attrs = $this->customHelper->getGroupAttributes($product, $group['attribute_group_id'], $product->getAttributes());
                                $attachments = "";

                                if ($attrs && !empty($attrs)) {
                                    $attachments .= '<div class="attachment-tab-content">';
                                    $attachments .= '<div class="additional-attributes-wrapper table-wrapper">';
                                    $attachments .= '<table class="data table additional-attributes" id="product-attribute-specs-table">';
                                    $attachments .= '<caption class="table-caption">More Information</caption>';
                                    $attachments .= '<tbody>';
                                    $attachments .= '<tr>';
                                    foreach ($attrs as $_data) {
                                        $attachments .= '<td class="col label" scope="row">';
                                        $attachments .= '<a href="' . $_data->getFrontend()->getValue($product) . '" target="_blank">' . rtrim($_data->getFrontendLabel(), ' URL') . '</a>';
                                        $attachments .= '</td>';
                                    }
                                    $attachments .= '</tr>';
                                    $attachments .= '</tbody>';
                                    $attachments .= '</table>';
                                    $attachments .= '</div>';
                                    $attachments .= '</div>';
                                } else {
                                    $attachments .= '<div class="additional-attributes-wrapper table-wrapper">';
                                    $attachments .= '<p>No information available</p>';
                                    $attachments .= '</div>';
                                }
                            }
                        }
                        $resData[$product->getId()]['attachments'] = $attachments;
                    }
                }

                foreach ($productsCollection as $product) {
                    $arr = [];
                    $chileProduct = $this->productRepository->getById($product->getId());
                    $m_data = $chileProduct->getData();
                    $resData[$product->getId()]['sku'] = $product->getSku();
                    if (isset($sessionProductData[$product->getSku()]['prices']) && isset($sessionProductData[$product->getSku()]['lineItem'])) {
                        $price = $sessionProductData[$product->getSku()]['prices']['netPrice'];
                        $resData[$product->getId()]['price'] = $this->priceHelper->currency($price, true, false);
//                    $arr['uom'] = $sessionProductData[$product->getSku()]['lineItem']['uom']['uomCode'];
//                    $arr['qty'] = $sessionProductData[$product->getSku()]['lineItem']['totalAvailable'];
                        $resData[$product->getId()]['uom_html'] = '';
                        if (isset($sessionProductData[$product->getSku()]['lineItem']['uom']['uomFactors']) && !empty($sessionProductData[$product->getSku()]['lineItem']['uom']['uomFactors'])) {
                            foreach ($sessionProductData[$product->getSku()]['lineItem']['uom']['uomFactors'] as $uom) {
                                $selected = "";
                                if ($sessionProductData[$product->getSku()]['lineItem']['uom']['uomCode'] == $uom['altUomCode']) {
                                    $selected = "selected";
                                }
                                $resData[$product->getId()]['uom_html'] .= '<option value="' . $uom['altUomCode'] . '" data-price="' . $this->getPriceWithCurrency($uom['price']) . '"' . $selected . '="true">';
                                if ($uom['altUomCode'] != '') {
                                    $resData[$product->getId()]['uom_html'] .= $uom['altUomCode'];
                                } else {
                                    $resData[$product->getId()]['uom_html'] .= $uom['altUomDesc'];
                                }
                                $resData[$product->getId()]['uom_html'] .= '</option>';
                            }
                        }
                        $resData[$product->getId()]['erp_data'] = $sessionProductData[$product->getSku()];
                        $resData[$product->getId()]['qty'] = $sessionProductData[$product->getSku()]['lineItem']['totalAvailable'];
                    } else {
                        $resData[$product->getId()]['uom_html'] = '<option value="EA" data-price="' . $this->getPriceWithCurrency($product->getPrice()) . '">EA</option>';
                        $resData[$product->getId()]['qty'] = $product->getQty();
                    }
                    $resData[$product->getId()]['m_data'] = $m_data;
                    $resData[$product->getId()]['type'] = $product->getTypeId();
                }
            }
        } else {
            $product = $this->productRepository->getById($params['id']);
            $m_data = $product->getData();
            $resData[$product->getId()]['sku'] = $product->getSku();
            $resData[$product->getId()]['m_data'] = $m_data;
            //$resData[$product->getId()]['description'] = $description;
            $resData[$product->getId()]['price'] = $this->priceHelper->currency($product->getPrice(), true, false);
            $resData[$product->getId()]['qty'] = 0;
            $resData[$product->getId()]['type'] = $product->getTypeId();
        }
        return $resultJson->setData($resData);
    }

    protected function getPriceWithCurrency($price = false)
    {
        if ($price) {
            return $this->priceHelper->currency((float)$price, true, false);
        }
        return false;
    }
}
