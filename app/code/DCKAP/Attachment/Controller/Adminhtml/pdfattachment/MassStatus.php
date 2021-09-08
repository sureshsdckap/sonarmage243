<?php
namespace Dckap\Attachment\Controller\Adminhtml\pdfattachment;

use Magento\Backend\App\Action;

class MassStatus extends \Magento\Backend\App\Action
{
    /**
     * Update blog post(s) status action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     * @throws \Magento\Framework\Exception\LocalizedException|\Exception
     */
    protected $pdfSection;
    protected $session;

    /**
     * @param Action\Context $context
     */
    public function __construct(
        Action\Context $context,
        \Dckap\Attachment\Model\Pdfattachment $pdfAttachment
    ){
        parent::__construct($context);
        $this->pdfAttachment = $pdfAttachment;
    }

    public function execute()
    {
        $itemIds = $this->getRequest()->getParam('pdfattachment');
        if (!is_array($itemIds) || empty($itemIds)) {
            $this->messageManager->addError(__('Please select item(s).'));
        } else {
            try {
                $status = (int) $this->getRequest()->getParam('status');
                foreach ($itemIds as $postId) {
                    $this->setActiveById($postId,$status);
                    }
                $this->messageManager->addSuccess(
                    __('A total of %1 record(s) have been updated.', count($itemIds))
                );
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
        }
        return $this->resultRedirectFactory->create()->setPath('attachment/*/index');
    }

    public function setActiveById($postId,$status){
        $post = $this->pdfAttachment->load($postId);
        $post->setIsActive($status)->save();
    }
}
