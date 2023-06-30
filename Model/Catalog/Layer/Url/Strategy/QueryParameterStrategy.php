<?php
/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\Strategy;

use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Filter\Item;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\CategoryUrlInterface;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\FilterApplierInterface;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\StrategyHelper;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\UrlInterface;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\UrlModel;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\ProductNavigationRequest;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\ProductSearchRequest;
use Tweakwise\Magento2Tweakwise\Model\Config as TweakwiseConfig;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Framework\App\Request\Http as MagentoHttpRequest;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url;

class QueryParameterStrategy implements UrlInterface, FilterApplierInterface, CategoryUrlInterface
{
    /**
     * Separator used in category tree urls
     */
    public const CATEGORY_TREE_SEPARATOR = '-';

    /**
     * Extra ignored page parameters
     */
    public const PARAM_MODE = 'product_list_mode';
    public const PARAM_CATEGORY = 'categorie';
    public const PARAM_CACHE = '_';

    /**
     * Commonly used query parameters from headers
     */
    public const PARAM_LIMIT = 'product_list_limit';
    public const PARAM_ORDER = 'product_list_order';
    public const PARAM_PAGE = 'p';
    public const PARAM_SEARCH = 'q';

    /**
     * Parameters to be ignored as attribute filters
     *
     * @var string[]
     */
    protected $ignoredQueryParameters = [
        self::PARAM_CATEGORY,
        self::PARAM_ORDER,
        self::PARAM_LIMIT,
        self::PARAM_MODE,
        self::PARAM_SEARCH,
        self::PARAM_CACHE,
    ];

    /**
     * @var UrlModel
     */
    protected $url;

    /**
     * @var StrategyHelper
     */
    protected $strategyHelper;

    /**
     * @var CookieManagerInterface
     */
    protected $cookieManager;

    /**
     * @var TweakwiseConfig
     */
    protected $tweakwiseConfig;

    /**
     * @var array
     */
    private $queryUrlCache = [];

    /**
     * @var Url
     */
    protected $layerUrl;

    /**
     * Magento constructor.
     *
     * @param UrlModel $url
     * @param StrategyHelper $strategyHelper
     * @param CookieManagerInterface $cookieManager
     * @param TweakwiseConfig $config
     */
    public function __construct(
        UrlModel $url,
        StrategyHelper $strategyHelper,
        CookieManagerInterface $cookieManager,
        TweakwiseConfig $config,
        Url $layerUrl
    ) {
        $this->url = $url;
        $this->strategyHelper = $strategyHelper;
        $this->cookieManager = $cookieManager;
        $this->tweakwiseConfig = $config;
        $this->layerUrl = $layerUrl;
    }

    /**
     * {@inheritdoc}
     */
    public function getClearUrl(MagentoHttpRequest $request, array $activeFilterItems): string
    {
        $query = [];
        /** @var Item $item */
        foreach ($activeFilterItems as $item) {
            $filter = $item->getFilter();

            $urlKey = $filter->getUrlKey();
            $query[$urlKey] = $filter->getCleanValue();
        }

        return $this->getCurrentQueryUrl($request, $query);
    }

    /**
     * @param MagentoHttpRequest $request
     * @param array $query
     * @return string
     */
    protected function getCurrentQueryUrl(MagentoHttpRequest $request, array $query)
    {
        $params['_query'] = $query;
        $params['_escape'] = false;

        if ($originalUrl = $request->getQuery('__tw_original_url')) {

            if (!empty($request->getParam('q'))){
                $params['_query']['q'] = $request->getParam('q');
            }

            $newOriginalUrl = $this->url->getDirectUrl($this->getOriginalUrl($request), $params);

            return str_replace($this->url->getBaseUrl(), '', $newOriginalUrl);
        }

        $url = $this->url->getDirectUrl($this->getOriginalUrl($request), $params);

        if (strpos($url, 'catalogsearch') !== false) {
            $params['_current'] = true;
            $params['_use_rewrite'] = true;
            $url = $this->url->getUrl('*/*/*', $params);
        }

        return $url;
    }

    /**
     * Fetch current selected values
     *
     * @param MagentoHttpRequest $request
     * @param Item $item
     * @return string[]|string|null
     */
    protected function getRequestValues(MagentoHttpRequest $request, Item $item)
    {
        $filter = $item->getFilter();
        $settings = $filter
            ->getFacet()
            ->getFacetSettings();

        $urlKey = $filter->getUrlKey();

        $data = $request->getQuery($urlKey);
        if (!$data) {
            if ($settings->getIsMultipleSelect()) {
                return [];
            }

            return null;
        }

        if ($settings->getIsMultipleSelect()) {
            if (!is_array($data)) {
                $data = [$data];
            }
            return array_map('strval', $data);
        }

        return (string) $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getCategoryFilterSelectUrl(MagentoHttpRequest $request, Item $item): string
    {
        $category = $this->strategyHelper->getCategoryFromItem($item);
        if (!$this->getSearch($request)) {
            $categoryUrl = $category->getUrl();
            $categoryUrlPath = \parse_url($categoryUrl, PHP_URL_PATH);

            $url = $this->url->getDirectUrl(
                trim($categoryUrlPath, '/'),
                [
                    '_query' => $this->getAttributeFilters($request)
                ]
            );

            $url = str_replace($this->url->getBaseUrl(), '', $url);

            return $url;
        }

        $urlKey = $item->getFilter()->getUrlKey();

        $value[] = $category->getId();
        /** @var Category|CategoryInterface $category */
        while ((int)$category->getParentId() !== 1) {
            $value[] = $category->getParentId();
            $category = $category->getParentCategory();
        }

        $value = implode(self::CATEGORY_TREE_SEPARATOR, array_reverse($value));

        $query = [$urlKey => $value];
        return $this->getCurrentQueryUrl($request, $query);
    }

    /**
     * {@inheritdoc}
     */
    public function getCategoryFilterRemoveUrl(MagentoHttpRequest $request, Item $item): string
    {
        $filter = $item->getFilter();
        $urlKey = $filter->getUrlKey();

        $query = [$urlKey => $filter->getCleanValue()];
        return $this->getCurrentQueryUrl($request, $query);
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeSelectUrl(MagentoHttpRequest $request, Item $item): string
    {
        $settings = $item
            ->getFilter()
            ->getFacet()
            ->getFacetSettings();
        $attribute = $item->getAttribute();

        $urlKey = $settings->getUrlKey();
        $value = $attribute->getTitle();

        $values = $this->getRequestValues($request, $item);

        if ($settings->getIsMultipleSelect()) {
            $values[] = $value;
            $values = array_unique($values);

            $queryParams = [];
            foreach ($values as $key => $value) {
                $queryParams[] = '__VALUE.'.$key.'__';
            }

            $query = [$urlKey => $queryParams];
        } else {
            $query = [$urlKey => '__VALUE.0__'];
        }

        $hash = sha1(serialize($query));
        if (!isset($this->queryUrlCache[$hash])) {
            $this->queryUrlCache[$hash] = $this->getCurrentQueryUrl($request, $query);
        }
        $queryUrl = $this->queryUrlCache[$hash];

        if (!$settings->getIsMultipleSelect()) {
            $values[] = $value;
        }
        foreach ($values as $key => $value) {
            $queryUrl = str_replace('__VALUE.'.$key.'__', $value, $queryUrl);
        }

        return $queryUrl;
    }

    /**
     * @param MagentoHttpRequest $request
     * @param Item[] $filters
     * @return string
     */
    public function buildFilterUrl(MagentoHttpRequest $request, array $filters = []): string
    {
        $attributeFilters = $this->getAttributeFilters($request);
        return $this->getCurrentQueryUrl($request, $attributeFilters);
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeRemoveUrl(MagentoHttpRequest $request, Item $item): string
    {
        $filter = $item->getFilter();
        $settings = $filter->getFacet()->getFacetSettings();

        $urlKey = $settings->getUrlKey();

        if ($settings->getIsMultipleSelect()) {
            $attribute = $item->getAttribute();
            $value = $attribute->getTitle();
            $values = $this->getRequestValues($request, $item);

            $index = array_search($value, $values, false);
            if ($index !== false) {
                /** @noinspection OffsetOperationsInspection */
                unset($values[$index]);
            }

            $query = [$urlKey => $values];
        } else {
            $query = [$urlKey => $filter->getCleanValue()];
        }

        return $this->getCurrentQueryUrl($request, $query);
    }

    /**
     * {@inheritdoc}
     */
    protected function getCategoryFilters(MagentoHttpRequest $request)
    {
        $categories = $request->getQuery(self::PARAM_CATEGORY);
        $categories = explode(self::CATEGORY_TREE_SEPARATOR, $categories ?? '');
        $categories = array_map('intval', $categories);
        $categories = array_filter($categories);
        $categories = array_unique($categories);

        return $categories;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeFilters(MagentoHttpRequest $request)
    {
        $result = [];
        foreach ($request->getQuery() as $attribute => $value) {
            if (in_array(mb_strtolower($attribute), $this->ignoredQueryParameters, false)) {
                continue;
            }

            $result[$attribute] = $value;
        }
        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getSliderUrl(MagentoHttpRequest $request, Item $item): string
    {
        $query = [$item->getFilter()->getUrlKey() => '{{from}}-{{to}}'];

        return $this->getCurrentQueryUrl($request, $query);
    }

    /**
     * {@inheritdoc}
     */
    public function apply(MagentoHttpRequest $request, ProductNavigationRequest $navigationRequest): FilterApplierInterface
    {
        $attributeFilters = $this->getAttributeFilters($request);
        foreach ($attributeFilters as $attribute => $values) {
            if (!is_array($values)) {
                $values = [$values];
            }

            foreach ($values as $value) {
                $navigationRequest->addAttributeFilter($attribute, $value);
            }
        }

        $sortOrder = $this->getSortOrder($request);
        if ($sortOrder) {
            $navigationRequest->setOrder($sortOrder);
        }

        $page = $this->getPage($request);

        if ($page && (bool) $navigationRequest->getParameter('resetPagination') === false) {
            $navigationRequest->setPage($page);
        }

        $limit = $this->getLimit($request);
        if ($limit) {
            $navigationRequest->setLimit($limit);
        }

        // Add this only for ajax requests
        $path = $request->getPathInfo();
        if ($this->tweakwiseConfig->isPersonalMerchandisingActive() && ($request->isAjax() || $path === '/tweakwise/ajax/navigation/')) {
            $profileKey = $this->cookieManager->getCookie(
                $this->tweakwiseConfig->getPersonalMerchandisingCookieName(),
                null
            );

            if ($profileKey) {
                $navigationRequest->setProfileKey($profileKey);
            }
        }

        $categories = $this->getCategoryFilters($request);

        if ($categories) {
            $navigationRequest->addCategoryPathFilter($categories);
        }

        $search = $this->getSearch($request);
        if ($navigationRequest instanceof ProductSearchRequest && $search) {
            /** @var ProductSearchRequest $navigationRequest */
            $navigationRequest->setSearch($search);
        }

        return $this;
    }

    /**
     * @param MagentoHttpRequest $request
     * @return string|null
     */
    protected function getSortOrder(MagentoHttpRequest $request)
    {
        return $request->getQuery(self::PARAM_ORDER);
    }

    /**
     * @param MagentoHttpRequest $request
     * @return int|null
     */
    protected function getPage(MagentoHttpRequest $request)
    {
        return $request->getQuery(self::PARAM_PAGE);
    }

    /**
     * @param MagentoHttpRequest $request
     * @return int|null
     */
    protected function getLimit(MagentoHttpRequest $request)
    {
        return $request->getQuery(self::PARAM_LIMIT);
    }

    /**
     * @param MagentoHttpRequest $request
     * @return string|null
     */
    protected function getSearch(MagentoHttpRequest $request)
    {
        return $request->getQuery(self::PARAM_SEARCH);
    }

    /**
     * Determine if this UrlInterface is allowed in the current context
     *
     * @return boolean
     */
    public function isAllowed(): bool
    {
        return true;
    }

    /**
     * @return string
     */
    public function getOriginalUrl(MagentoHttpRequest $request) : string
    {
        if ($originalUrl = $request->getQuery('__tw_original_url')) {
            $urlArray = explode('/', $originalUrl);
            $newOriginalUrl = '';
            foreach ($urlArray as $url) {
                $newOriginalUrl .= '/' . filter_var($url, FILTER_SANITIZE_ENCODED);
            }

            //check if string should start with an / to prevent double slashes later
            if (mb_stripos($originalUrl, '/') === 0) {
                $newOriginalUrl = mb_substr($newOriginalUrl, 1);
            }

            // This seems ugly, perhaps there is another way?
            $query = [];
            // Add page and sort
            $sort = $request->getParam('product_list_order');
            $limit = $request->getParam('product_list_limit');
            $mode = $request->getParam('product_list_mode');

            if ($sort) {
                $query['product_list_order'] = $sort;
            }
            if ($limit) {
                $query['product_list_limit'] = $limit;
            }
            if ($mode) {
                $query['product_list_mode'] = $mode;
            }

            $newOriginalUrl = $this->url->getDirectUrl($newOriginalUrl, ['_query' => $query]);

            return str_replace($this->url->getBaseUrl(), '', $newOriginalUrl);
        }

        return $this->getCurrentUrl($request);
    }

    private function getCurrentUrl(MagentoHttpRequest $request) : string
    {
        $url = $request->getOriginalPathInfo();

        if (strpos($url, 'ajax/navigation') !== false) {
            $params['_current'] = true;
            $params['_use_rewrite'] = true;
            $params['_escape'] = false;
            return $this->url->getUrl('*/*/*', $params);
        }

        return str_replace($this->url->getBaseUrl(), '', $url);
    }
}
