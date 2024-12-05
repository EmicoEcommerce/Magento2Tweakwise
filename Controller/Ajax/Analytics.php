<?php

namespace Tweakwise\Magento2Tweakwise\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\HTTP\Client\Curl;

class Analytics extends Action
{
    protected $resultJsonFactory;
    protected $curl;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Curl $curl
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        $this->curl = $curl;
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $type = $this->getRequest()->getParam('type');

        if ($type === 'product') {
            $productKey = $this->getRequest()->getParam('productKey');
        }

        try {

            $response = $this->curl->getBody();
            $result->setData(['success' => true, 'response' => $response]);
        } catch (\Exception $e) {
            $result->setData(['success' => false, 'message' => $e->getMessage()]);
        }

        return $result;
    }
}
