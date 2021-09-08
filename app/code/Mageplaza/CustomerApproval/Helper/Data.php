<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category  Mageplaza
 * @package   Mageplaza_CustomerApproval
 * @copyright Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license   https://www.mageplaza.com/LICENSE.txt
 */

namespace Mageplaza\CustomerApproval\Helper;

use Exception;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Helper\View as CustomerViewHelper;
use Magento\Customer\Model\Context as CustomerContext;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Framework\App\Area;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\FailureToSendException;
use Magento\Framework\Stdlib\Cookie\PhpCookieManager;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mageplaza\Core\Helper\AbstractData;
use Mageplaza\CustomerApproval\Model\Config\Source\AttributeOptions;
use Mageplaza\CustomerApproval\Model\Config\Source\TypeAction;
use Cloras\DDI\Helper\Data as ClorasDDIHelper;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use   Magento\Customer\Model\Session  as coreCustomerSession;

/**
 * Class Data
 *
 * @package Mageplaza\CustomerApproval\Helper
 */
class Data extends AbstractData
{
    const CONFIG_MODULE_PATH = 'mpcustomerapproval';
    const XML_PATH_EMAIL     = 'email';
    const DEFAULT_SHIP_TO_ID  = '00000001';

    /**
     * @var HttpContext
     */
    protected $_httpContext;

    /**
     * @var TransportBuilder
     */
    protected $transportBuilder;

    /**
     * @var CookieMetadataFactory
     */
    private $cookieMetadataFactory;

    /**
     * @var PhpCookieManager
     */
    private $cookieMetadataManager;

    /**
     * @var AttributeOptions
     */
    private $attributeOptions;

    /**
     * @var CustomerViewHelper
     */
    protected $customerViewHelper;

    /**
     * @var CustomerRegistry
     */
    protected $customerRegistry;
    protected $clorasDDIHelper;
    protected $customerRepositoryInterface;
    protected $addressRepository;
    /**
     * Data constructor.
     *
     * @param Context $context
     * @param ObjectManagerInterface $objectManager
     * @param StoreManagerInterface $storeManager
     * @param HttpContext $httpContext
     * @param TransportBuilder $transportBuilder
     * @param AttributeOptions $attributeOptions
     * @param CustomerViewHelper $customerViewHelper
     * @param CustomerRegistry $customerRegistry
     */
    public function __construct(
        Context $context,
        ObjectManagerInterface $objectManager,
        StoreManagerInterface $storeManager,
        HttpContext $httpContext,
        TransportBuilder $transportBuilder,
        AttributeOptions $attributeOptions,
        CustomerViewHelper $customerViewHelper,
        CustomerRegistry $customerRegistry,
        ClorasDDIHelper $clorasDDIHelper,
        CustomerRepositoryInterface $customerRepositoryInterface,
        AddressRepositoryInterface $addressRepository,
        coreCustomerSession $coreCustomerSession
    ) {
        $this->_httpContext = $httpContext;
        $this->transportBuilder = $transportBuilder;
        $this->attributeOptions = $attributeOptions;
        $this->customerViewHelper = $customerViewHelper;
        $this->customerRegistry = $customerRegistry;
        $this->clorasDDIHelper = $clorasDDIHelper;
        $this->customerRepositoryInterface = $customerRepositoryInterface;
        $this->storeManager=$storeManager;
        $this->addressRepository = $addressRepository;
	    $this->coreCustomerSession = $coreCustomerSession;
        parent::__construct($context, $objectManager, $storeManager);
    }

    /**
     * @param $customerId
     *
     * @return CustomerInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getCustomerById($customerId)
    {
        $customerModel = $this->customerRegistry->retrieve($customerId);

        return $customerModel->getDataModel();
    }

    /**
     * @param int|null $customerId
     *
     * @return mixed
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getIsApproved($customerId)
    {
        $isApprovedObject = $this->getCustomerById($customerId)
            ->getCustomAttribute('is_approved');
        if (!$isApprovedObject || !$isApprovedObject->getValue()) {
            $this->approvalAction($customerId, AttributeOptions::APPROVED);

            return AttributeOptions::APPROVED;
        }

        return $isApprovedObject->getValue();
    }

    /**
     * @param $status
     *
     * @return string
     */
    public function getApprovalLabel($status)
    {
        $options = $this->attributeOptions->toArray();
        if (!array_key_exists($status, $options)) {
            return '';
        }

        return $options[$status];
    }

    /**
     * @param $customerId
     * @param $typeAction
     *
     * @throws Exception
     */
    public function approvalCustomerById($customerId, $typeAction = TypeAction::OTHER)
    {
        $customer = $this->customerRegistry->retrieve($customerId);
        $this->approvalAction($customer, AttributeOptions::APPROVED);
        // send email
        if ((!$this->getAutoApproveConfig() && !$this->isAdmin()) || $typeAction != TypeAction::OTHER) {
            $this->emailApprovalAction($customer, 'approve');
        }
    }

    /**
     * @param int $customerId
     *
     * @throws Exception
     */
    public function notApprovalCustomerById($customerId)
    {
        $customer = $this->customerRegistry->retrieve($customerId);
        $this->approvalAction($customer, AttributeOptions::NOTAPPROVE);
        // send email
        $this->emailApprovalAction($customer, 'not_approve');
    }

    /**
     * @param Customer|int $customer
     * @param string $typeApproval
     *
     * @throws Exception
     */
    public function approvalAction($customer, $typeApproval)
    {
        if (is_int($customer)) {
            $customer = $this->customerRegistry->retrieve($customer);
        }

        if (!$customer instanceof Customer) {
            throw new NoSuchEntityException(__('Customer does not exist.'));
        }

        $customerData = $customer->getDataModel();
        $attribute = $customerData->getCustomAttribute('is_approved');
        if ($attribute && $attribute->getValue() != $typeApproval) {
            $customerData->setId($customer->getId());
            $customerData->setCustomAttribute('is_approved', $typeApproval);
            $customer->updateData($customerData);
            $customer->save();
        }
        if($typeApproval == 'approved') {
            $creationStatus = $this->erpCustomerCreation($customer->getId());
        }
    }

    /**
     * @param int $customerId
     * @param bool $actionRegister
     *
     * @throws Exception
     */
    public function setApprovePendingById($customerId, $actionRegister)
    {
        if ($this->getIsApproved($customerId) != AttributeOptions::PENDING) {
            $customer = $this->customerRegistry->retrieve($customerId);
            $customerData = $customer->getDataModel();

            $customerData->setId($customerId);
            $customerData->setCustomAttribute('is_approved', AttributeOptions::PENDING);
            $customer->updateData($customerData);
            $customer->save();

            if ($actionRegister) {
                $this->emailApprovalAction($customer, 'success');
            }
        }
    }

    /**
     * @return int
     * @throws NoSuchEntityException
     */
    public function getStoreId()
    {
        return $this->storeManager->getStore()->getId();
    }

    /**
     * @return bool
     */
    public function isCustomerApprovalEnabled()
    {
        return $this->isEnabled();
    }

    /**
     * @return mixed|null
     */
    public function getCustomerGroupId()
    {
        return $this->_httpContext->getValue(CustomerContext::CONTEXT_GROUP);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getEnabledNoticeAdmin($storeId = null)
    {
        return $this->getModuleConfig('admin_notification_email/enabled', $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getNoticeAdminTemplate($storeId = null)
    {
        return $this->getModuleConfig('admin_notification_email/template', $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getSenderAdmin($storeId = null)
    {
        return $this->getModuleConfig('admin_notification_email/sender', $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getRecipientsAdmin($storeId = null)
    {
        return preg_replace('/\s+/', '', $this->getModuleConfig('admin_notification_email/sendto', $storeId));
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getSenderCustomer($storeId = null)
    {
        return $this->getModuleConfig('customer_notification_email/sender', $storeId);
    }

    /**
     * @param $type
     * @param null $storeId
     *
     * @return mixed
     */
    public function getEmailEnable($type, $storeId = null)
    {
        return $this->getModuleConfig(
            'customer_notification_email/customer_' . $type . '_email/enabled',
            $storeId
        );
    }

    /**
     * @param $type
     * @param null $storeId
     *
     * @return mixed
     */
    public function getEmailTemplate($type, $storeId = null)
    {
        return $this->getModuleConfig(
            'customer_notification_email/customer_' . $type . '_email/template',
            $storeId
        );
    }

    /**
     * @param $customer
     * @param $emailType
     *
     * @throws NoSuchEntityException
     */
    public function emailApprovalAction($customer, $emailType)
    {
        $storeId = $this->getStoreId();


        $sendTo = $customer->getEmail();
        $sender = $this->getSenderCustomer();
        if ($this->getAutoApproveConfig()) {
            $sender = $this->getConfigValue('customer/create_account/email_identity');
        }

        if ($this->getEmailEnable($emailType)) {
            $template = $this->getEmailTemplate($emailType);
            $this->sendMail($sendTo, $customer, $template, $storeId, $sender);
        }
    }

    /**
     * @param $customer
     *
     * @throws NoSuchEntityException
     */
    public function emailNotifyAdmin($customer)
    {
        $storeId = $this->getStoreId();
        $sender = $this->getSenderAdmin();
        if ($this->getAutoApproveConfig()) {
            $sender = $this->getConfigValue('customer/create_account/email_identity');
        }
        $sendTo = $this->getRecipientsAdmin();
        $sendToArray = explode(',', $sendTo);

        if ($this->getEnabledNoticeAdmin()) {
            // send email notify to admin
            foreach ($sendToArray as $recipient) {
                $this->sendMail(
                    $recipient,
                    $customer,
                    $this->getNoticeAdminTemplate(),
                    $storeId,
                    $sender
                );
            }
        }
    }

    /**
     * @param $sendTo
     * @param $customer
     * @param $emailTemplate
     * @param $storeId
     * @param $sender
     *
     * @return bool
     */
    public function sendMail($sendTo, $customer, $emailTemplate, $storeId, $sender)
    {
        try {
            /** @var Customer $mergedCustomerData */
            $customerEmailData = $this->customerRegistry->retrieve($customer->getId());
            $customerEmailData->setData('name', $customerEmailData->getName());
	        $url =  $this->storeManager->getStore($customer->getStoreId())->getBaseUrl()."customer/account/";
            $transport = $this->transportBuilder
                ->setTemplateIdentifier($emailTemplate)
                ->setTemplateOptions([
                    'area'  => Area::AREA_FRONTEND,
                    'store' => $storeId,
                ])
                ->setTemplateVars([
                    'customer' => $customerEmailData,
	                'code'=>$url
                ])
                ->setFrom($sender)
                ->addTo($sendTo)
                ->getTransport();
            $transport->sendMessage();

            return true;
        } catch (Exception $e) {
            $this->_logger->critical($e->getLogMessage());
        }

        return false;
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getAutoApproveConfig($storeId = null)
    {
        return $this->getConfigGeneral('auto_approve', $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getMessageAfterRegister($storeId = null)
    {
        return $this->getConfigGeneral('message_after_register', $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getTypeNotApprove($storeId = null)
    {
        return $this->getConfigGeneral('type_not_approve', $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getErrorMessage($storeId = null)
    {
        return $this->getConfigGeneral('error_message', $storeId);
    }

    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getCmsRedirectPage($storeId = null)
    {
        return $this->getConfigGeneral('redirect_cms_page', $storeId);
    }

    /**
     * @param $path
     * @param $param
     *
     * @return string
     */
    public function getUrl($path, $param)
    {
        return $this->_getUrl($path, $param);
    }

    /**
     * @param $stringCode
     *
     * @return mixed
     */
    public function getRequestParam($stringCode)
    {
        return $this->_request->getParam($stringCode);
    }

    /**
     * Retrieve cookie manager
     *
     * @return     PhpCookieManager
     * @deprecated 100.1.0
     */
    private function getCookieManager()
    {
        if (!$this->cookieMetadataManager) {
            $this->cookieMetadataManager = ObjectManager::getInstance()->get(PhpCookieManager::class);
        }

        return $this->cookieMetadataManager;
    }

    /**
     * Retrieve cookie metadata factory
     *
     * @return     CookieMetadataFactory
     * @deprecated 100.1.0
     */
    private function getCookieMetadataFactory()
    {
        if (!$this->cookieMetadataFactory) {
            $this->cookieMetadataFactory = ObjectManager::getInstance()->get(CookieMetadataFactory::class);
        }

        return $this->cookieMetadataFactory;
    }

    /**
     * @param null $websiteId
     *
     * @return array|mixed
     */
    public function isEnabledForWebsite($websiteId = null)
    {
        return $this->getConfigValue(
            self::CONFIG_MODULE_PATH . '/general/enabled',
            $websiteId,
            ScopeInterface::SCOPE_WEBSITE
        );
    }

    /**
     * @param $typeApprove
     *
     * @return bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function shouldEnableButton($typeApprove)
    {
        if (!$this->getRequestParam('id')) {
            return false;
        }

        $customerId = $this->getRequestParam('id');
        $customer = $this->getCustomerById($customerId);
        $websiteId = $customer->getWebsiteId();

        if (!$this->isEnabledForWebsite($websiteId) || $this->getIsApproved($customerId) == $typeApprove) {
            return false;
        }

        return true;
    }

    /**
     * @param int $customerId
     *
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function setPendingCustomer($customerId)
    {
        $cusAttributeData = $this->getIsApproved($customerId);
        if (!$cusAttributeData) {
            $actionRegister = false;
            $this->setApprovePendingById($customerId, $actionRegister);
        }
    }

    /**
     * @return bool
     */
    public function hasCustomerEdit()
    {
        $param = $this->_request->getParams();

        return isset($param['customer']['is_active']);
    }

    /**
     * @throws InputException
     * @throws FailureToSendException
     */
    public function processCookieLogout()
    {
        if ($this->getCookieManager()->getCookie('mage-cache-sessid')) {
            $metadata = $this->getCookieMetadataFactory()->createCookieMetadata();
            $metadata->setPath('/');
            $this->getCookieManager()->deleteCookie('mage-cache-sessid', $metadata);
        }
    }

    public function erpCustomerCreation($customerId)
    {
        $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/after-account-creation.log');
        $logger = new \Zend\Log\Logger();
        $logger->addWriter($writer);
        $logger->info("erpCustomerCreation");
        try {
            $customer = $this->customerRepositoryInterface->getById($customerId);
            $logger->info("email - ".$customer->getEmail());
            /* create customer api call */
            list($status, $integrationData) = $this->clorasDDIHelper->isServiceEnabled('create_user');
            if ($status) {
                $responseData = $this->clorasDDIHelper->createCustomer($integrationData, $customer);
                if ($responseData != 0) {
                    $logger->info("Inside create user success response");
                    /* store account number and shipto number from CreateUser api response */
                    if ( true == array_key_exists('user', $responseData) && true == is_array($responseData['user']) && 0 < count($responseData['user'] ) ) {
                        $updateAddress = $this->addressRepository->getById($customer->getDefaultShipping());
                        $updateAddress->setCustomAttribute('ddi_ship_number', SELF::DEFAULT_SHIP_TO_ID);
                        $updateAddress->setCustomAttribute('erp_account_number', $responseData['user'][0]['accountNumber']);
                        $this->addressRepository->save($updateAddress);
	                    list($status, $integrationData) = $this->clorasDDIHelper->isServiceEnabled('validate_user');
	                    $users = $this->clorasDDIHelper->validateEcommUser($integrationData, $customer->getEmail());
	                    if (isset($users['user'][0]['token'])) {
		                    $this->coreCustomerSession->setEcommtoken(['ecomm_token' => $users['user'][0]['token']]);
	                    }
	                    $this->coreCustomerSession->setCustomData($users['user'][0]);
                    }

                    if (isset($responseData['isValid']) && $responseData['isValid'] == 'no' && isset($responseData['errorMessage']) && $responseData['errorMessage'] =='The user name you specified already exists') {
                        $customer->setCustomAttribute('is_b2b', true);
                        $this->customerRepositoryInterface->save($customer);
                    } else {
                        $customer->setCustomAttribute('is_b2b',false);
                        $this->customerRepositoryInterface->save($customer);
                    }
                    /*here set the customer token on customer session api*/
                    if (isset($responseData['token'])) {
                        $this->coreCustomerSession->setEcommtoken(['ecomm_token' => $responseData['token']]);
                    }

                    if(isset($responseData['user'][0])){
                        $this->coreCustomerSession->setCustomData($responseData['user'][0]);
                    }
                } else {
                    $logger->info("Inside create user failure");

                    $customer->setCustomAttribute('is_b2b',false);
                    $this->customerRepositoryInterface->save($customer);

                    /* store account number and shipto number from ValidateUser api response */
                    list($status, $integrationData) = $this->clorasDDIHelper->isServiceEnabled('validate_user');

                    if ($status) {
                        $users = $this->clorasDDIHelper->validateEcommUser($integrationData, $customer->getEmail());
                        if ( true == array_key_exists('user', $users ) && true == is_array($users['user']) && 0 < count($users['user'])) {
                            $updateAddress = $this->addressRepository->getById($customer->getDefaultShipping());
                            if (isset($users['user'][0]['shiptoId'])) {
                                $updateAddress->setCustomAttribute('ddi_ship_number', $users['user'][0]['shiptoId']);
                            } else {
                                $updateAddress->setCustomAttribute('ddi_ship_number', SELF::DEFAULT_SHIP_TO_ID);
                            }
                            $updateAddress->setCustomAttribute('erp_account_number', $users['user'][0]['accountNumber']);
                            $this->addressRepository->save($updateAddress);
	                        if (isset($users['user'][0]['token'])) {
		                        $this->coreCustomerSession->setEcommtoken(['ecomm_token' => $users['user'][0]['token']]);
	                        }
	                        $this->coreCustomerSession->setCustomData($users['user'][0]);
                        }
                    }
                }
            }

            /* Ship To Address save */
            list($status, $integrationData) = $this->clorasDDIHelper->isServiceEnabled('ship_to_insert');
            if ($status) {
                $logger->info('Shipto Insert done');
                $email = $customer->getEmail();
                $websiteId = $customer->getWebsiteId();
                $items = $this->clorasDDIHelper->shipToInsert($integrationData, $email, $websiteId);
                $logger->info('Shipto Insert done');
            }

            /* order approval insert */
            list($status, $integrationData) = $this->clorasDDIHelper->isServiceEnabled('order_approval_insert');
            if ($status) {
                $logger->info('Order Approval Insert start');
                $email = $customer->getEmail();
                $websiteId = $customer->getWebsiteId();
                $items = $this->clorasDDIHelper->orderApprovalInsert($integrationData, $email, $websiteId);
                $logger->info('Order Approval Insert done');
            }
        } catch (\Exception $e)  {
            $logger->info('Exception - '.$e->getMessage());
        }
        return true;
    }
}
