<?php

namespace Dckap\ProductImport\Controller\Adminhtml\Attributeimport;

use Magento\Backend\App\Action;

class SampleCsv extends \Magento\Backend\App\Action
{

    public function __construct(
        Action\Context $context
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $outputFile = "pub/media/sample-attribute-import.csv";
        $this->downloadCsv($outputFile);
    }
    public function downloadCsv($file)
    {
        if (file_exists($file)) {
            //set appropriate headers
            header('Content-Description: File Transfer');
            header('Content-Type: application/csv');
            header('Content-Disposition: attachment; filename='.basename($file));
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));
            ob_clean();flush();
            readfile($file);
        }
    }
}