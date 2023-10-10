<?php
/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Tweakwise\Magento2Tweakwise\Model\Catalog\Product\Recommendation;

use Tweakwise\Magento2Tweakwise\Model\Client;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\Recommendations\FeaturedRequest;
use Tweakwise\Magento2Tweakwise\Model\Client\RequestFactory;
use Tweakwise\Magento2Tweakwise\Model\Client\Response\RecommendationsResponse;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\Config as CatalogConfig;
use Tweakwise\Magento2Tweakwise\Model\Config;

class Context
{

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var RequestFactory
     */
    protected $requestFactory;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var CatalogConfig
     */
    protected $catalogConfig;

    /**
     * @var Visibility
     */
    protected $visibility;

    /**
     * @var FeaturedRequest
     */
    protected $request;

    /**
     * @var RecommendationsResponse
     */
    protected $response;

    /**
     * @var Collection
     */
    protected $collection;

    /**
     * @var Config
     */
    protected $config;

    /**
     * Context constructor.
     * @param Client $client
     * @param RequestFactory $requestFactory
     * @param CollectionFactory $collectionFactory
     * @param CatalogConfig $catalogConfig
     * @param Visibility $visibility
     * @param Config $config
     */
    public function __construct(
        Client $client,
        RequestFactory $requestFactory,
        CollectionFactory $collectionFactory,
        CatalogConfig $catalogConfig,
        Visibility $visibility,
        Config $config
    ) {
        $this->client = $client;
        $this->requestFactory = $requestFactory;
        $this->collectionFactory = $collectionFactory;
        $this->catalogConfig = $catalogConfig;
        $this->visibility = $visibility;
        $this->config = $config;
    }

    /**
     * @return FeaturedRequest
     */
    public function getRequest()
    {
        if (!$this->request) {
            $this->request = $this->requestFactory->create();
        }

        return $this->request;
    }

    /**
     * @return RecommendationsResponse
     */
    public function getResponse()
    {
        if (!$this->response) {
            $this->response = $this->client->request($this->getRequest());
        }

        $template = $this->request->getTemplate();

        if (!is_int($this->request->getTemplate())) {
            //grouped item
            $limit = $this->config->getLimitGroupCodeItems();
            if (!empty($limit) && $limit > 0) {
                $items = $this->response->getItems();
                $items = array_slice($items, 0, $limit);
                $this->response->replaceItems($items);
            }
        }

        return $this->response;
    }

    /**
     * @return Collection
     */
    public function getCollection()
    {
        if (!$this->collection) {
            $collection = $this->collectionFactory->create(['response' => $this->getResponse()]);
            $this->prepareCollection($collection);
            $this->collection = $collection;
        }

        return $this->collection;
    }

    /**
     * @param Collection $collection
     */
    protected function prepareCollection(Collection $collection)
    {
        $collection->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents()
            ->addAttributeToSelect($this->catalogConfig->getProductAttributes())
            ->addUrlRewrite()
            ->setVisibility($this->visibility->getVisibleInCatalogIds())
            ->setFlag('do_not_use_category_id', true);
    }

    /**
     * @param FeaturedRequest $request
     * @return void
     */

    public function setRequest(FeaturedRequest $request) {
        $this->collection = null;
        $this->response = null;
        $this->request = $request;
    }
}
