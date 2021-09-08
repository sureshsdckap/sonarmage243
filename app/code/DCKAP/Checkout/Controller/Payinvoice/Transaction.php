<?php

namespace Dckap\Checkout\Controller\Payinvoice;

class Transaction extends \Magento\Framework\App\Action\Action
{

    protected $customerSession;
    protected $resultPageFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->customerSession = $customerSession;
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();

        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/pay_invoice.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info("--------------------------------------------------");
        $logger->info('Response from Cayan Payment');
        $logger->info(print_r($params, true));
        $logger->info("--------------------------------------------------");
        $logger->info("##################################################");

        $resultPage = $this->resultPageFactory->create();
        return $resultPage;
    }
}
