<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Coupons
 */


namespace Amasty\Coupons\Model\ResourceModel\Rule;

use Amasty\Coupons\Model\Rule;
use Amasty\Coupons\Model\ResourceModel\Rule as ResourceRule;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init(Rule::class, ResourceRule::class);
    }
}
