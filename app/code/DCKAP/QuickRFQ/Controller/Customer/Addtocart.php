<?php

namespace Dckap\QuickRFQ\Controller\Customer;

class Addtocart extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;
    /**
     * @var \Magento\Catalog\Model\Product
     */
    protected $product;

    protected $formKey;

    protected $productRepository;

    protected $jsonFactory;

    private $serializer;
    private $dckapCatalogHelper;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Catalog\Model\Product $product,
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \Magento\Framework\Serialize\SerializerInterface $serializer,
        \DCKAP\Catalog\Helper\Data $dckapCatalogHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    )
    {
        $this->resultPageFactory = $resultPageFactory;
        $this->cart = $cart;
        $this->product = $product;
        $this->formKey = $formKey;
        $this->productRepository = $productRepository;
        $this->jsonFactory = $jsonFactory;
        $this->serializer = $serializer;
        $this->dckapCatalogHelper = $dckapCatalogHelper;
        $this->_storeManager = $storeManager;
        parent::__construct($context);
    }

    public function execute()
    {
        $datas = $this->getRequest()->getParams();
        $AvailSku = false;
        $SkuList = [];
        $resultData = [];
        try {
            if(isset($datas['newvalues']) && count( $datas['newvalues'] )) {
                foreach ($datas['newvalues'] as $key => $qty) {
                    if ($qty != '' && $qty != '0') {
                        $getSkuv = explode('&&', $key);
                        $sku = str_replace('qty_', '', $getSkuv[0]);
                        $params['qty'] = $qty;

                    try {
                        $pros = $this->productRepository->get($sku);
                        $Status = $pros->getStatus();
                        if ($Status != 1) {
                            $SkuList[] = $pros->getSku();
                        } else {
                            $AvailSku = true;
                            $additionalOptions['custom_uom'] = [
                                'label' => 'UOM',
                                'value' => $getSkuv[1]
                            ];

                            $pros->addCustomOption('additional_options', $this->serializer->serialize($additionalOptions));
                            $params['custom_uom'] = $getSkuv[1];
                            if ($pros) {
                                $params['product'] = $pros->getId();
                                $this->cart->addProduct($pros, $params);
                            }
                        }
                    } catch (\Exception $e) {
                        $SkuList[] = $sku;
                        $resultData['status'] = "Failure";
                        $resultData['msg'] = $sku ." this product temporarily unavailable.";
                    }

                    }
                }
            }

            $this->messageManager->getMessages(true);
            if ($AvailSku) {
                $this->cart->save();
                $this->messageManager->addSuccess(__('Product(s) added to <a href="'.$this->_storeManager->getStore()->getBaseUrl().'checkout/cart/">shopping cart</a> successfully.'));
                $resultData['status'] = "Success";
                $resultData['msg'] = '';
            }

            if ($SkuList && count($SkuList)) {
                $SkuList = implode(', ', $SkuList);
                $this->messageManager->addWarning(__('%1 These product(s) are currently unavailable to be purchased.',
                    $SkuList));
                $resultData['status'] = "Failure";
                $resultData['msg'] = $SkuList. " this product temporarily unavailable.";
            }

        } catch (\Exception $e) {
//            $this->messageManager->addException($e, __('error'));
            $this->messageManager->addException($e, $e->getMessage());
            $resultData['status'] = "Failure";
            $resultData['msg'] = $e->getMessage();
        }
        $res = $this->jsonFactory->create();
        $resultData['backurl' ] = 'checkout/cart';
        $result = $res->setData($resultData);
        return $result;
    }
}
