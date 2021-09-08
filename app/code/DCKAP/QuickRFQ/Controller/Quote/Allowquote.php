<?php

namespace Dckap\QuickRFQ\Controller\Quote;

class Allowquote extends \Magento\Framework\App\Action\Action
{

    private $customerSession;
    protected $resultJsonFactory;
    protected $themeHelper;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\SessionFactory $customerSession,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Dckap\Theme\Helper\Data $themeHelper
    ) {
        parent::__construct($context);
        $this->customerSession = $customerSession;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->themeHelper= $themeHelper;
    }

    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $customerSession = $this->customerSession->create();
        $response = array(
            'success' => false,
            'login' => false
        );
        if ($customerSession->isLoggedIn()) {
            $response['success'] = $this->themeHelper->getQuoteOptionView();
            $response['login'] = true;
        }
        $resultJson->setData($response);
        return $resultJson;
       
    }
}