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
 */
class FacetAttributes extends Action
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

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        RequestFactory $requestFactory,
        Client $client
    ) {
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
        $facetKey = $this->getRequest()->getParam('facetkey');
        $filtertemplate = (int)$this->getRequest()->getParam('filtertemplate');
        $allStores = $facetRequest->getStores();

        if (!empty($facetKey)) {
            $facetRequest->addFacetKey($facetKey);
        }

        if (!empty($filtertemplate)) {
            $facetRequest->addParameter('tn_ft', $filtertemplate);
        }

        $result = [];
        foreach ($allStores as $store) {
            $facetRequest->setStore($store->getId());
            if (!empty($categoryId)) {
                $facetRequest->addCategoryFilter($categoryId);
            }

            try {
                $response = $this->client->request($facetRequest);
            } catch (ApiException $e) {
                if (!$e->getCode() == 404) {
                    throw $e;
                }

                continue;
            }

            if (!empty($response->getAttributes())) {
                foreach ($response->getAttributes() as $attribute) {
                    $result[] = ['value' => $attribute['title'], 'label' => $attribute['title']];
                }
            }
        }

        $result[] = ['value' => 'tw_other', 'label' => 'Other (text field)'];

        //prevent duplicate keys
        $values = array_column($result, 'value');
        $uniqueValues = array_unique($values);
        $uniqueKeys = array_keys($uniqueValues);
        $result = array_intersect_key($result, array_flip($uniqueKeys));

        //prevent non sequential array keys. That causes json encode to act differently and creates objects instead of arrays
        $result = array_values($result);

        //set access control headers, for when the admin is on another domain
        $json->setHeader('Access-Control-Allow-Origin', '*');
        $json->setHeader(
            'Access-Control-Allow-Headers',
            'Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With'
        );
        $json->setData(['data' => $result]);

        return $json;
    }
}
