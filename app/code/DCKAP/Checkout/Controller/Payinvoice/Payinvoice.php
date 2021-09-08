<?php

namespace Dckap\Checkout\Controller\Payinvoice;

class Payinvoice extends \Magento\Framework\App\Action\Action
{
    protected $resultJsonFactory;
    protected $customerSession;
    protected $dckapCheckoutHelper;
    protected $clorasDDIHelper;
    protected $paymentConfig;
    protected $encryptor;
    protected $storeManager;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Dckap\Checkout\Helper\Data $dckapCheckoutHelper,
        \Cloras\DDI\Helper\Data $clorasDDIHelper,
        \Dckap\Checkout\Gateway\PaymentConfig $paymentConfig,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->customerSession = $customerSession;
        $this->dckapCheckoutHelper = $dckapCheckoutHelper;
        $this->clorasDDIHelper = $clorasDDIHelper;
        $this->paymentConfig = $paymentConfig;
        $this->encryptor = $encryptor;
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $responseData = $data = [];
        try {
            $params = $this->getRequest()->getParams();
            $data = $params;
            if (isset($params['invoice']) && $params['invoice'] != '') {
                $customerId = $this->customerSession->getCustomerId();
                $data['invoice'] = $this->dckapCheckoutHelper->getInvoices(['data' => $params['invoice']]);

                $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/pay_invoice.log');
                $logger = new \Zend\Log\Logger();
                $logger->addWriter($writer);
                $logger->info("--------------------------------------------------");
                $logger->info('Info sent to ERP after cayan payment');
                $logger->info(print_r($data, true));
                $logger->info("--------------------------------------------------");
                // void payment transaction for mismatch amount begin
                $authorizedAmount = (float)$data['cc_amount_approved'];
                $amount = (float)$data['invoice']['total'];
                $logger->info('$authorizedAmount - '. $authorizedAmount);
                $logger->info('$amount - '. $amount);
                if ($authorizedAmount < $amount) {
                    $paymentConfig = $this->paymentConfig;
                    $encryptor = $this->encryptor;
                    $storeManager = $this->storeManager;
                    $baseUrl = $storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB);
                    $redirectLocation = $baseUrl.'dckapcheckout/payinvoice/transaction';
                    $logger->info('requesting void payment');
                    $merchantName = $paymentConfig->getMerchantName();
                    $merchantSiteId = $encryptor->decrypt($paymentConfig->getMerchantSiteId());
                    $merchantKey = $encryptor->decrypt($paymentConfig->getMerchantKey());
                    
                    $logger->info('merchant name - '.$merchantName);
                    $logger->info('merchant site id - '.$merchantSiteId);
                    $logger->info('merchant key - '.$merchantKey);
                    $token = $data['cc_token'];
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
                                        <soap12:Envelope xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:xsd=\"http://www.w3.org/2001/XMLSchema\" 
                                        xmlns:soap12=\"http://www.w3.org/2003/05/soap-envelope\">
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
                        $responseData['status'] = 'fail';
                        $responseData['msg'] = $err;
                        $logger->info('curl error from void transaction');
                        $logger->info(print_r($err, true));
                    } else {
                        $clean_xml = str_ireplace(['SOAP-ENV:', 'SOAP:'], '', $response);
                        $xml = simplexml_load_string($clean_xml);
                        $voidResult = (array)$xml->Body->VoidResponse->VoidResult;
                        $logger->info((array)$voidResult);
                        $responseData['status'] = 'fail';
                        $logger->info('curl success response from void transaction');
                        if ($voidResult['ApprovalStatus'] == 'APPROVED') {
                            $logger->info('curl approve from void transaction');
                            $responseData['msg'] = 'Transaction has been cancelled due to insufficient balance. Please pay with a different card.';
                        } else {
                            $responseData['msg'] = 'Something went wrong, please try again later.';
                        }
                    }
                    $responseData['void'] = 1;
                } else {
                    $logger->info('submit invoice data to ddi erp');
                    $erpResponseData = $this->submitPayInvoice($data);
                    if (isset($erpResponseData['data']['isValid']) && $erpResponseData['data']['isValid'] == 'yes') {
                        $responseData['status'] = 'success';
                        $responseData['msg'] = 'Invoice has been paid successfully.';
                    } else {
                        $responseData['status'] = 'fail';
                        $responseData['msg'] = 'Something went wrong';
                    }
                    $responseData['void'] = 0;
                }
                // void payment transaction for mismatch amount ends
            } else {
                $responseData['status'] = 'fail';
                $responseData['msg'] = 'Invalid invoice details';
                $responseData['void'] = 0;
            }
        } catch (\Exception $e) {
            $responseData['status'] = 'fail';
            $responseData['msg'] = 'Invalid invoice details';
            $responseData['void'] = 0;
        }
        return $resultJson->setData($responseData);
    }

    protected function submitPayInvoice($data)
    {
        list($status, $integrationData) = $this->clorasDDIHelper->isServiceEnabled('pay_invoice');
        if ($status) {
            $responseData = $this->clorasDDIHelper->submitPayment($integrationData, $data);
            if ($responseData && !empty($responseData)) {
                return $responseData;
            }
        }
        return false;
    }
}
