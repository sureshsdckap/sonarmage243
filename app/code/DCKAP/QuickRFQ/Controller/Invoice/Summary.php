<?php

namespace Dckap\QuickRFQ\Controller\Invoice;

class Summary extends \Magento\Framework\App\Action\Action
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
            $this->messageManager->addNotice(__("Login Required to view Invoice Summary."));
            $loginUrl = $this->_url->getUrl('customer/account/login');
            return $resultRedirect->setPath($loginUrl);
        }
        $params = $this->getRequest()->getParams();

//        $ddiInvoices = $this->customerSession->getDdiInvoices();
        if (isset($params['startDate']) && isset($params['endDate'])) {
            $startDate = $params['startDate'];
            $endDate = $params['endDate'];
        } else {
            $startDate = date('m/d/y', strtotime('-90 day'));
            $endDate = date('m/d/y');
        }
        /*$oldStartDate = $ddiInvoices['startDate'];
        $oldEndDate = $ddiInvoices['endDate'];
        if ($startDate == $oldStartDate && $endDate == $oldEndDate) {
            if (isset($ddiInvoices['invoiceList']) && count($ddiInvoices['invoiceList']) > 0) {
                $invoiceList = array();
                if (isset($ddiInvoices['invoiceList'])) {
                    $invoiceList = $ddiInvoices['invoiceList'];
                }
                $formatedOrderHitory = $this->getFormatedReportData($invoiceList, 25);
                $this->_registry->unregister('ddi_invoices');
                $this->_registry->register('ddi_invoices', $formatedOrderHitory);
                $this->_registry->register('ddi_custledger', $this->getCustLedgerData());
                $resultPage = $this->resultPageFactory->create();
                return $resultPage;
            }
        }*/
        $filterData = array(
            'startDate' => $startDate,
            'endDate' => $endDate
        );
        $filterData['openOnly'] = 'Y';
        $ddiInvoices = $this->getCollectionData($filterData);
//        $this->customerSession->setDdiInvoices($ddiInvoices);
        $invoiceList = array();
        if (isset($ddiInvoices['invoiceList'])) {
            $invoiceList = $ddiInvoices['invoiceList'];
        }
        $formatedData = $this->getFormatedReportData($invoiceList, 25);
        $this->_registry->register('ddi_invoices', $formatedData);
        $this->_registry->register('ddi_custledger', $this->getCustLedgerData());

        $resultPage = $this->resultPageFactory->create();
        return $resultPage;
    }

    protected function getFormatedReportData($data, $pagination)
    {

        $params = $this->getRequest()->getParams();

        if (!empty($params['limit'])) {
            $limit = abs((int)$params['limit']);
        } else {
            if ($pagination && $pagination != '') {
                $limit = (int)$pagination;
            } else {
                $limit = 25;
            }
        }
        if (count($data)) {
            $limit = count($data);
        }

        if (isset($params['startDate'])) {
            $startDate = $params['startDate'];
        } else {
            $startDate = date('m/d/y', strtotime('-90 day'));
        }

        if (isset($params['endDate'])) {
            $endDate = $params['endDate'];
        } else {
            $endDate = date('m/d/y');
        }

        if (isset($params['page'])) {
            $page = abs((int)$params['page']);
        } else {
            $page = 1;
        }

        if ($page == 1) {
            $firstPage = NULL;
        } else {
            $firstPage = 1;
        }

        $lastPage = floor(count($data) / $limit);

        if (fmod(count($data), $limit) > 0) {
            $lastPage = $lastPage + 1;
        }
        if ($lastPage == $page) {
            $lastPage = NULL;
        }

        if ($page > 1) {
            $prevPage = $page - 1;
        } else {
            $prevPage = NULL;
        }

        if ($page < $lastPage) {
            $nextPage = $page + 1;
        } else {
            $nextPage = NULL;
        }
        $start = abs($limit * ($page - 1));

        $returnData = array_slice($data, $start, $limit);

        if (count($data) < $limit) {
            $end = count($data);
        } elseif (count($returnData) < $limit) {
            $end = $start + count($returnData);
        } else {
            $end = abs($limit * ($page));
        }

        $handle = ['current_page' => $page,
            'first_page' => $firstPage,
            'last_page' => $lastPage,
            'prev_page' => $prevPage,
            'next_page' => $nextPage,
            'records_count' => count($data),
            'start' => $start + 1,
            'end' => $end,
            'start_date' => $startDate,
            'end_date' => $endDate
        ];
        $this->_registry->register('handle', $handle);

        return $returnData;
    }

    protected function getCollectionData($filterData = false)
    {
        list($status, $integrationData) = $this->clorasDDIHelper->isServiceEnabled('invoice_list');
        if ($status) {
            $responseData = $this->clorasDDIHelper->getInvoiceList($integrationData, $filterData);
            if ($responseData && count($responseData)) {
                foreach ($responseData['invoiceList'] as $key => $invoice) {
                    if ($invoice['invoiceStatus'] == 'Completed') {
                        unset($responseData['invoiceList'][$key]);
                    }
                }
                $responseData['startDate'] = $filterData['startDate'];
                $responseData['endDate'] = $filterData['endDate'];
                return $responseData;
            }
        }
        return false;
    }

    protected function getCustLedgerData()
    {
        list($status, $integrationData) = $this->clorasDDIHelper->isServiceEnabled('cust_ledger');
        if ($status) {
            $responseData = $this->clorasDDIHelper->getCustLedger($integrationData);
            if ($responseData && count($responseData)) {
                return $responseData;
            }
        }
        return false;
    }
}