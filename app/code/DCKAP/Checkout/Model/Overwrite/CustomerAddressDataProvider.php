<?php

namespace Dckap\Checkout\Model\Overwrite;

/**
 * Class CustomerAddressDataProvider
 * @package Dckap\Checkout\Model\Overwrite
 */
class CustomerAddressDataProvider
{
    /**
     * @var \Magento\Customer\Model\SessionFactory
     */
    protected $session;
    protected $_orderApprovalHelper;

    /**
     * CustomerAddressDataProvider constructor.
     * @param \Magento\Customer\Model\SessionFactory $session
     */
    public function __construct(
        \Magento\Customer\Model\SessionFactory $session,
        \DCKAP\OrderApproval\Helper\Data $orderApprovalHelper
    ) {
        $this->session = $session;
        $this->_orderApprovalHelper = $orderApprovalHelper;
    }

    /**
     * @param \Magento\Customer\Model\Address\CustomerAddressDataProvider $subject
     * @param $result
     * @return mixed
     */
    public function afterGetAddressDataByCustomer(\Magento\Customer\Model\Address\CustomerAddressDataProvider $subject, $result)
    {
        $customerSession = $this->session->create();
        $customerSessionData = $customerSession->getCustomData();
        $accountNumber = $customerSessionData['accountNumber'];
        $addressId = $this->_orderApprovalHelper->getExistingShipto();
        if ($result && !empty($result)) {
            foreach ($result as $key => $address) {
                if (isset($address['custom_attributes'])) {
                    if (isset($address['custom_attributes']['erp_account_number']) && $address['custom_attributes']['erp_account_number']['value'] != $accountNumber) {
                        unset($result[$key]);
                    }
                }
                /**
                 * Setup existing address as first address in the list
                 * It needs to be work only on checkout page - need to add condition in future
                 */

                if ($addressId && isset($result[$addressId])) {
                    $new_value = $result[$addressId];
                    unset($result[$addressId]);
                    array_unshift($result, $new_value);
                }

                if (isset($result[$key])) {
                    if ($address['default_shipping'] == 1) {
                        $new_value = $result[$key];
                        unset($result[$key]);
                        array_unshift($result, $new_value);
                    }
                }
            }
        }
        return $result;
    }
}
