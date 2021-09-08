<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2020 Amasty (https://www.amasty.com)
 * @package Amasty_Coupons
 */


declare(strict_types=1);

namespace Amasty\Coupons\Block\Adminhtml;

/**
 * Button without html classes
 */
class FormButton extends \Magento\Backend\Block\Widget\Button
{
    /**
     * Prepare attributes
     *
     * @param string $title
     * @param array $classes
     * @param string $disabled
     * @return array
     */
    protected function _prepareAttributes($title, $classes, $disabled)
    {
        foreach ($classes as $key => $class) {
            if ($class === 'action-default' || $class === 'scalable') {
                unset($classes[$key]);
            }
        }

        return parent::_prepareAttributes($title, $classes, $disabled);
    }
}
