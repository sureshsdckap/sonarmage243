<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Coupons
 */


declare(strict_types=1);

namespace Amasty\Coupons\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * @api
 */
interface DiscountBreakdownLineInterface extends ExtensibleDataInterface
{
    /**
     * Constants used as key into $_data
     */
    const RULE_ID = 'rule_id';
    const RULE_NAME = 'rule_name';
    const RULE_AMOUNT = 'rule_amount';

    /**
     * @return string|null
     */
    public function getRuleName(): ?string;

    /**
     * @param string $ruleName
     * @return void
     */
    public function setRuleName(string $ruleName): void;

    /**
     * @return string
     */
    public function getRuleAmount(): string;

    /**
     * @param string $ruleAmount
     * @return void
     */
    public function setRuleAmount(string $ruleAmount): void;
}
