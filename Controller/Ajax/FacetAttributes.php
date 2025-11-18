<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Tweakwise\Magento2Tweakwise\Controller\Ajax;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Tweakwise\Magento2Tweakwise\Exception\ApiException;
use Tweakwise\Magento2Tweakwise\Model\Client;
use Tweakwise\Magento2Tweakwise\Model\Client\RequestFactory;

/**
 * Class Navigation
 * Handles ajax filtering requests for category pages
 */
class FacetAttributes extends Action
{
    public function __construct(
        Context $context,
        private RequestFactory $requestFactory,
        private Client $client
    ) {
        parent::__construct($context);
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
            // @phpstan-ignore-next-line
            $facetRequest->addFacetKey($facetKey);
        }

        if (!empty($filtertemplate)) {
            // @phpstan-ignore-next-line
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
                // @phpstan-ignore-next-line
                if (!$e->getCode() === 404) {
                    throw $e;
                }

                continue;
            }

            // @phpstan-ignore-next-line
            if (empty($response->getAttributes())) {
                continue;
            }

            // @phpstan-ignore-next-line
            foreach ($response->getAttributes() as $attribute) {
                $result[] = ['value' => $attribute['title'], 'label' => $attribute['title']];
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
        // @phpstan-ignore-next-line
        $json->setData(['data' => $result]);

        return $json;
    }
}
