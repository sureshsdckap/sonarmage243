<?php

namespace DCKAP\Extension\Model;

use \Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Option\ArrayInterface;
use \Magento\Payment\Model\Config;

class Payment extends DataObject implements ArrayInterface
{
    /**
     * @var ScopeConfigInterface
     */
    protected $_appConfigScopeConfigInterface;
    /**
     * @var Config
     */
    protected $_paymentModelConfig;

    /**
     * @param ScopeConfigInterface $appConfigScopeConfigInterface
     * @param Config               $paymentModelConfig
     */
    public function __construct(
        ScopeConfigInterface $appConfigScopeConfigInterface,
        Config $paymentModelConfig
    ) {

        $this->_appConfigScopeConfigInterface = $appConfigScopeConfigInterface;
        $this->_paymentModelConfig = $paymentModelConfig;
    }

    public function toOptionArray()
    {
        $payments = $this->_paymentModelConfig->getActiveMethods();
        $methods = [];
        $methods[0] = [
            'label' => 'Select an option',
            'value' => ''
        ];
        foreach ($payments as $paymentCode => $paymentModel) {
            if ($paymentCode == 'anet_creditcard' || $paymentCode == 'authorizenet_acceptjs' ||
                $paymentCode == 'elementpayment')
            {
                $paymentTitle = $this->_appConfigScopeConfigInterface
                    ->getValue('payment/' . $paymentCode . '/title');
                $methods[$paymentCode] = [
                    'label' => $paymentTitle,
                    'value' => $paymentCode
                ];
            }
        }
        return $methods;
    }
}
