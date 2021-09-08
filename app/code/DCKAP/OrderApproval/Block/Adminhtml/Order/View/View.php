<?php

namespace DCKAP\OrderApproval\Block\Adminhtml\Order\View;

use Magento\Setup\Exception;

class View extends \Magento\Backend\Block\Template
{
    protected $orderRepository;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $serializer;

    protected $urlBuider;

    protected $orderApprovalHelper;
    protected $_orderCollectionFactory;
    protected $_logger;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Framework\Serialize\Serializer\Json $serializer,
        \Magento\Framework\UrlInterface $urlBuilder,
        \DCKAP\OrderApproval\Helper\Data $orderApprovalHelper,
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
        \Psr\Log\LoggerInterface $logger,
        array $data = []
    ) {
        $this->orderRepository = $orderRepository;
        $this->serializer = $serializer;
        $this->urlBuilder = $urlBuilder;
        $this->orderApprovalHelper = $orderApprovalHelper;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->logger = $logger;
        parent::__construct($context, $data);
    }

    /**
     * @return mixed
     */
    public function isOrderApprovalEnabled()
    {
        return $this->orderApprovalHelper->isOrderApprovalEnabled();
    }

    public function getAdminOrderApprovalData($orderId = false)
    {
        if ($orderId && $orderId != '') {
            try {
                $order = $this->orderRepository->get($orderId);
                $adminOrderApprovalDetails = $order->getAdminApprovalDetails();
                if ($adminOrderApprovalDetails != '' && $adminOrderApprovalDetails != null) {
                    return $this->serializer->unserialize($adminOrderApprovalDetails);
                }
            } catch (\Exception $e) {
                return false;
            }
        }
        return false;
    }

    public function getBackToPendingApprovalUrl($intOrderId)
    {
        return $this->urlBuilder->getUrl('orderapproval/order/backtopendingapproval', ['order_id' => $intOrderId]);
    }

    public function getApprovalUrl($intOrderId)
    {
        return $this->urlBuilder->getUrl('orderapproval/order/approve', ['order_id' => $intOrderId]);
    }
    public function getOriginalOrderUrl($intOrderId)
    {
        return $this->urlBuilder->getUrl('sales/order/view', ['order_id' => $intOrderId]);
    }
    public function getNewOrderUrl($intOldOrderId){
        $strNewOrderUrl = '#';
        try{
            $objExistingOrderDetail= $this->_orderCollectionFactory->create()->addFieldToSelect('*')
                ->addFieldToFilter('existing_order_id', ['eq' => $intOldOrderId])->getFirstItem();
            $intOrderId = (int) $objExistingOrderDetail->getId();
            return $this->urlBuilder->getUrl('sales/order/view', ['order_id' =>$intOrderId ]);
        } catch (\Exception $e) {
            $this->_logger->info('Error in admin - '.$e->getMessage());
        }
        return $strNewOrderUrl;
    }
    /**
     * @return array
     */
    public function getUnserilizeOrderDetail($strJsonOrderDetails)
    {
        $arrOrderDetails = [];
        if(!empty($strJsonOrderDetails)){
            $arrOrderDetails = $this->serializer->unserialize($strJsonOrderDetails);
        }
        return $arrOrderDetails;
    }
}
