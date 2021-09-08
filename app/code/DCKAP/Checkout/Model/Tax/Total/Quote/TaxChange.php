<?php
namespace Dckap\Checkout\Model\Tax\Total\Quote;

use Magento\Checkout\Model\SessionFactory as CheckoutSession;

class TaxChange
{
    protected $checkoutSession;

    public function __construct(
        CheckoutSession $checkoutSession
    ) {
        $this->checkoutSession = $checkoutSession;
    }

    public function afterCollect(\Magento\Tax\Model\Sales\Total\Quote\Tax $subject, $result, $quote, $shippingAssignment, $total)
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/dditax.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info("Tax afterCollect method started");

        $checkoutSession = $this->checkoutSession->create();
        $checkoutData = $checkoutSession->getCheckoutData();
        if ($checkoutData && !empty($checkoutData)) {
            $quote = $checkoutSession->getQuote();
            $logger->info('quote id - '.$quote->getId());
            $logger->info('quote customer email - '.$quote->getCustomerEmail());
            $ddiTaxAmount = 0.00;
            if (isset($checkoutData[$quote->getId()])) {
                $ddiTaxAmount = $checkoutData[$quote->getId()];
                $logger->info('Get Tax Amount after collect');
                $logger->info($ddiTaxAmount);
                $total->setGrandTotal($total->getGrandTotal() + $ddiTaxAmount);
                $total->setBaseGrandTotal($total->getBaseGrandTotal() + $ddiTaxAmount);
                $total->setTaxAmount($ddiTaxAmount);
                $total->setBaseTaxAmount($ddiTaxAmount);
                $logger->info('getTaxAmount after collect - '.$total->getTaxAmount());
                $logger->info('getGrandTotal after collect - '.$total->getGrandTotal());
                $logger->info("#########################");
//                 $checkoutSession->unsCheckoutData();
            }
        }
        return $result;
    }
}
