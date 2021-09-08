<?php

namespace DCKAP\OrderApproval\Block;


class CustomMessage extends \Magento\Framework\View\Element\Template
{

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;
    /**
     * @var \Magento\Quote\Model\QuoteRepository
     */
    protected $quoteRepository;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry             $registry
     * @param \Magento\Framework\Data\FormFactory     $formFactory
     * @param array                                   $data
     *
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Checkout\Model\Session $checkoutSession,


        array $data = []
    )
    {
        $this->messageManager = $messageManager;
        $this->checkoutSession = $checkoutSession;
        parent::__construct($context, $data);
    }


    public function getDisplayAction()
    {
        /** @var \Magento\Quote\Model\Quote  */
        $quote = $this->checkoutSession->getQuote();
        if(false==is_null($quote->getOrderId())) {
           $this->messageManager->addWarningMessage(__("You are editing an existing order."));

         }

    }
}