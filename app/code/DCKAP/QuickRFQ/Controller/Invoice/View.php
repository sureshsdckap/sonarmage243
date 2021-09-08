<?php

namespace Dckap\QuickRFQ\Controller\Invoice;

use Magento\Framework\App\Filesystem\DirectoryList;

class View extends \Magento\Framework\App\Action\Action
{

    protected $customerSession;
    protected $_registry;
    protected $resultPageFactory;
    protected $clorasHelper;
    protected $clorasDDIHelper;
    protected $extensionHelper;
    protected $filesystem;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Cloras\Base\Helper\Data $clorasHelper,
        \Cloras\DDI\Helper\Data $clorasDDIHelper,
        \DCKAP\Extension\Helper\Data $extensionHelper,
        \Magento\Framework\Filesystem $filesystem
    ) {
        parent::__construct($context);
        $this->customerSession = $customerSession;
        $this->_registry = $registry;
        $this->resultPageFactory = $resultPageFactory;
        $this->clorasHelper = $clorasHelper;
        $this->clorasDDIHelper = $clorasDDIHelper;
        $this->extensionHelper = $extensionHelper;
        $this->filesystem = $filesystem;
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
        $invoiceData = $this->getInvoiceData($params['id'], true);

        /**
         * Check whether invoice pdf is available or not
         * If available create that as pdf file in var folder and opens that in new tab
         * If Not display view invoice page contents
         */

        if (isset($invoiceData['invoiceDetails']['shipMethod']['attachment']) && $invoiceData['invoiceDetails']['shipMethod']['attachment']['mimeType'] == 'application/pdf') {
            $base64 = $invoiceData['invoiceDetails']['shipMethod']['attachment']['documentData'];
            $decoded = base64_decode($base64);
            $media = $this->filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
            $file = $this->filesystem->getDirectoryRead(DirectoryList::VAR_DIR)->getAbsolutePath();
            $filename = "tmp/invoice_".$params['id'].".pdf";
            $media->writeFile($filename, $decoded);
            $file = $file . $filename;

            if (file_exists($file)) {
                header('Content-Type: application/pdf');
                header('Content-Disposition: inline; filename="' . basename($file) . '"');
                header('Content-Transfer-Encoding: binary');
                header('Content-Length: ' . filesize($file));
                readfile($file);
                exit;
            }
        }


        $invoiceData = $this->getInvoiceData($params['id'], true);
        $this->_registry->register('ddi_invoice', $invoiceData);

        $resultPage = $this->resultPageFactory->create();
        return $resultPage;
    }

    protected function getInvoiceData($invoiceNumber = false, $isPdf = false)
    {
        list($status, $integrationData) = $this->clorasDDIHelper->isServiceEnabled('invoice_detail');
        if ($status) {
            $responseData = $this->clorasDDIHelper->getInvoiceDetail($integrationData, $invoiceNumber, $isPdf);
            if ($responseData && count($responseData)) {
                return $responseData;
            }
        }
        return false;
    }
}