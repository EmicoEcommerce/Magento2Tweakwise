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
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultInterface;

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
     * @return ResponseInterface|Json|ResultInterface
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        if ($this->config->isAnalyticsEnabled()) {
            $request = $this->getRequest();
            $type = $request->getParam('type');
            $value = $request->getParam('value');
            $profileKey = $this->config->getProfileKey();

            //hyva theme
            if (empty($type)) {
                $contentDecoded = json_decode($request->getContent(), true);
                $type = isset($contentDecoded['type']) ? $contentDecoded['type'] : $type;
                $value = isset($contentDecoded['value']) ? $contentDecoded['value'] : $value;
            }

            $tweakwiseRequest = $this->requestFactory->create();
            $tweakwiseRequest->setProfileKey($profileKey);

            if ($type === 'product') {
                $tweakwiseRequest->setParameter('productKey', $value);
                $tweakwiseRequest->setPath('pageview');
            } elseif ($type === 'search') {
                $tweakwiseRequest->setParameter('searchTerm', $value);
                $tweakwiseRequest->setPath('search');
            }

            if (!empty($tweakwiseRequest->getPath())) {
                try {
                    $this->client->request($tweakwiseRequest);
                    $result->setData(['success' => true]);
                } catch (\Exception $e) {
                    $result->setData(
                        [
                            'success' => false,
                            'message' => $e->getMessage()
                        ]
                    );
                }
            }
        }

        return $result;
    }
}
