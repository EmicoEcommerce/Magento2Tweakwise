<?php

namespace Tweakwise\Magento2Tweakwise\Controller\Ajax;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Tweakwise\Magento2Tweakwise\Model\Client;
use Tweakwise\Magento2Tweakwise\Model\PersonalMerchandisingConfig;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Tweakwise\Magento2Tweakwise\Model\Client\RequestFactory;

class Analytics extends Action
{
    /**
     * Constructor.
     *
     * @param Context                     $context
     * @param JsonFactory                 $resultJsonFactory
     * @param Client                      $client
     * @param PersonalMerchandisingConfig $config
     * @param RequestFactory              $requestFactory
     */
    public function __construct(
        private Context $context,
        private JsonFactory $resultJsonFactory,
        private Client $client,
        private PersonalMerchandisingConfig $config,
        private RequestFactory $requestFactory
    ) {
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Json|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        if ($this->config->isAnalyticsEnabled()) {
            $type = $this->getRequest()->getParam('type');
            $profileKey = $this->config->getProfileKey();

            $tweakwiseRequest = $this->requestFactory->create();
            $tweakwiseRequest->setProfileKey($profileKey);

            if ($type === 'product') {
                $productKey = $this->getRequest()->getParam('productKey');
                $tweakwiseRequest->setParameter('productKey', $productKey);
                $tweakwiseRequest->setPath('pageview');
            } elseif ($type === 'search') {
                $searchTerm = $this->getRequest()->getParam('searchTerm');
                $tweakwiseRequest->setParameter('searchTerm', $searchTerm);
                $tweakwiseRequest->setPath('search');
            }


            if (!empty($tweakwiseRequest->getPath())) {
                try {
                    $this->client->request($tweakwiseRequest);
                    $result->setData(['success' => true]);
                } catch (\Exception $e) {
                    $result->setData([
                        'success' => false,
                        'message' => $e->getMessage()
                    ]);
                }
            }
        }

        return $result;
    }
}
