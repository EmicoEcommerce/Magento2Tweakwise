<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Tweakwise\Magento2Tweakwise\Model\Autocomplete\DataProvider;

use Magento\Catalog\Model\Category;
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
use Tweakwise\Magento2Tweakwise\Model\Autocomplete\DataProvider\SuggestionBlockItemFactory;

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
        protected readonly SuggestionBlockItemFactory $suggestionBlockItemFactory
    ) {
        $this->config = $config;
        $this->cookieManager = $cookieManager;
        $this->dataProviderHelper = $dataProviderHelper;
        $this->suggestionGroupItemFactory = $suggestionGroupItemFactory;
        $this->productSuggestionRequestFactory = $productSuggestionRequestFactory;
        $this->suggestionRequestFactory = $suggestionRequestFactory;
        $this->client = $client;
        $this->request = $request;
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

        $promises = $this->buildRequests($query, $this->dataProviderHelper->getCategory());
        if (empty($promises)) {
            return [];
        }

        // @phpstan-ignore-next-line
        $responses = Utils::unwrap($promises);
        $results = [];

        foreach ($responses as $response) {
            $results[] = $this->processResponse($response);
        }

        $flattened = array_filter(array_merge(...$results));
        return !empty($flattened) ? $flattened : [];
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

    /**
     * Build async requests for suggestions and products
     *
     * @param string $query
     * @param int|Category $category Category object or category ID
     * @return array
     */
    private function buildRequests(string $query, int|Category $category): array
    {
        $profileKeyCookie = $this->cookieManager->getCookie(
            $this->config->getPersonalMerchandisingCookieName(),
            null
        );

        $promises = [];
        $promises['suggestions'] = $this->client->request(
            $this->buildSuggestionsRequest($query, $category, $profileKeyCookie),
            true
        );
        $promises['product_suggestions'] = $this->client->request(
            $this->buildProductSuggestionsRequest($query, $category, $profileKeyCookie),
            true
        );

        return $promises;
    }

    /**
     * Build suggestions request with common parameters
     *
     * @param string $query
     * @param Category|int $category Category object or category ID
     * @param string|null $profileKeyCookie
     *
     * @return Request
     */
    private function buildSuggestionsRequest(string $query, int|Category $category, ?string $profileKeyCookie): Request
    {
        $request = $this->suggestionRequestFactory->create();
        // @phpstan-ignore-next-line
        $request->setSearch($query);
        $this->addProfileKeyToRequest($request, $profileKeyCookie);
        $request->addCategoryFilter($category);

        return $request;
    }

    /**
     * Build product suggestions request with common parameters
     *
     * @param string $query
     * @param Category|int $category Category object or category ID
     * @param string|null $profileKeyCookie
     *
     * @return Request
     */
    private function buildProductSuggestionsRequest(string $query, int|Category $category, ?string $profileKeyCookie): Request
    {
        /** @var ProductSuggestionsRequest $request */
        $request = $this->productSuggestionRequestFactory->create();
        $request->setSearch($query);
        $this->addProfileKeyToRequest($request, $profileKeyCookie);
        $request->addCategoryFilter($category);

        return $request;
    }

    /**
     * Add profile key parameter to request if available
     *
     * @param Request $request
     * @param string|null $profileKeyCookie
     * @return void
     */
    private function addProfileKeyToRequest(Request $request, ?string $profileKeyCookie): void
    {
        if (!$profileKeyCookie) {
            return;
        }

        $profileKey = $this->request->setProfileKey($profileKeyCookie);
        // @phpstan-ignore-next-line
        $request->addParameter(key($profileKey->getParameters()), $profileKeyCookie);
    }

    /**
     * Process a single response and return items
     *
     * @param AutocompleteProductResponseInterface|SuggestionsResponse $response
     * @return array
     */
    private function processResponse($response): array
    {
        if ($response instanceof AutocompleteProductResponseInterface) {
            return $this->dataProviderHelper->useBlocks()
                ? $this->getSuggestionBlocks($response)
                : $this->dataProviderHelper->getProductItems($response);
        }

        if ($response instanceof SuggestionsResponse) {
            return $this->getSuggestionGroups($response);
        }

        return [];
    }

    /**
     * @param AutocompleteProductResponseInterface $response
     *
     * @return array
     * @throws LocalizedException
     */
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

                $resolvedItems[] = [
                    ...$item,
                    ...$productItemsByTweakwiseId[$itemNumber],
                ];
            }

            $suggestionBlock['items'] = $resolvedItems;
            $results[] = $this->suggestionBlockItemFactory->create(['data' => ['block' => $suggestionBlock]]);
        }

        return $results;
    }
}
