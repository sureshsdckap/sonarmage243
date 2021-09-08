<?php

namespace Dckap\QuickRFQ\Controller\Invoice;

class Printview extends \Magento\Framework\App\Action\Action
{

    protected $customerSession;
    protected $_registry;
    protected $resultPageFactory;
    protected $clorasHelper;
    protected $clorasDDIHelper;
    protected $extensionHelper;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Cloras\Base\Helper\Data $clorasHelper,
        \Cloras\DDI\Helper\Data $clorasDDIHelper,
        \DCKAP\Extension\Helper\Data $extensionHelper
    ) {
        parent::__construct($context);
        $this->customerSession = $customerSession;
        $this->_registry = $registry;
        $this->resultPageFactory = $resultPageFactory;
        $this->clorasHelper = $clorasHelper;
        $this->clorasDDIHelper = $clorasDDIHelper;
        $this->extensionHelper = $extensionHelper;
    }

    public function execute()
    {
        if (!$this->customerSession->isLoggedIn()) {
            $resultRedirect = $this->resultRedirectFactory->create();
            $this->messageManager->addNotice(__("Login Required to view order detail."));
            $loginUrl = $this->_url->getUrl('customer/account/login');
            return $resultRedirect->setPath($loginUrl);
        }
        $params = $this->getRequest()->getParams();
        $invoiceData = $this->getInvoiceData($params['id']);
        $this->_registry->register('ddi_invoice', $invoiceData);

        $resultPage = $this->resultPageFactory->create();
        return $resultPage;
    }

    protected function getInvoiceData($invoiceNumber = false)
    {
        list($status, $integrationData) = $this->clorasDDIHelper->isServiceEnabled('invoice_detail');
        if ($status) {
            $responseData = $this->clorasDDIHelper->getInvoiceDetail($integrationData, $invoiceNumber);
            if ($responseData && count($responseData)) {
                return $responseData;
            }
        }
        return false;
    }
}