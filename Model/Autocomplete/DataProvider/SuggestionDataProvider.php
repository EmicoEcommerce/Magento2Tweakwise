<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Tweakwise\Magento2Tweakwise\Model\Autocomplete\DataProvider;

use Magento\Framework\Stdlib\CookieManagerInterface;
use Tweakwise\Magento2Tweakwise\Model\Autocomplete\DataProviderHelper;
use Tweakwise\Magento2Tweakwise\Model\Autocomplete\DataProviderInterface;
use Tweakwise\Magento2Tweakwise\Model\Client;
use Tweakwise\Magento2Tweakwise\Model\Client\Request;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\Suggestions\ProductSuggestionsRequest;
use Tweakwise\Magento2Tweakwise\Model\Client\RequestFactory;
use Tweakwise\Magento2Tweakwise\Model\Client\Response\AutocompleteProductResponseInterface;
use Tweakwise\Magento2Tweakwise\Model\Client\Response\Suggestions\SuggestionsResponse;
use Tweakwise\Magento2Tweakwise\Model\Config;
use GuzzleHttp\Promise\Utils;
use Magento\Framework\Exception\LocalizedException;
use Magento\Search\Model\Autocomplete\ItemInterface;

class SuggestionDataProvider implements DataProviderInterface
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var CookieManagerInterface
     */
    protected $cookieManager;

    /**
     * @var DataProviderHelper
     */
    protected $dataProviderHelper;

    /**
     * @var SuggestionGroupItemFactory
     */
    protected $suggestionGroupItemFactory;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var RequestFactory
     */
    protected $productSuggestionRequestFactory;

    /**
     * @var RequestFactory
     */
    protected $suggestionRequestFactory;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var SuggestionBlockItemFactory
     */
    protected $suggestionBlockItemFactory;

    /**
     * AutocompleteDataProvider constructor.
     * @param Config $config
     * @param CookieManagerInterface $cookieManager
     * @param DataProviderHelper $dataProviderHelper
     * @param SuggestionGroupItemFactory $suggestionGroupItemFactory
     * @param RequestFactory $productSuggestionRequestFactory
     * @param RequestFactory $suggestionRequestFactory
     * @param Client $client
     * @param Request $request
     * @param SuggestionBlockItemFactory $suggestionBlockItemFactory
     */
    public function __construct(
        Config $config,
        CookieManagerInterface $cookieManager,
        DataProviderHelper $dataProviderHelper,
        SuggestionGroupItemFactory $suggestionGroupItemFactory,
        RequestFactory $productSuggestionRequestFactory,
        RequestFactory $suggestionRequestFactory,
        Client $client,
        Request $request,
        SuggestionBlockItemFactory $suggestionBlockItemFactory
    ) {
        $this->config = $config;
        $this->cookieManager = $cookieManager;
        $this->dataProviderHelper = $dataProviderHelper;
        $this->suggestionGroupItemFactory = $suggestionGroupItemFactory;
        $this->productSuggestionRequestFactory = $productSuggestionRequestFactory;
        $this->suggestionRequestFactory = $suggestionRequestFactory;
        $this->client = $client;
        $this->request = $request;
        $this->suggestionBlockItemFactory = $suggestionBlockItemFactory;
    }

    /**
     * @return bool
     */
    public function isSupported(): bool
    {
        return $this->config->isSuggestionsAutocomplete();
    }

    /**
     * @return ItemInterface[]
     * @throws LocalizedException
     * @throws \Throwable
     */
    public function getItems()
    {
        $query = $this->dataProviderHelper->getQuery();
        if (empty($query)) {
            return [];
        }

        $category = $this->dataProviderHelper->getCategory();
        $promises = [];

        $profileKeyCookie = $this->cookieManager->getCookie(
            $this->config->getPersonalMerchandisingCookieName(),
            null
        );

        $suggestionsRequest = $this->suggestionRequestFactory->create();
        // @phpstan-ignore-next-line
        $suggestionsRequest->setSearch($query);

        if ($profileKeyCookie) {
            $profileKey = $this->request->setProfileKey($profileKeyCookie);
            // @phpstan-ignore-next-line
            $suggestionsRequest->addParameter(key($profileKey->getParameters()), $profileKeyCookie);
        }

        $suggestionsRequest->addCategoryFilter($category);
        $promises['suggestions'] = $this->client->request(
            $suggestionsRequest,
            true
        );

        /** @var ProductSuggestionsRequest $productSuggestionsRequest */
        $productSuggestionsRequest = $this->productSuggestionRequestFactory->create();
        $productSuggestionsRequest->setSearch($query);

        if ($profileKeyCookie) {
            // @phpstan-ignore-next-line
            $productSuggestionsRequest->addParameter(key($profileKey->getParameters()), $profileKeyCookie);
        }

        $productSuggestionsRequest->addCategoryFilter($category);
        $promises['product_suggestions'] = $this->client->request(
            $productSuggestionsRequest,
            true
        );

        // @phpstan-ignore-next-line
        if (empty($promises)) {
            return [];
        }

        $results = [];
        // @phpstan-ignore-next-line
        $responses = Utils::unwrap($promises);
        foreach ($responses as $response) {
            if ($response instanceof AutocompleteProductResponseInterface) {
                if ($this->dataProviderHelper->useBlocks()) {
                    $results[] = $this->getSuggestionBlocks($response);
                    continue;
                }

                $results[] = $this->dataProviderHelper->getProductItems($response);
            }

            if (!($response instanceof SuggestionsResponse)) {
                continue;
            }

            $results[] = $this->getSuggestionGroups($response);
        }

        return !empty($results) ? array_merge(...$results) : [];
    }

    /**
     * @param SuggestionsResponse $response
     * @return ItemInterface[]
     */
    protected function getSuggestionGroups(SuggestionsResponse $response)
    {
        $results = [];
        // @phpstan-ignore-next-line
        $groups = $response->getGroups() ? $response->getGroups() : [];
        foreach ($groups as $suggestionGroup) {
            $results[] = $this->suggestionGroupItemFactory->create(['group' => $suggestionGroup]);
        }

        return $results;
    }

    protected function getSuggestionBlocks(AutocompleteProductResponseInterface $response): array
    {
        $results = [];

        $blocks = $response->getBlocks() ? $response->getBlocks() : [];
        if (empty($blocks)) {
            return $results;
        }

        $products = $this->dataProviderHelper->getProductItems($response);

        $productItemsByTweakwiseId = [];
        foreach ($products as $product) {
            $productItemsByTweakwiseId[(string)$product->getProduct()->getData('tweakwise_id')] = $product->toArray();
        }

        foreach ($blocks as $suggestionBlock) {
            if (empty($suggestionBlock['items'])) {
                continue;
            }

            $items = isset($suggestionBlock['items']['item'][0]) ? $suggestionBlock['items']['item'] : $suggestionBlock['items'];
            $resolvedItems = [];

            foreach ($items as $item) {
                $itemNumber = (string)($item['itemno'] ?? '');
                if (!isset($productItemsByTweakwiseId[$itemNumber])) {
                    $resolvedItems[] = $item;
                    continue;
                }

                $resolvedItems[] = array_merge($item, $productItemsByTweakwiseId[$itemNumber]);
            }

            $suggestionBlock['items'] = $resolvedItems;
            $results[] = $this->suggestionBlockItemFactory->create(['data' => ['block' => $suggestionBlock]]);
        }

        return $results;
    }
}
