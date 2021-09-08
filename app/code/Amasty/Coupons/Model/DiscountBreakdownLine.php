<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Coupons
 */


declare(strict_types=1);

namespace Amasty\Coupons\Model;

use Amasty\Coupons\Api\Data\DiscountBreakdownLineInterface;
use Magento\Framework\Api\AbstractSimpleObject;

class DiscountBreakdownLine extends AbstractSimpleObject implements DiscountBreakdownLineInterface
{
    /**
     * @return int
     */
    public function getRuleId(): int
    {
        return (int)$this->_get(self::RULE_ID);
    }

    /**
     * @param int $ruleId
     * @return void
     */
    public function setRuleId(int $ruleId): void
    {
        $this->setData(self::RULE_ID, $ruleId);
    }

    /**
     * @return string|null
     */
    public function getRuleName(): ?string
    {
        return $this->_get(self::RULE_NAME);
    }

    /**
     * @param string $ruleName
     * @return void
     */
    public function setRuleName(string $ruleName): void
    {
        $this->setData(self::RULE_NAME, $ruleName);
    }

    /**
     * @return string
     */
    public function getRuleAmount(): string
    {
        return $this->_get(self::RULE_AMOUNT);
    }

    /**
     * @param string $ruleAmount
     * @return void
     */
    public function setRuleAmount(string $ruleAmount): void
    {
        $this->setData(self::RULE_AMOUNT, $ruleAmount);
    }
}
