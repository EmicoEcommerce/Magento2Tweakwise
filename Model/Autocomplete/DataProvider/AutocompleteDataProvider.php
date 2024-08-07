<?php

namespace Tweakwise\Magento2Tweakwise\Model\Autocomplete\DataProvider;

use Tweakwise\Magento2Tweakwise\Model\Autocomplete\DataProviderHelper;
use Tweakwise\Magento2Tweakwise\Model\Autocomplete\DataProviderInterface;
use Tweakwise\Magento2Tweakwise\Model\Client;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\AutocompleteRequest;
use Tweakwise\Magento2Tweakwise\Model\Client\RequestFactory;
use Tweakwise\Magento2Tweakwise\Model\Client\Response\AutocompleteResponse;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Magento\Catalog\Model\Layer\Category\CollectionFilter;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Search\Model\Autocomplete\ItemInterface;
use Magento\Store\Model\StoreManagerInterface;

class AutocompleteDataProvider implements DataProviderInterface
{
    /**
     * @var DataProviderHelper
     */
    protected $dataProviderHelper;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var RequestFactory
     */
    protected $autocompleteRequestFactory;

    /**
     * @var ProductCollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var CollectionFilter
     */
    protected $collectionFilter;

    /**
     * @var ProductItemFactory
     */
    protected $productItemFactory;

    /**
     * @var SuggestionItemFactory
     */
    protected $suggestionItemFactory;

    /**
     * AutocompleteDataProvider constructor
     * @param DataProviderHelper $dataProviderHelper
     * @param Config $config
     * @param Client $client
     * @param RequestFactory $autocompleteRequestFactory
     * @param StoreManagerInterface $storeManager
     * @param ProductCollectionFactory $productCollectionFactory
     * @param CollectionFilter $collectionFilter
     * @param ProductItemFactory $productItemFactory
     * @param SuggestionItemFactory $suggestionItemFactory
     */
    public function __construct(
        DataProviderHelper $dataProviderHelper,
        Config $config,
        Client $client,
        RequestFactory $autocompleteRequestFactory,
        StoreManagerInterface $storeManager,
        ProductCollectionFactory $productCollectionFactory,
        CollectionFilter $collectionFilter,
        ProductItemFactory $productItemFactory,
        SuggestionItemFactory $suggestionItemFactory
    ) {
        $this->config = $config;
        $this->client = $client;
        $this->autocompleteRequestFactory = $autocompleteRequestFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->storeManager = $storeManager;
        $this->collectionFilter = $collectionFilter;
        $this->productItemFactory = $productItemFactory;
        $this->suggestionItemFactory = $suggestionItemFactory;
        $this->dataProviderHelper = $dataProviderHelper;
    }

    /**
     * @return bool
     */
    public function isSupported(): bool
    {
        return ($this->config->isAutocompleteEnabled() && !$this->config->isSuggestionsAutocomplete());
    }

    /**
     * @return ItemInterface[]
     * @throws LocalizedException
     */
    public function getItems()
    {
        $query = $this->dataProviderHelper->getQuery();
        if (empty($query)) {
            return [];
        }

        $config = $this->config;

        /** @var AutocompleteRequest $request */
        $request = $this->autocompleteRequestFactory->create();
        $request->addCategoryFilter($this->dataProviderHelper->getCategory());
        $request->setGetProducts($config->isAutocompleteProductsEnabled());
        $request->setGetSuggestions($config->isAutocompleteSuggestionsEnabled());
        $request->setMaxResult($config->getAutocompleteMaxResults());
        $request->setSearch($query);

        /** @var AutocompleteResponse $response */
        try {
            $response = $this->client->request($request);
        } catch (\Exception $e) {
            return [];
        }

        $productResult = $this->dataProviderHelper->getProductItems($response);
        $suggestionResult = $this->getSuggestionResult($response);

        return array_merge($suggestionResult, $productResult);
    }

    /**
     * @param AutocompleteResponse $response
     * @return ItemInterface[]
     */
    protected function getSuggestionResult(AutocompleteResponse $response)
    {
        $result = [];
        foreach ($response->getSuggestions() as $suggestion) {
            $result[] = $this->suggestionItemFactory->create(['suggestion' => $suggestion]);
        }

        return $result;
    }
}
