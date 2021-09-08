<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Coupons
 */


namespace Amasty\Coupons\Model;

use Amasty\Coupons\Api\Data\RuleInterface;

/**
 * Object of Amasty Coupons.
 */
class Rule extends \Magento\Framework\Model\AbstractModel implements RuleInterface
{
    /**
     * Set resource model and Id field name
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();

        $this->_init(\Amasty\Coupons\Model\ResourceModel\Rule::class);
        $this->setIdFieldName(RuleInterface::ENTITY_ID);
    }

    /**
     * @inheritdoc
     */
    public function getEntityId()
    {
        return $this->_getData(RuleInterface::ENTITY_ID);
    }

    /**
     * @inheritdoc
     */
    public function setEntityId($entityId)
    {
        $this->setData(RuleInterface::ENTITY_ID, $entityId);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getRuleId()
    {
        return $this->_getData(RuleInterface::KEY_SALESRULE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setRuleId($ruleId)
    {
        $this->setData(RuleInterface::KEY_SALESRULE_ID, $ruleId);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getAllowCouponsSameRule()
    {
        return $this->_getData(RuleInterface::ALLOW_COUPONS_SAME_RULE);
    }

    /**
     * @inheritdoc
     */
    public function setAllowCouponsSameRule($allowCouponsSameRule)
    {
        $this->setData(RuleInterface::ALLOW_COUPONS_SAME_RULE, $allowCouponsSameRule);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getUseConfigValue()
    {
        return $this->_getData(RuleInterface::USE_CONFIG_VALUE);
    }

    /**
     * @inheritdoc
     */
    public function setUseConfigValue($useConfigValue)
    {
        $this->setData(RuleInterface::USE_CONFIG_VALUE, $useConfigValue);

        return $this;
    }
}
