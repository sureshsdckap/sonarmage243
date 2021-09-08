<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Coupons
 */


namespace Amasty\Coupons\Plugin\SalesRule\Model;

use Amasty\Coupons\Api\Data\RuleInterface;

class DataProviderPlugin
{
    /**
     * @param \Magento\SalesRule\Model\Rule\DataProvider $subject
     * @param array $result
     *
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetData(\Magento\SalesRule\Model\Rule\DataProvider $subject, $result)
    {
        if (is_array($result)) {
            foreach ($result as &$item) {
                if (isset($item[RuleInterface::EXTENSION_ATTRIBUTES_KEY][RuleInterface::EXTENSION_CODE])
                    && $item[RuleInterface::EXTENSION_ATTRIBUTES_KEY][RuleInterface::EXTENSION_CODE] instanceof
                    RuleInterface && $this->isRuleExist($item)
                ) {
                    $item[RuleInterface::EXTENSION_ATTRIBUTES_KEY][RuleInterface::EXTENSION_CODE] =
                        $item[RuleInterface::EXTENSION_ATTRIBUTES_KEY][RuleInterface::EXTENSION_CODE]->toArray();
                }
            }
        }

        return $result;
    }

    /**
     * @param array $item
     *
     * @return bool
     */
    private function isRuleExist($item)
    {
        return !empty($item[RuleInterface::EXTENSION_ATTRIBUTES_KEY][RuleInterface::EXTENSION_CODE]->getData());
    }
}
