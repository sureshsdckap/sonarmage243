<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Coupons
 */


namespace Amasty\Coupons\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * Additional Sales Rule Data
 */
interface RuleInterface extends ExtensibleDataInterface
{
    /**#@+
     * Constants defined for keys of data array
     */
    const EXTENSION_CODE = 'amcoupons';
    const ENTITY_ID = 'entity_id';
    const KEY_SALESRULE_ID = 'rule_id';
    const ALLOW_COUPONS_SAME_RULE = 'allow_coupons_same_rule';
    const USE_CONFIG_VALUE = 'use_config_value';
    /**#@-*/

    /**
     * @return int
     */
    public function getEntityId();

    /**
     * @param int $entityId
     *
     * @return \Amasty\Coupons\Api\Data\RuleInterface
     */
    public function setEntityId($entityId);

    /**
     * @return int
     */
    public function getRuleId();

    /**
     * @param int $ruleId
     *
     * @return \Amasty\Coupons\Api\Data\RuleInterface
     */
    public function setRuleId($ruleId);

    /**
     * @return bool
     */
    public function getAllowCouponsSameRule();

    /**
     * @param bool $allowCouponsSameRule
     *
     * @return \Amasty\Coupons\Api\Data\RuleInterface
     */
    public function setAllowCouponsSameRule($allowCouponsSameRule);

    /**
     * @return int
     */
    public function getUseConfigValue();

    /**
     * @param int $useConfigValue
     *
     * @return \Amasty\Coupons\Api\Data\RuleInterface
     */
    public function setUseConfigValue($useConfigValue);
}
