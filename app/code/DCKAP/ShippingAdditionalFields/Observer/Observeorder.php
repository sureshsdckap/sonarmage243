<?php

namespace Dckap\ShippingAdditionalFields\Observer;

/**
 * Class Observeorder
 *
 * @package Dckap\ShippingAdditionalFields\Observer
 */
class Observeorder implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /* before quote submit save the freight list values in sales_order_address table */
         $writer = new \Zend\Log\Writer\Stream(BP . "/var/log/mylogfile.log");
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);


        $order = $observer->getEvent()->getOrder();
        $quote = $observer->getEvent()->getQuote();
        $logger->info("observer");
        $logger->info($quote->getDdiPickupDate());

        $order->setData("ddi_delivery_contact_email", $quote->getDdiDeliveryContactEmail());
        $order->setData("ddi_delivery_contact_no", $quote->getDdiDeliveryContactNo());
        $order->setData("ddi_pref_warehouse", $quote->getDdiPrefWarehouse());
        $order->setData("ddi_pickup_date", $quote->getDdiPickupDate());
        $logger->info($quote->getDdiPickupDate());
        
        return $this;
    }
}
