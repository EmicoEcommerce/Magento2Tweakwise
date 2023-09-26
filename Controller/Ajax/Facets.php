<?php

namespace Tweakwise\Magento2Tweakwise\Controller\Ajax;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Tweakwise\Magento2Tweakwise\Exception\ApiException;
use Tweakwise\Magento2Tweakwise\Model\Client;
use Tweakwise\Magento2Tweakwise\Model\Client\RequestFactory;

/**
 * Class Navigation
 * Handles ajax filtering requests for category pages
 * @package Tweakwise\Magento2Tweakwise\Controller\Ajax
 */
class Facets extends Action
{
    /**
     * @var JsonFactory
     */
    private JsonFactory $jsonFactory;

    /**
     * @var Client
     */
    private Client $client;

    /**
     * @var RequestFactory
     */
    private RequestFactory $requestFactory;

    public function __construct(Context $context, JsonFactory $jsonFactory, RequestFactory $requestFactory, Client $client)
    {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->requestFactory = $requestFactory;
        $this->client = $client;
    }

    public function execute()
    {
        $json = $this->resultFactory->create('json');
        $facetRequest = $this->requestFactory->create();

        $categoryId = $this->getRequest()->getParam('category');
        $facetRequest->addCategoryFilter($categoryId);

        $response = $this->client->request($facetRequest);
        $result = [];
        foreach ($response->getFacets() as $facet) {
            $result[] = ['value' => $facet->getFacetSettings()->getUrlKey(), 'label' => $facet->getFacetSettings()->getTitle()];
        }

        $json->setData(['data' => $result]);
        return $json;
    }
}
