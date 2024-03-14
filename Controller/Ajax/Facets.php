<?php

namespace Tweakwise\Magento2Tweakwise\Controller\Ajax;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
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
        $result = [];
        $json = $this->resultFactory->create('json');
        $facetRequest = $this->requestFactory->create();

        $categoryId = $this->getRequest()->getParam('category');
        $filtertemplate = (int) $this->getRequest()->getParam('filtertemplate');
        $allStores = $facetRequest->getStores();

        if (!empty($filtertemplate)) {
            $facetRequest->addParameter('tn_ft', $filtertemplate);
        }

        foreach ($allStores as $store) {
            $facetRequest->setStore($store->getId());
            if (!empty($categoryId)) {
                $facetRequest->addCategoryFilter($categoryId);
            }

            $response = $this->client->request($facetRequest);

            foreach ($response->getFacets() as $facet) {
                $result[] = ['value' => $facet->getFacetSettings()->getUrlKey(), 'label' => $facet->getFacetSettings()->getTitle()];
            }
        }

        $result[] = ['value' => 'tw_other', 'label' => 'Other (text field)'];

        $result = array_unique($result, SORT_REGULAR);

        //prevent non sequential array keys. That causes json encode to act differently and creates objects instead of arrays
        $result = array_values($result);

        $json->setData(['data' => $result]);
        return $json;
    }
}
