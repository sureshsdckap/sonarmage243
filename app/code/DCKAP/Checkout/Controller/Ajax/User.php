<?php

namespace Dckap\Checkout\Controller\Ajax;

class User extends \Magento\Framework\App\Action\Action
{
    protected $resultJsonFactory;
    protected $customerSession;
    protected $extensionHelper;
    protected $orderApprovalHelper;
    protected $checkoutSession;
    protected $addressRepository;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Customer\Model\Session $customerSession,
        \DCKAP\Extension\Helper\Data $extensionHelper,
        \DCKAP\OrderApproval\Helper\Data $orderApprovalHelper,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->customerSession = $customerSession;
        $this->extensionHelper = $extensionHelper;
        $this->orderApprovalHelper = $orderApprovalHelper;
        $this->checkoutSession = $checkoutSession;
        $this->addressRepository = $addressRepository;
        parent::__construct($context);
    }

    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $data = [];
        try {
            $params = $this->getRequest()->getParams();
            $customerSessionData = $this->customerSession->getCustomData();
            $websiteMode = $this->extensionHelper->getWebsiteMode();
            if ($websiteMode == 'b2c') {
                $data['allowOnAccount'] = 'no';
            } else {
                $data['allowOnAccount'] = $customerSessionData['allowOnAccount'];
            }

            /**
             * Get order approval status based on the shipto
             */
            $orderApprovalStatus = '';
            if (isset($params['place']) && $params['place'] == 'minicart') {
                $orderApprovalStatus = $this->orderApprovalHelper->getDefaultOrderApprovalStatus();
            } else {
                $email = $this->customerSession->getCustomer()->getEmail();
                $isB2B = $this->customerSession->getCustomer()->getData('is_b2b');
                $accountNumber = $customerSessionData['accountNumber'];
                $shiptoNumber = '';
                $arrMixShippingAddress = $this->checkoutSession->getQuote()->getShippingAddress()->getData();
                if (true == array_key_exists('customer_address_id', $arrMixShippingAddress) && false == empty($arrMixShippingAddress['customer_address_id'])) {
                    $intAddressId = (int) $arrMixShippingAddress['customer_address_id'];
                    $objShipToAddress = $this->addressRepository->getById($intAddressId);
                    $objDdiShipToNumber = $objShipToAddress->getCustomAttribute('ddi_ship_number');
                    if (true == is_object($objDdiShipToNumber)) {
                        $shiptoNumber = $objDdiShipToNumber->getValue();
                    }
                }
                $data['is_b2b'] = $isB2B;
                $orderApprovalStatus = $this->orderApprovalHelper->getOrderApprovalStatus($email, $accountNumber, $shiptoNumber, $isB2B);
            }
            $boolIsFromOrderEdit = $this->orderApprovalHelper->getIsFromOrderApprovalEdit();

            $data['order_approval'] = $orderApprovalStatus;
            $data['is_from_edit_order'] = $boolIsFromOrderEdit;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
        return $resultJson->setData($data);
    }
}
