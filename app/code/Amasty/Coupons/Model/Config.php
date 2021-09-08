<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Coupons
 */


namespace Amasty\Coupons\Model;

use Amasty\Base\Model\ConfigProviderAbstract;

/**
 * Module Config Provider
 */
class Config extends ConfigProviderAbstract
{
    /**
     * xpath prefix of module (section)
     * @var string '{section}/'
     */
    protected $pathPrefix = 'amcoupons/';

    const UNIQUE_COUPONS = 'general/unique_codes';
    const ALLOW_SAME_RULE = 'general/allow_same_rule';

    /**
     * @return string
     */
    public function getUniqueCoupons(): string
    {
        return (string)$this->getValue(self::UNIQUE_COUPONS);
    }

    /**
     * @return bool
     */
    public function isAllowCouponsSameRule(): bool
    {
        return $this->isSetFlag(self::ALLOW_SAME_RULE);
    }
}
