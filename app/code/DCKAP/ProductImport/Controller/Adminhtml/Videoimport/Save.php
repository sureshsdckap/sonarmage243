<?php
namespace Dckap\ProductImport\Controller\Adminhtml\Videoimport;

use Magento\Framework\App\Filesystem\DirectoryList;

class Save extends \Magento\Backend\App\Action
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
    ) {
        $this->_filesystem = $filesystem;
        $this->_storeManager = $storeManager;
        $this->csvProcessor = $csvProcessor;
        $this->uploaderFactory = $uploaderFactory;
        $this->importHelper = $importHelper;
        parent::__construct($context);
    }

    public function execute()
    {
        $postData = $this->getRequest()->getPostValue();
       $resultRedirect = $this->resultRedirectFactory->create();
       try {
         $tmpfilename = $_FILES['video_csv']['tmp_name'];
        if (!isset($tmpfilename)) 
        throw new \Magento\Framework\Exception\LocalizedException(__('Invalid file upload attempt.'));

     $csvData = $this->csvProcessor->getData($tmpfilename);
       $count = 0;
           $rootDirectory = $this->_filesystem->getDirectoryRead(DirectoryList::ROOT)->getAbsolutePath();
         foreach ($csvData as $rowIndex => $dataRow) 
                {
                    if($rowIndex > 0) 
                    {
                        $thumbnail = $rootDirectory. $postData['thumb_dir'].'/'.$dataRow[4];
                        $data = array(
                            'sku' => $dataRow[0],
                            'video_id' => $dataRow[1],
                            'video_title' => $dataRow[2],
                            'video_description' => $dataRow[3],
                            'file' => $thumbnail,
                            'video_provider' => $dataRow[5],
                            'video_metadata' => $dataRow[6],
                            'video_url' => $dataRow[7],
                        );
                        $this->importHelper->importProductVideos($data);
                    }
                }
                $this->messageManager->addSuccess(__('Videos imported successfully.', $count));
            }
            
         catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
        }

        return $resultRedirect->setPath('*/videoimport/index');
    }
    }

    
