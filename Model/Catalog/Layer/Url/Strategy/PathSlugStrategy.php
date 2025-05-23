<?php

/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\Strategy;

use Tweakwise\Magento2Tweakwise\Exception\RuntimeException;
use Tweakwise\Magento2Tweakwise\Exception\UnexpectedValueException;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Filter\Item;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\NavigationContext\CurrentContext;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\CategoryUrlInterface;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\FilterApplierInterface;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\RewriteResolver\RewriteResolverInterface;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\RouteMatchingInterface;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\StrategyHelper;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\UrlInterface;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\UrlModel;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\UrlFactory;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\ProductNavigationRequest;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\ProductSearchRequest;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\FacetType\SettingsType;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\CatalogUrlRewrite\Model\CategoryUrlPathGenerator;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http as MagentoHttpRequest;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface as MagentoUrlInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class PathSlugStrategy implements
    UrlInterface,
    RouteMatchingInterface,
    FilterApplierInterface,
    CategoryUrlInterface
{
    public const REQUEST_FILTER_PATH = 'filter_path';

    /**
     * @var Resolver
     */
    protected $layerResolver;

    /**
     * @var FilterSlugManager
     */
    protected $filterSlugManager;

    /**
     * @var UrlModel
     */
    protected $magentoUrl;

    /**
     * @var UrlFinderInterface
     */
    protected $urlFinder;

    /**
     * @var Item[]
     */
    protected $activeFilters;

    /**
     * @var QueryParameterStrategy
     */
    protected $queryParameterStrategy;

    /**
     * @var UrlFactory
     */
    protected $urlFactory;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var CurrentContext
     */
    protected $currentContext;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var StrategyHelper
     */
    protected $strategyHelper;

    /**
     * @var RewriteResolverInterface[]
     */
    protected $rewriteResolvers;

    /**
     * @var array
     */
    protected $skipMatchExtensions;

    /**
     * Magento constructor.
     *
     * @param UrlModel $magentoUrl
     * @param UrlFactory $urlFactory
     * @param Resolver $layerResolver
     * @param FilterSlugManager $filterSlugManager
     * @param QueryParameterStrategy $queryParameterStrategy
     * @param Config $config
     * @param CurrentContext $currentContext
     * @param ScopeConfigInterface $scopeConfig
     * @param StrategyHelper $strategyHelper
     * @param RewriteResolverInterface[] $rewriteResolvers
     * @param array $skipMatchExtensions
     */
    public function __construct(
        UrlModel $magentoUrl,
        UrlFactory $urlFactory,
        Resolver $layerResolver,
        FilterSlugManager $filterSlugManager,
        QueryParameterStrategy $queryParameterStrategy,
        Config $config,
        CurrentContext $currentContext,
        ScopeConfigInterface $scopeConfig,
        StrategyHelper $strategyHelper,
        array $rewriteResolvers,
        array $skipMatchExtensions
    ) {
        $this->magentoUrl = $magentoUrl;
        $this->layerResolver = $layerResolver;
        $this->filterSlugManager = $filterSlugManager;
        $this->queryParameterStrategy = $queryParameterStrategy;
        $this->urlFactory = $urlFactory;
        $this->config = $config;
        $this->currentContext = $currentContext;
        $this->scopeConfig = $scopeConfig;
        $this->rewriteResolvers = $rewriteResolvers;
        $this->skipMatchExtensions = $skipMatchExtensions;
        $this->strategyHelper = $strategyHelper;
    }

    /**
     * Get url when selecting item
     *
     * @param MagentoHttpRequest $request
     * @param Item $item
     * @return string
     */
    public function getAttributeSelectUrl(MagentoHttpRequest $request, Item $item): string
    {
        $filters = $this->getActiveFilters();
        $filters[] = $item;

        return $this->buildFilterUrl($request, $filters);
    }

    /**
     * Get url when removing item from selecting
     *
     * @param MagentoHttpRequest $request
     * @param Item $item
     * @return string
     */
    public function getAttributeRemoveUrl(MagentoHttpRequest $request, Item $item): string
    {
        $filters = $this->getActiveFilters();
        /**
         * @var int $key
         * @var Item $activeItem
         */
        foreach ($filters as $key => $activeItem) {
            if ($activeItem === $item) {
                unset($filters[$key]);
            }
        }

        return $this->buildFilterUrl($request, $filters);
    }

    /**
     * @param MagentoHttpRequest $request
     * @param Item $item
     * @return string
     */
    public function getSliderUrl(MagentoHttpRequest $request, Item $item): string
    {
        $filters = $this->getActiveFilters();
        /**
         * @var int $key
         * @var Item $activeItem
         */
        foreach ($filters as $key => $activeItem) {
            if ($activeItem->getFilter()->getUrlKey() === $item->getFilter()->getUrlKey()) {
                unset($filters[$key]);
            }
        }

        $attribute = clone $item->getAttribute();
        $attribute->setValue('title', '{{from}}-{{to}}');
        $filters[] = new Item($item->getFilter(), $attribute, $this->urlFactory->create());

        return $this->buildFilterUrl($request, $filters);
    }

    /**
     * Fetch clear all items from url
     *
     * @param MagentoHttpRequest $request
     * @param Item[] $activeFilterItems
     * @return string
     */
    public function getClearUrl(MagentoHttpRequest $request, array $activeFilterItems): string
    {
        return $this->buildFilterUrl($request);
    }

    /**
     * Apply all attribute filters, category filters, sort order, page limit request parameters to navigation request
     *
     * @param MagentoHttpRequest $request
     * @param ProductNavigationRequest $navigationRequest
     * @return $this
     */
    public function apply(
        MagentoHttpRequest $request,
        ProductNavigationRequest $navigationRequest
    ): FilterApplierInterface {
        // Order / pagination etc. is still done with query parameters. Also apply this using the queryParameter strategy
        $this->queryParameterStrategy->apply($request, $navigationRequest);
        $filterPath = $request->getParam(self::REQUEST_FILTER_PATH);
        if (empty($filterPath)) {
            return $this;
        }

        $filterPath = trim($filterPath, '/');
        $filterParts = explode('/', $filterPath);
        $facet = $filterParts[0];
        foreach ($filterParts as $i => $part) {
            if ($i % 2 === 0) {
                $facet = $part;
            } else {
                try {
                    $attribute = $this->filterSlugManager->getAttributeBySlug($part);
                } catch (UnexpectedValueException $exception) {
                    $attribute = $part;
                }

                $navigationRequest->addAttributeFilter($facet, $attribute);
            }
        }

        return $this;
    }

    /**
     * @return array|Item[]
     */
    protected function getActiveFilters(): array
    {
        if ($this->activeFilters !== null) {
            return $this->activeFilters;
        }

        $filters = $this->getLayer()->getState()->getFilters();
        if (!\is_array($filters)) {
            return [];
        }

        $this->activeFilters = $filters;
        return $this->activeFilters;
    }

    /**
     * @param MagentoHttpRequest $request
     * @return string
     */
    public function getOriginalUrl(MagentoHttpRequest $request): string
    {
        $twOriginalUrl = $request->getParam('__tw_original_url');
        if ($twOriginalUrl) {
            // This seems ugly, perhaps there is another way?
            $query = [];
            // Add page and sort
            $page = $request->getParam('p');
            $sort = $request->getParam('product_list_order');
            $limit = $request->getParam('product_list_limit');
            $mode = $request->getParam('product_list_mode');

            if (
                $page &&
                (int) $page > 1
            ) {
                $query['p'] = $page;
            } else {
                unset($query['p']);
            }

            if ($sort) {
                $query['product_list_order'] = $sort;
            }

            if ($limit) {
                $query['product_list_limit'] = $limit;
            }

            if ($mode) {
                $query['product_list_mode'] = $mode;
            }

            $url = filter_var(
                $this->magentoUrl->getDirectUrl($twOriginalUrl, ['_query' => $query]),
                FILTER_SANITIZE_URL
            );

            return str_replace($this->magentoUrl->getBaseUrl(), '', $url);
        }

        $url = $this->getCurrentUrl($request);

        $filterPath = $this->buildFilterSlugPath($this->getActiveFilters());

        //remove active filters from url,this causes the url to be wrong on page refresh
        if (strpos($url, $filterPath) !== false) {
            $url = str_replace($filterPath, '', $url);
        }

        return $url;
    }

    /**
     * @return UrlModel
     */
    public function getMagentoUrl()
    {
        return $this->magentoUrl;
    }

    /**
     * @return string
     */
    protected function getCurrentUrl(MagentoHttpRequest $request): string
    {
        $url = $request->getOriginalPathInfo();

        return str_replace($this->magentoUrl->getBaseUrl(), '', $url);
    }

    /**
     * @return Layer
     */
    protected function getLayer(): Layer
    {
        return $this->layerResolver->get();
    }

    /**
     * @param MagentoHttpRequest $request
     * @param array $filters
     * @return string
     * phpcs:disable Magento2.Functions.DiscouragedFunction.Discouraged
     */
    public function buildFilterUrl(MagentoHttpRequest $request, array $filters = []): string
    {
        $currentUrl = $this->getOriginalUrl($request);

        $currentFilterPath = $request->getParam(self::REQUEST_FILTER_PATH);
        $newFilterPath = $this->buildFilterSlugPath($filters);

        if (empty($currentFilterPath)) {
            $urlParts = parse_url($currentUrl);

            if (strpos($urlParts['path'], $newFilterPath) === false) {
                $url = $urlParts['path'] . $newFilterPath;
            } else {
                //filter path already exists in url
                $url = $urlParts['path'];
            }

            if (isset($urlParts['query'])) {
                $url .= '?' . $urlParts['query'];
            }
        } else {
            // Replace filter path in current URL with the new filter combination path
            if (strpos($currentUrl, $currentFilterPath) !== false) {
                $url = str_replace(
                    sprintf('%s/', rtrim($currentFilterPath, '/')),
                    sprintf('%s/', $newFilterPath),
                    $currentUrl
                );
            } else {
                $url = $currentUrl . '/' . $newFilterPath;
            }
        }

        $categoryUrlSuffix = $this->scopeConfig->getValue(
            CategoryUrlPathGenerator::XML_PATH_CATEGORY_URL_SUFFIX,
            'store'
        );

        // Replace all occurrences of double slashes with a single slash except those in scheme.
        // This can happen when $categoryUrlSuffix === '/' and in some other cases
        return preg_replace('/(?<!:)\/\//', '/', $url);
    }

    /**
     * @param array $filters
     * @return string
     */
    protected function buildFilterSlugPath(array $filters): string
    {
        if (empty($filters)) {
            return '';
        }

        usort($filters, [$this, 'sortFilterItems']);

        $path = '';
        /** @var Item $filterItem */
        foreach ($filters as $filterItem) {
            $filter = $filterItem->getFilter();

            $urlKey = $filter->getUrlKey();
            $facetSettings = $filter->getFacet()->getFacetSettings();
            if ($facetSettings->getSource() === SettingsType::SOURCE_CATEGORY) {
                continue;
            }

            if ($facetSettings->getSelectionType() === SettingsType::SELECTION_TYPE_SLIDER) {
                $slug = $filterItem->getAttribute()->getTitle();
            } else {
                $slug = $this->filterSlugManager->getSlugForFilterItem($filterItem);
            }

            $path .= $urlKey . '/' . $slug . '/';
        }

        return '/' . $path;
    }

    /**
     * First sort filter items by facet and then on attribute
     *
     * @param Item $filterItemA
     * @param Item $filterItemB
     * @return int
     */
    protected function sortFilterItems(Item $filterItemA, Item $filterItemB): int
    {
        $facetA = $filterItemA->getFilter()->getUrlKey();
        $facetB = $filterItemB->getFilter()->getUrlKey();
        $attributeA = $filterItemA->getAttribute()->getTitle();
        $attributeB = $filterItemB->getAttribute()->getTitle();

        if ($facetA === $facetB) {
            return $attributeA > $attributeB ? 1 : -1;
        }

        return $facetA > $facetB ? 1 : -1;
    }

    /**
     * @param MagentoHttpRequest $request
     * @return bool|ActionInterface
     */
    public function match(MagentoHttpRequest $request)
    {
        if ($this->skip($request)) {
            return false;
        }

        $rewrites = [];
        foreach ($this->rewriteResolvers as $rewriteResolver) {
            $rewrites[] = $rewriteResolver->getRewrites($request);
        }

        $rewrites = array_merge([], ...$rewrites);

        if (empty($rewrites)) {
            return false;
        }

        $sortByLongestMatch = static function (
            UrlRewrite $rewrite1,
            UrlRewrite $rewrite2
        ) {
            return
                strlen($rewrite2->getRequestPath()) -
                strlen($rewrite1->getRequestPath());
        };
        usort($rewrites, $sortByLongestMatch);

        $rewrite = current($rewrites);
        $path = trim($request->getPathInfo(), '/');

        $filterPath = str_replace($rewrite->getRequestPath(), '', $path);

        //only replace first ocurrence of category name
        $pos = strpos($path, $rewrite->getRequestPath());
        if ($pos !== false) {
            $filterPath = substr_replace($path, '', $pos, strlen($rewrite->getRequestPath()));
        }

        $filterPathParts = explode('/', trim($filterPath, '/'));
        if ((count($filterPathParts) % 2) !== 0) {
            /* In this case we dont have an even amount of path segments,
            This cannot correspond to a filter in this model since the filters
            are constructed as filterName/filterValue for each filter, note the two components
            for each filter. Meaning that a correct filter path should have an even number of path parts
            */
            return false;
        }

        if ($rewrite->getRedirectType() === 301) {
            $url = $this->magentoUrl->getDirectUrl($rewrite->getTargetPath() . $filterPath);
            $request->setParam('tw_filter_redirect', $url);
        }

        // Set the filter params part of the URL as a separate request param.
        // The request param filter_path is used to query tweakwise.
        $request->setParam(self::REQUEST_FILTER_PATH, $filterPath);
        $request->setAlias(
            MagentoUrlInterface::REWRITE_REQUEST_PATH_ALIAS,
            $path
        );
        $request->setPathInfo('/' . $rewrite->getTargetPath());

        return true;
    }

    /**
     * We dont need to match on media urls
     *
     * @param MagentoHttpRequest $request
     * @return bool
     */
    protected function skip(MagentoHttpRequest $request): bool
    {
        $requestPath = $request->getPathInfo();
        foreach ($this->skipMatchExtensions as $fileExtension) {
            // Check for string length of the request path.
            if (strlen($requestPath) <= strlen($fileExtension)) {
                continue;
            }

            if (strpos($requestPath, $fileExtension, -\strlen($fileExtension)) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param MagentoHttpRequest $request
     * @param Item $item
     * @return string
     * @throws NoSuchEntityException
     * phpcs:disable Magento2.Functions.DiscouragedFunction.Discouraged
     */
    public function getCategoryFilterSelectUrl(
        MagentoHttpRequest $request,
        Item $item
    ): string {
        if ($this->currentContext->getRequest() instanceof ProductSearchRequest) {
            return $this->queryParameterStrategy->getCategoryFilterSelectUrl($request, $item);
        }

        $category = $this->strategyHelper->getCategoryFromItem($item);
        $categoryUrlPath = \parse_url($category->getUrl(), PHP_URL_PATH);

        $url = $this->magentoUrl->getDirectUrl($categoryUrlPath);

        /*
        Make sure we dont have any double slashes, add the current filter path to the category url to maintain
        the currently selected filters.
        */
        $filterSlugPath = $this->buildFilterSlugPath($this->getActiveFilters());

        if (!empty($filterSlugPath)) {
            $url .= '/' . trim($filterSlugPath, '/');
        }

        /*
         We explode the url so that we can capture its parts and find the double values in order to remove them.
         This is needed because the categoryUrlPath contains the store code in some cases and the directUrl as well.
         These two are the only unique parts in this situation and so need to be removed.
         */

        $explode = explode('/', $url);

        // Filter out consecutive duplicate values
        $filteredParts = [];
        $prevPart = null;
        foreach ($explode as $part) {
            if ($part !== $prevPart) {
                $filteredParts[] = $part;
            }

            $prevPart = $part;
        }

        $url = implode('/', $filteredParts);

        //remove double slashes with exception for the protocol
        $url = preg_replace('#(?<!:)//+#', '/', $url);

        return $url;
    }

    /**
     * @param MagentoHttpRequest $request
     * @param Item $item
     * @return mixed
     */
    public function getCategoryFilterRemoveUrl(
        MagentoHttpRequest $request,
        Item $item
    ): string {
        return $this->queryParameterStrategy->getCategoryFilterRemoveUrl($request, $item);
    }

    /**
     * Determine if this UrlInterface is allowed in the current context
     *
     * @return boolean
     */
    public function isAllowed(): bool
    {
        try {
            $context = $this->currentContext->getContext();
        } catch (RuntimeException $e) {
            return true;
        }

        return !$context->getRequest() instanceof ProductSearchRequest;
    }
}
