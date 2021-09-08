<?php

namespace Dckap\Checkout\Controller\Index;

class Transaction extends \Magento\Framework\App\Action\Action
{

    protected $customerSession;
    protected $resultPageFactory;
    protected $_checkoutSession;
    protected $paymentConfig;
    protected $encryptor;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Checkout\Model\SessionFactory $_checkoutSession,
        \Dckap\Checkout\Gateway\PaymentConfig $paymentConfig,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor
    ) {
        parent::__construct($context);
        $this->customerSession = $customerSession;
        $this->resultPageFactory = $resultPageFactory;
        $this->_checkoutSession = $_checkoutSession;
        $this->paymentConfig = $paymentConfig;
        $this->encryptor = $encryptor;
    }

    public function execute()
    {
        $params = $this->getRequest()->getParams();
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/checkout_transaction.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $customer = $this->customerSession->getCustomer();
        $logger->info("Cayan payment process response");
        $logger->info("Customer Id - ".$customer->getId());
        $logger->info("Customer - ".$customer->getEmail());
        $logger->info('Response from Cayan Payment');
        $logger->info(print_r($params, true));
        // begin void transaction if amount not matched only for checkout page
        /*$controllerName = $this->getRequest()->getControllerName();
        $actionName = $this->getRequest()->getActionName();
        $routeName = $this->getRequest()->getRouteName();*/
        
        $logger->info('inside checkout page if condition');
        /*$cart = $objectManager->get('\Magento\Checkout\Model\Cart');
        $quote = $itemsCollection = $cart->getQuote();
        $amount = (float)$quote->getGrandTotal();*/
        $checkoutSession = $this->_checkoutSession->create();
        $checkoutReviewData = $checkoutSession->getCheckoutData();
        $logger->info(print_r($checkoutReviewData, true));
        $amount = $checkoutReviewData['order_total'];
        $logger->info($amount);
            $authorizedAmount = (float)$params['AmountApproved'];
        if ($authorizedAmount < $amount) {
            $logger->info('inside amount check condition for void payment');
            $logger->info('requesting void payment');
            $merchantName = $this->paymentConfig->getMerchantName();
            $merchantSiteId = $this->encryptor->decrypt($this->paymentConfig->getMerchantSiteId());
            $merchantKey = $this->encryptor->decrypt($this->paymentConfig->getMerchantKey());
                
            $logger->info('merchant name'.$merchantName);
            $logger->info('merchant site id'.$merchantSiteId);
            $logger->info('merchant key'.$merchantKey);
            $token = $params['Token'];
            $logger->info('token'.$token);
            $curl = curl_init();
            curl_setopt_array($curl, [
            CURLOPT_URL => "https://ps1.merchantware.net/Merchantware/ws/RetailTransaction/v46/Credit.asmx",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "<?xml version=\"1.0\" encoding=\"utf-8\"?>
                                    <soap12:Envelope xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" xmlns:soap12=\"http://www.w3.org/2003/05/soap-envelope\">
                                        <soap12:Body>
                                            <Void xmlns=\"http://schemas.merchantwarehouse.com/merchantware/v46/\">
                                                <Credentials>
                                                    <MerchantName>".$merchantName."</MerchantName>
                                                    <MerchantSiteId>".$merchantSiteId."</MerchantSiteId>
                                                    <MerchantKey>".$merchantKey."</MerchantKey>
                                                </Credentials>
                                                <Request>
                                                    <Token>".$token."</Token>
                                                    <RegisterNumber>123</RegisterNumber>
                                                    <CardAcceptorTerminalId>32</CardAcceptorTerminalId>
                                                </Request>
                                            </Void>
                                        </soap12:Body>
                                    </soap12:Envelope>",
            CURLOPT_HTTPHEADER => [
                "cache-control: no-cache",
                "content-type: text/xml"
            ],
            ]);
            $response = curl_exec($curl);
            $err = curl_error($curl);
            $logger->info($response);
            curl_close($curl);
            if ($err) {
                $voiderror  = "cURL Error #:" . $err;
                $logger->info('curl error from void transaction'.$err);
            } else {
                $clean_xml = str_ireplace(['SOAP-ENV:', 'SOAP:'], '', $response);
                $xml = simplexml_load_string($clean_xml);
                $voidResult = (array)$xml->Body->VoidResponse->VoidResult;
                $logger->info((array)$voidResult);
                    
                $logger->info('curl success response from void transaction');
                if ($voidResult['ApprovalStatus'] = 'APPROVED') {
                    $logger->info('curl approve from void transaction in checkout');
                } else {
                    $logger->info('curl not approved from void transaction in checkout');
                }
            }
        }

        // ends void transaction if amount not matched only for checkout page
        $resultPage = $this->resultPageFactory->create();
        return $resultPage;
    }
}
