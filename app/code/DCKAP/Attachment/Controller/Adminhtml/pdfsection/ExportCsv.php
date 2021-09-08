<?php

namespace Dckap\Attachment\Controller\Adminhtml\pdfsection;

use Magento\Backend\App\Action;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;

class ExportCsv extends \Magento\Backend\App\Action
{
    protected $fileFactory;
    public function __construct(
        Action\Context $context,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory
      ) {
        $this->fileFactory = $fileFactory;
        parent::__construct($context);
    }
    public function execute()
    {
        $this->_view->loadLayout(false);

        $fileName = 'pdfsection.csv';

        $exportBlock = $this->_view->getLayout()->createBlock('Dckap\Attachment\Block\Adminhtml\Pdfsection\Grid');

        return $this->fileFactory->create(
            $fileName,
            $exportBlock->getCsvFile(),
            DirectoryList::VAR_DIR
        );
    }
}
