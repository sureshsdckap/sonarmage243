<?php
/**
 * Copyright © 2016 DCKAP. All rights reserved.
 */

namespace DCKAP\OrderApproval\Controller\Order;

/**
 * Class Cancel
 * @package DCKAP\OrderApproval\Controller\Order
 */
class Cancel extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $jsonFactory;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * Cancel constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Controller\Result\JsonFactory $jsonFactory
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Customer\Model\Session $customerSession
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $jsonFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->orderRepository = $orderRepository;
        $this->customerSession = $customerSession;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        if (!$this->customerSession->isLoggedIn()) {
            $resultRedirect = $this->resultRedirectFactory->create();
            $this->messageManager->addNotice(__("Login required to cancel the order."));
            $loginUrl = $this->_url->getUrl('customer/account/login');
            return $resultRedirect->setPath($loginUrl);
        }
        $params = $this->getRequest()->getParams();
        $orderId = $params['order_id'];
        $data = [];
        try {
            $order = $this->orderRepository->get($orderId);
            $order->setState("pending")->setStatus("customer_cancelled");
            $order->save();
            $data['status'] = "Success";
            $data['message'] = __("Order cancelled successfully.");
        } catch (\Exception $e) {
            $data['status'] = "Failure";
            $data['message'] = $e->getMessage();
        }
        if (isset($params['is_ajax']) && $params['is_ajax'] == '1') {
            $res = $this->jsonFactory->create();
            $result = $res->setData($data);
            return $result;
        } else {
            $resultRedirect = $this->resultRedirectFactory->create();
            if ($data['status'] == "Success") {
                $this->messageManager->addSuccess(__($data['message']));
            } else {
                $this->messageManager->addNotice(__($data['message']));
            }
            $loginUrl = $this->_url->getUrl('orderapproval/index/submittedorders');
            return $resultRedirect->setPath($loginUrl);
        }
    }
}
