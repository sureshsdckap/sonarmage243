<?php
/**
 * @author Eric COURTIAL <e.courtial30@gmail.com>
 * Date: 01/08/2017
 * Time: 11:29
 */
namespace Dckap\AccountCreation\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Dckap\AccountCreation\Setup\InstallData;
use Psr\Log\LoggerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Message\ManagerInterface;
use Dckap\AccountCreation\Model\ActivationEmail;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\MailException;

class UserEdition implements ObserverInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Dckap\AccountCreation\Model\ActivationEmail
     */
    protected $activationEmail;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $connexion;

    /**
     * UserEdition constructor.
     *
     * @param  \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param  \Psr\Log\LoggerInterface                           $logger
     * @param  \Magento\Customer\Api\CustomerRepositoryInterface  $customerRepository
     * @param  \Magento\Framework\Message\ManagerInterface        $messageManager
     * @param  ActivationEmail                                    $activationEmail
     * @param  \Magento\Framework\App\ResourceConnection          $resourceConnection
     * @throws \DomainException
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        CustomerRepositoryInterface $customerRepository,
        ManagerInterface $messageManager,
        ActivationEmail $activationEmail,
        ResourceConnection $resourceConnection
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
        $this->customerRepository = $customerRepository;
        $this->messageManager = $messageManager;
        $this->activationEmail = $activationEmail;
        $this->connexion = $resourceConnection->getConnection();
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\State\InputMismatchException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \RuntimeException
     */
    public function execute(EventObserver $observer)
    {
        $flag = '0';
        $customer = $observer->getEvent()->getCustomer();
        $customerId = $customer->getId();
        $currentCustomer = $this->customerRepository->getById($customerId);
        $preValue = $currentCustomer->getCustomAttributes();
        $request = $observer->getEvent()->getRequest();
        $params = $request->getParams();
        if (isset($preValue['account_is_active'])) {
            $preValue =  $currentCustomer->getCustomAttributes()['account_is_active']->getValue();
            $postValue = $params['customer']['account_is_active'];
            if ($preValue != $postValue) {
                $flag = '1';
            }

            /**
 * @var \Magento\Customer\Api\Data\CustomerInterface $customer
*/
            // At customer account update (in adminhtml), if the account is active
            // but the email has not been sent: send it to the customer to notice it
            if ($this->scopeConfig->getValue(
                'customer/create_account/customer_account_activation',
                ScopeInterface::SCOPE_STORE,
                $customer->getStoreId()
            )
                && $flag == '1'
                && $customer->getCustomAttribute(InstallData::CUSTOMER_ACCOUNT_ACTIVE)->getValue() === '1'
            ) {
                $this->manageUserActivationEmail($customer);
            }
        }
    }
    /**
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     */
    protected function manageUserActivationEmail($customer)
    {
        $this->connexion->beginTransaction();
        $blnStatus = true;

        try {
            $this->sendEmail($customer);
        } catch (CouldNotSaveException $ex) {
            $this->messageManager->addErrorMessage("Impossible to update user, email has not been sent");
            $blnStatus = false;
        } catch (MailException $e) {
            $this->messageManager->addErrorMessage(
                "Impossible to send the email. Please try to deactivate then reactive the user again"
            );
            $blnStatus = false;
        }

        if ($blnStatus) {
            $this->connexion->commit();
        } else {
            $this->connexion->rollBack();
        }
    }
    /**
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @throws \Magento\Framework\Exception\MailException
     */
    protected function sendEmail($customer)
    {
        try {
            $this->activationEmail->send($customer);
        } catch (MailException $e) {
            $this->messageManager->addErrorMessage(
                "Impossible to send the email."
            );
        }
    }
}
