<?php
namespace Dckap\Attachment\Model;

class Pdfsection extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Dckap\Attachment\Model\ResourceModel\Pdfsection');
    }
}
