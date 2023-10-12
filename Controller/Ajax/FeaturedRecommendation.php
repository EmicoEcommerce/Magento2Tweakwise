<?php

namespace Tweakwise\Magento2Tweakwise\Controller\Ajax;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Tweakwise\Magento2Tweakwise\Exception\ApiException;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Product\Recommendation\Context as RecommendationsContext;
use Tweakwise\Magento2Tweakwise\Model\Client;
use Tweakwise\Magento2Tweakwise\Model\Client\RequestFactory;
use Tweakwise\Magento2Tweakwise\Model\Client\RequestFactory\Recommendations\FeaturedRequest;

/**
 * Class Navigation
 * Handles ajax filtering requests for category pages
 * @package Tweakwise\Magento2Tweakwise\Controller\Ajax
 */
class FeaturedRecommendation extends Action
{
    /**
     * @var RecommendationsContext
     */
    protected $recommendationsContext;

    /**
     * @var JsonFactory
     */
    private JsonFactory $jsonFactory;

    public function __construct(Context $context, JsonFactory $jsonFactory, RecommendationsContext $recommendationsContext)
    {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->recommendationsContext = $recommendationsContext;
    }

    public function execute()
    {
        $json = $this->resultFactory->create('json');

        $this->recommendationsContext->getRequest();
        $this->recommendationsContext->configureRequest();

        $result = [];
        try {
            $response = $this->recommendationsContext->getResponse();
            $result = $response->getItemsData();
            $json->setData(['data' => $result]);
            return $json;
        } catch (ApiException $e) {
            if (!$e->getCode() == 404) {
                throw $e;
            }
        }
    }
}
