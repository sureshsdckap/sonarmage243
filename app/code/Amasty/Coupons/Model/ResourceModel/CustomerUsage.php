<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Coupons
 */


declare(strict_types=1);

namespace Amasty\Coupons\Model\ResourceModel;

class CustomerUsage extends \Magento\SalesRule\Model\ResourceModel\Coupon\Usage
{
    /**
     * @param int $customerId
     * @param int[] $couponIds
     *
     * @return int[] array({coupon_id} => {times_used})
     */
    public function getCouponsCounterForCustomer(int $customerId, array $couponIds): array
    {
        $connection = $this->getConnection();

        $select = $connection->select()
            ->from($this->getMainTable(), ['coupon_id', 'times_used'])
            ->where('customer_id =:customer_id')
            ->where('coupon_id IN (?)', $couponIds);

        return $connection->fetchPairs($select, [':customer_id' => $customerId]);
    }
}
