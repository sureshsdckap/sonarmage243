<?php

namespace Dckap\QuickRFQ\Controller\Customer;

class Index extends \Magento\Framework\App\Action\Action
{

    private $customerSession;
    protected $_registry;
    protected $resultPageFactory;
    protected $clorasHelper;
    protected $clorasDDIHelper;
    protected $extensionHelper;
    protected $scopeConfig;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Cloras\Base\Helper\Data $clorasHelper,
        \Cloras\DDI\Helper\Data $clorasDDIHelper,
        \DCKAP\Extension\Helper\Data $extensionHelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    )
    {
        parent::__construct($context);
        $this->customerSession = $customerSession;
        $this->_registry = $registry;
        $this->resultPageFactory = $resultPageFactory;
        $this->clorasHelper = $clorasHelper;
        $this->clorasDDIHelper = $clorasDDIHelper;
        $this->extensionHelper = $extensionHelper;
        $this->scopeConfig = $scopeConfig;
    }

    public function execute()
    {
         $configValue = $this->scopeConfig->getValue(
            'themeconfig/mode_config/website_mode',
            \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE
        );
        $this->messageManager->getMessages(true);
        if (!$this->customerSession->isLoggedIn()) {
            $resultRedirect = $this->resultRedirectFactory->create();
            $this->messageManager->addNotice(__("Login Required to view order pad."));
            $loginUrl = $this->_url->getUrl('customer/account/login');
            return $resultRedirect->setPath($loginUrl);
        }
        $params = $this->getRequest()->getParams();
        if (!empty($params['shipto'])) {
            $shipto = $params['shipto'];
        } else {
            $shipto = '';
        }
        if ($this->extensionHelper->getShiptoConfig() && $shipto == '' && $configValue=="b2b") {
            $this->_registry->register('orderpad_items', array());
            $this->_registry->register('handle', array());
            $this->_registry->register('config', 1);
        } else {
            $oldShipto = ($this->customerSession->getShipto()) ? $this->customerSession->getShipto() : '';
            if ($oldShipto == $shipto) {
                $orderPadItems = $this->customerSession->getReportsOrders();
                if (isset($orderPadItems['orderPad']) && count($orderPadItems['orderPad']) > 0) {
                    $formatedOrderHitory = $this->getFormatedReportData($orderPadItems['orderPad'], $orderPadItems['pagination']);
                    $this->_registry->unregister('orderpad_items');
                    $this->_registry->register('orderpad_items', $formatedOrderHitory);
                    $resultPage = $this->resultPageFactory->create();
                    return $resultPage;
                }
            }
            if (isset($params['shipto'])) {
                $this->customerSession->setShipto($params['shipto']);
            } else {
                $this->customerSession->unsShipto();
            }

            $orderPadItems = $this->getCollectionData($shipto);
            $this->customerSession->setReportsOrders($orderPadItems);
	        $formatedData =[];
	        if (isset($orderPadItems['orderPad']) && count($orderPadItems['orderPad']) > 0) {
		        $formatedData = $this->getFormatedReportData($orderPadItems['orderPad'], $orderPadItems['pagination']);
	        }
            $this->_registry->register('orderpad_items', $formatedData);
        }
        $resultPage = $this->resultPageFactory->create();
        return $resultPage;
    }

    protected function getFormatedReportData($data, $pagination)
    {

        $params = $this->getRequest()->getParams();

        if (!empty($params['shipto'])) {
            $shipto = $params['shipto'];
        } else {
            $shipto = NULL;
        }
        if (!empty($params['limit'])) {
            $limit = abs((int)$params['limit']);
        } else {
            if ($pagination && $pagination != '') {
                $limit = (int)$pagination;
            } else {
                $limit = 25;
            }
        }

        if (!empty($params['page'])) {
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

        if (isset($params['sfield']) && !empty($params['sfield'])) {
            $sortField = $params['sfield'];
        } else {
            $sortField = 'lastDate';
        }
        $handleSorder = 0;
        if (isset($params['sorder']) && !empty($params['sorder'])) {
            $sortOrder = ($params['sorder'] == 1) ? SORT_ASC : SORT_DESC;
            $handleSorder = 1;
        } else{
            $sortOrder = SORT_DESC;
        }

        $newData = $data;
        $fdesc = '';
        if (isset($params['fdesc']) && $params['fdesc'] != '') {
            $fdesc = $params['fdesc'];
            foreach ($data as $key => $val) {
                if (!(strpos(strtolower($val['description']), strtolower($fdesc)) !== false)) {
                    unset($newData[$key]);
                }
            }
        }
        $data = $newData;

        $fieldColumn = array_column($data, $sortField);
        if ($sortField == 'price') {
            foreach ($fieldColumn as $key => $val) {
                $fieldColumn[$key] = str_replace('$', '', $val);
            }
        }
//        var_dump($fieldColumn);
        if ($sortField == 'lastDate') {
            foreach ($fieldColumn as $key => $val) {
                $fieldColumn[$key] = strtotime($val);
            }
        }
//        var_dump($fieldColumn);die;
        array_multisort($fieldColumn, $sortOrder, $data);

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
            'current_sfield' => $sortField,
            'current_sorder' => $handleSorder,
            'fdesc' => $fdesc,
            'shipto' => $shipto
        ];
        $this->_registry->register('handle', $handle);

        return $returnData;
    }

    protected function getCollectionData($shipto = false)
    {
        list($status, $integrationData) = $this->clorasDDIHelper->isServiceEnabled('orderpad');
        if ($status) {
            $responseData = $this->clorasDDIHelper->getOrderpadItems($integrationData, $shipto);
            if ($responseData && count($responseData)) {
                return $responseData;
            }
        }
        return false;
    }
}