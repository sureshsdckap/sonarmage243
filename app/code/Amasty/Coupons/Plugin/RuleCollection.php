<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Coupons
 */


declare(strict_types=1);

namespace Amasty\Coupons\Plugin;

use Amasty\Coupons\Model\CouponRenderer;
use Amasty\Coupons\Model\SalesRule\CouponListProvider;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Quote\Model\Quote\Address;
use Magento\Rule\Model\ResourceModel\Rule\Collection\AbstractCollection;
use Magento\SalesRule\Model\ResourceModel\Rule\Collection;

/**
 * Plugin override coupon filter for Sales Rules for filter on multiple coupons
 */
class RuleCollection
{
    /**
     * @var CouponRenderer
     */
    private $couponRenderer;

    /**
     * @var CouponListProvider
     */
    private $couponListProvider;

    public function __construct(
        CouponRenderer $couponRenderer,
        CouponListProvider $couponListProvider
    ) {
        $this->couponRenderer = $couponRenderer;
        $this->couponListProvider = $couponListProvider;
    }

    /**
     * @param Collection $subject
     * @param int $websiteId
     * @param int $customerGroupId
     * @param string $couponCode
     * @param string|null $now
     * @param Address $address
     *
     * @return array|null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSetValidationFilter(
        Collection $subject,
        $websiteId,
        $customerGroupId,
        $couponCode = '',
        $now = null,
        $address = null
    ) {
        if (!is_string($couponCode) || strpos($couponCode, ',') === false) {
            return null;
        }

        $coupons = $this->couponRenderer->render($couponCode);
        if (count($coupons) === 1) {
            return [$websiteId, $customerGroupId, current($coupons), $now, $address];
        }

        return null;
    }

    /**
     * @param Collection $subject
     * @param Collection $result
     * @param int $websiteId
     * @param int $customerGroupId
     * @param string $couponCode
     *
     * @return Collection
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSetValidationFilter(
        Collection $subject,
        $result,
        $websiteId,
        $customerGroupId,
        $couponCode = ''
    ) {
        if (!is_string($couponCode) || strpos($couponCode, ',') === false) {
            return $result;
        }

        $coupons = $this->couponRenderer->render($couponCode);
        if (empty($coupons)) {
            return $result;
        }

        $select = $result->getSelect();

        if (empty($select->getPart(\Zend_Db_Select::WHERE))) {
            $this->modifyUnionCouponCondition($result, $couponCode, $coupons);
        } else {
            $this->modifyRuleIds($result, $coupons);
        }

        return $result;
    }

    /**
     * Magento 2.3.2+ Compatibility.
     * Modify join condition to be able to search for multiple coupons
     *
     * @param Collection|AbstractDb $collection
     * @param string $originCode
     * @param string[] $renderedCoupons
     */
    private function modifyUnionCouponCondition(
        AbstractDb $collection,
        string $originCode,
        array $renderedCoupons
    ): void {
        $connection = $collection->getConnection();
        $select = $collection->getSelect();

        $searchCode = $connection->quoteInto(
            'code = ?',
            $originCode
        );
        $replaceCodeIn = $connection->quoteInto(
            'code IN (?)',
            $renderedCoupons
        );

        $unionPart = $select->getPart(\Zend_Db_Select::FROM)['t']['tableName']
             ->getPart(\Zend_Db_Select::UNION)[1][0];

        $fromPart = $unionPart->getPart(\Zend_Db_Select::FROM);
        $fromPart['rule_coupons']['joinCondition'] = str_ireplace(
            $searchCode,
            $replaceCodeIn,
            $fromPart['rule_coupons']['joinCondition']
        );
        $unionPart->setPart(\Zend_Db_Select::FROM, $fromPart);

        $select->group('rule_id');
    }

    /**
     * Magneto 2.3.0 - 2.3.1 compatibility.
     * Replace rule IDs with coupons
     *
     * @param Collection|AbstractDb $collection
     * @param array $renderedCoupons
     */
    private function modifyRuleIds(AbstractDb $collection, array $renderedCoupons): void
    {
        $connection = $collection->getConnection();
        $select = $collection->getSelect();
        $wherePart = $select->getPart(\Zend_Db_Select::WHERE);

        $ruleIds = [];
        foreach ($this->couponListProvider->getItemsByCodes($renderedCoupons) as $couponModel) {
            $ruleIds[] = $couponModel->getRuleId();
        }

        $searchRuleId = 'rule_id IN (NULL)';
        $replaceRuleIdIN = $connection->quoteInto(
            'rule_id IN (?)',
            $ruleIds
        );

        foreach ($wherePart as &$where) {
            if (stripos($where, $searchRuleId) !== false) {
                $where = str_ireplace($searchRuleId, $replaceRuleIdIN, $where);
            }
        }

        $select->setPart(\Zend_Db_Select::WHERE, $wherePart);
    }
}
