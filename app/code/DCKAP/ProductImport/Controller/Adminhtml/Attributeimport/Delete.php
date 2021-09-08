<?php

namespace Dckap\ProductImport\Controller\Adminhtml\Attributeimport;

class Delete extends \Magento\Backend\App\Action
{

    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $_filesystem;

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * CSV Processor
     *
     * @var \Magento\Framework\File\Csv
     */
    protected $csvProcessor;
    protected $uploaderFactory;
    protected $importHelper;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory,
        \Magento\Framework\File\Csv $csvProcessor,
        \Dckap\ProductImport\Helper\Data $importHelper
    )
    {
        $this->_filesystem = $filesystem;
        $this->_storeManager = $storeManager;
        $this->csvProcessor = $csvProcessor;
        $this->uploaderFactory = $uploaderFactory;
        $this->importHelper = $importHelper;
        parent::__construct($context);
    }

    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        $resultRedirect = $this->resultRedirectFactory->create();
        try {
            $tmpfilename = $_FILES['attribute_csv']['tmp_name'];
            if (!isset($tmpfilename))
                throw new \Magento\Framework\Exception\LocalizedException(__('Invalid file upload attempt.'));
            $csvData = $this->csvProcessor->getData($tmpfilename);
            foreach ($csvData as $rowIndex => $dataRow) {
                if ($rowIndex > 0) {
                    $this->importHelper->removeProductAttribute($dataRow[0]);
                }
            }
            $this->messageManager->addSuccess(__('Attributes removed successfully.'));
        } catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
        }
        return $resultRedirect->setPath('*/attributeimport/index');
    }
}

    
