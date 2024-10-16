<?php

/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2Tweakwise\Model;

use Magento\Framework\App\Cache\TypeListInterface;
use Tweakwise\Magento2Tweakwise\Exception\InvalidArgumentException;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\Strategy\QueryParameterStrategy;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\State;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\App\Config\Storage\WriterInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Config
{
    /**
     * Recommendation types
     */
    public const RECOMMENDATION_TYPE_UPSELL = 'upsell';
    public const RECOMMENDATION_TYPE_CROSSSELL = 'crosssell';
    public const RECOMMENDATION_TYPE_FEATURED = 'featured';
    public const RECCOMENDATION_TYPE_SHOPPINGCART = 'shoppingcart_crosssell';
    public const RECCOMENDATION_TYPE_SHOPPINGCART_FEATURED = 'shoppingcart_crosssell_featured';

    /**
     * Attribute names
     */
    public const ATTRIBUTE_FEATURED_TEMPLATE = 'tweakwise_featured_template';
    public const ATTRIBUTE_UPSELL_TEMPLATE = 'tweakwise_upsell_template';
    public const ATTRIBUTE_UPSELL_GROUP_CODE = 'tweakwise_upsell_group_code';
    public const ATTRIBUTE_CROSSSELL_TEMPLATE = 'tweakwise_crosssell_template';
    public const ATTRIBUTE_CROSSSELL_GROUP_CODE = 'tweakwise_crosssell_group_code';
    public const ATTRIBUTE_SHOPPINGCART_CROSSSELL_TEMPLATE = 'tweakwise_shoppingcart_crosssell_template';
    public const ATTRIBUTE_SHOPPINGCART_CROSSSELL_GROUP_CODE = 'tweakwise_shoppingcart_crosssell_group_code';
    public const ATTRIBUTE_SHOPPINGCART_CROSSSELL_FEATURED_TEMPLATE =
        'tweakwise_shoppingcart_crosssell_featured_template';
    public const ATTRIBUTE_FILTER_WHITELIST = 'tweakwise_filter_whitelist';
    public const ATTRIBUTE_FILTER_VALUES_WHITELIST = 'tweakwise_filter_values_whitelist';

    /**
     * @deprecated
     * @see Client::REQUEST_TIMEOUT
     */
    public const REQUEST_TIMEOUT = 5;

    /**
     * @deprecated
     * @see Client\EndpointManager::SERVER_URL
     */
    public const SERVER_URL = 'https://gateway.tweakwisenavigator.net';

    /**
     * @deprecated
     * @see Client\EndpointManager::FALLBACK_SERVER_URL
     */
    public const FALLBACK_SERVER_URL = 'https://gateway.tweakwisenavigator.com';

    private const PRODUCT_CARD_LIFETIME_XML_PATH = 'tweakwise/personal_merchandising/product_card_lifetime';

    /**
     * @var ScopeConfigInterface
     */
    protected $config;

    /**
     * @var Json
     */
    protected $jsonSerializer;

    /**
     * @var bool
     */
    protected $tweakwiseExceptionThrown = false;

    /**
     * @var string[]
     */
    protected $parsedFilterArguments;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var State
     */
    protected $state;

    /**
     * @var WriterInterface
     */
    protected $configWriter;

    /**
     * @var TypeListInterface
     */
    protected $cacheTypeList;

    /**
     * Export constructor.
     *
     * @param ScopeConfigInterface $config
     * @param Json $jsonSerializer
     * @param RequestInterface $request
     * @param State $state
     * @param WriterInterface $configWriter
     * @param TypeListInterface $cacheTypeList
     * @throws LocalizedException
     */
    public function __construct(
        ScopeConfigInterface $config,
        Json $jsonSerializer,
        RequestInterface $request,
        State $state,
        WriterInterface $configWriter,
        TypeListInterface $cacheTypeList
    ) {
        $this->config = $config;
        $this->jsonSerializer = $jsonSerializer;
        $this->request = $request;
        $this->state = $state;
        $this->configWriter = $configWriter;
        $this->cacheTypeList = $cacheTypeList;
    }

    /**
     * @param bool $thrown
     * @return $this
     */
    public function setTweakwiseExceptionThrown($thrown = true)
    {
        $this->tweakwiseExceptionThrown = (bool)$thrown;
        return $this;
    }

    public function getTweakwiseExceptionTrown()
    {
        return $this->tweakwiseExceptionThrown;
    }

    /**
     * @deprecated
     * @see \Tweakwise\Magento2Tweakwise\Model\Client\EndpointManager::getServerUrl()
     * @param bool $useFallBack
     * @return string
     */
    public function getGeneralServerUrl(bool $useFallBack = false)
    {
        return $useFallBack
            ? Client\EndpointManager::FALLBACK_SERVER_URL
            : Client\EndpointManager::SERVER_URL;
    }

    /**
     * @param Store|null $store
     * @return string
     */
    public function getGeneralAuthenticationKey(Store $store = null)
    {
        return (string)$this->getStoreConfig('tweakwise/general/authentication_key', $store);
    }

    /**
     * @deprecated
     * @see \Tweakwise\Magento2Tweakwise\Model\Client::REQUEST_TIMEOUT
     * @return int
     */
    public function getTimeout()
    {
        return Client::REQUEST_TIMEOUT;
    }

    /**
     * @param Store|null $store
     * @return bool
     */
    public function isLayeredEnabled(Store $store = null)
    {
        if ($this->tweakwiseExceptionThrown) {
            return false;
        }

        return (bool)$this->getStoreConfig('tweakwise/layered/enabled', $store);
    }

    /**
     * @param Store|null $store
     * @return bool
     */
    public function isAjaxFilters(Store $store = null)
    {
        return (bool)$this->getStoreConfig('tweakwise/layered/ajax_filters', $store);
    }

    /**
     * @param Store|null $store
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getCategoryAsLink(Store $store = null)
    {
        return (bool)$this->getStoreConfig('tweakwise/layered/category_links', $store);
    }

    /**
     * @param Store|null $store
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getHideSingleOptions(Store $store = null)
    {
        return (bool)$this->getStoreConfig('tweakwise/layered/hide_single_option', $store);
    }

    /**
     * @param Store|null $store
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getUseDefaultLinkRenderer(Store $store = null)
    {
        return (bool) $this->getStoreConfig('tweakwise/layered/default_link_renderer', $store);
    }

    /**
     * @param Store|null $store
     * @return bool
     */
    public function isFormFilters(Store $store = null)
    {
        return (bool)$this->getStoreConfig('tweakwise/layered/form_filters', $store);
    }

    /**
     * @param Store|null $store
     * @return string
     */
    public function getQueryFilterType(Store $store = null)
    {
        return (string)$this->getStoreConfig('tweakwise/layered/query_filter_type', $store);
    }

    /**
     * @param Store|null $store
     * @return array
     */
    public function getQueryFilterArguments(Store $store = null)
    {
        if ($this->parsedFilterArguments === null) {
            $arguments = $this->getStoreConfig('tweakwise/layered/query_filter_arguments', $store);
            $arguments = explode("\n", $arguments);
            $arguments = array_map('trim', $arguments);
            $arguments = array_filter($arguments);
            $this->parsedFilterArguments = $arguments;
        }

        return $this->parsedFilterArguments;
    }

    /**
     * @param Store|null $store
     * @return string
     */
    public function getQueryFilterRegex(Store $store = null)
    {
        return (string)$this->getStoreConfig('tweakwise/layered/query_filter_regex', $store);
    }

    /**
     * @param Store|null $store
     * @return string
     */
    public function getUrlStrategy(Store $store = null): string
    {
        $urlStrategy = $this->getStoreConfig('tweakwise/layered/url_strategy', $store);
        if (empty($urlStrategy)) {
            $urlStrategy = QueryParameterStrategy::class;
        }

        return $urlStrategy;
    }

    /**
     * @param Store|null $store
     * @return bool
     */
    public function isAutocompleteEnabled(Store $store = null)
    {
        if ($this->tweakwiseExceptionThrown) {
            return false;
        }

        return (bool)$this->getStoreConfig('tweakwise/autocomplete/enabled', $store);
    }

    /**
     * @param Store|null $store
     * @return bool
     */
    public function isSuggestionsAutocomplete(Store $store = null)
    {
        return (bool) $this->getStoreConfig('tweakwise/autocomplete/use_suggestions', $store);
    }

    /**
     * @param Store|null $store
     * @return bool
     */
    public function isAutocompleteProductsEnabled(Store $store = null)
    {
        return (bool)($this->getStoreConfig('tweakwise/autocomplete/show_products', $store) &&
            !$this->isSuggestionsAutocomplete());
    }

    /**
     * @param Store|null $store
     * @return bool
     */
    public function isAutocompleteSuggestionsEnabled(Store $store = null)
    {
        return (bool)($this->getStoreConfig('tweakwise/autocomplete/show_suggestions', $store) &&
            !$this->isSuggestionsAutocomplete());
    }

    /**
     * @param Store|null $store
     * @return int
     */
    public function showAutocompleteParentCategories(?Store $store = null)
    {
        return (bool)$this->getStoreConfig('tweakwise/autocomplete/show_parent_category', $store);
    }

    /**
     * @param Store|null $store
     * @return int
     */
    public function getAutocompleteMaxResults(Store $store = null)
    {
        return (int)$this->getStoreConfig('tweakwise/autocomplete/max_results', $store);
    }

    /**
     * @param Store|null $store
     * @return bool
     */
    public function isAutocompleteStayInCategory(Store $store = null)
    {
        return (bool)$this->getStoreConfig('tweakwise/autocomplete/in_current_category', $store);
    }

    /**
     * @param Store|null $store
     * @return int
     */
    public function isSearchEnabled(Store $store = null)
    {
        return (int)$this->getStoreConfig('tweakwise/search/enabled', $store);
    }

    /**
     * @param Store|null $store
     * @return int
     */
    public function getSearchTemplateId(Store $store = null)
    {
        return (int)$this->getStoreConfig('tweakwise/search/template', $store);
    }

    /**
     * @param Store|null $store
     * @return bool
     */
    public function isPersonalMerchandisingActive(Store $store = null)
    {
        return (bool) $this->getStoreConfig('tweakwise/personal_merchandising/enabled', $store)
            && $this->isAjaxFilters($store);
    }

    /**
     * @param Store|null $store
     * @return bool
     */
    public function isSearchBannersActive(Store $store = null)
    {
        return (bool) $this->getStoreConfig('tweakwise/search/searchbanner', $store)
            && $this->isSearchEnabled();
    }

    /**
     * @param Store|null $store
     * @return string
     */
    public function getPersonalMerchandisingCookieName(Store $store = null)
    {
        return (string) $this->getStoreConfig('tweakwise/personal_merchandising/cookie_name', $store);
    }

    /**
     * @param string $type
     * @param Store|null $store
     * @return bool
     */
    public function isRecommendationsEnabled($type, Store $store = null)
    {
        $this->validateRecommendationType($type);
        return (bool)$this->getStoreConfig(sprintf('tweakwise/recommendations/%s_enabled', $type), $store);
    }

    /**
     * @param Store|null $store
     * @return string
     */
    public function getShoppingcartCrossellType($store = null)
    {
        return (string)$this->getStoreConfig('tweakwise/recommendations/shoppingcart_crosssell_type', $store);
    }

    /**
     * @param string $type
     * @param Store|null $store
     * @return int
     */
    public function getRecommendationsTemplate($type, Store $store = null)
    {
        $this->validateRecommendationType($type);
        return (int)$this->getStoreConfig(sprintf('tweakwise/recommendations/%s_template', $type), $store);
    }

    /**
     * @param string $type
     * @param Store|null $store
     * @return int
     */
    public function getRecommendationsGroupCode($type, Store $store = null)
    {
        $this->validateRecommendationType($type);
        return $this->getStoreConfig(sprintf('tweakwise/recommendations/%s_group_code', $type), $store);
    }

    /**
     * @param Store|null $store
     * @return string
     */
    public function getRecommendationsFeaturedLocation(Store $store = null)
    {
        return (string)$this->getStoreConfig('tweakwise/recommendations/featured_location', $store);
    }

    /**
     * @param Store|null $store
     * @return string
     */
    public function getRecommendationsFeaturedCategory(Store $store = null)
    {
        return (string)$this->getStoreConfig('tweakwise/recommendations/featured_category', $store);
    }

    /**
     * @param Store|null $store
     * @return bool
     */
    public function isSeoEnabled(Store $store = null)
    {
        return (bool)$this->getStoreConfig('tweakwise/seo/enabled', $store);
    }

    /**
     * @param Store|null $store
     * @return array
     */
    public function getFilterWhitelist(Store $store = null)
    {
        return ConfigAttributeProcessService::extractFilterWhitelist(
            $this->getStoreConfig('tweakwise/seo/filter_whitelist', $store)
        );
    }

    /**
     * @param Store|null $store
     * @return array
     */
    public function getFilterValuesWhitelist(Store $store = null): array
    {
        return ConfigAttributeProcessService::extractFilterValuesWhitelist(
            $this->getStoreConfig('tweakwise/seo/filter_values_whitelist', $store)
        );
    }

    /**
     * @param Store|null $store
     * @return int
     */
    public function getLimitGroupCodeItems(Store $store = null): int
    {
        return (int) $this->getStoreConfig('tweakwise/recommendations/limit_group_code_items', $store);
    }

    /**
     * @param Store|null $store
     * @return int
     */
    public function getMaxAllowedFacets(Store $store = null)
    {
        return $this->getStoreConfig('tweakwise/seo/max_allowed_facets', $store);
    }

    /**
     * @param Store|null $store
     * @return mixed|string|null
     */
    public function getSearchLanguage(Store $store = null)
    {
        return $this->getStoreConfig('tweakwise/search/language', $store);
    }

    /**
     * @param string $path
     * @param Store|null $store
     * @return mixed|string|null
     * @throws LocalizedException
     */
    protected function getStoreConfig(string $path, Store $store = null)
    {
        if ($store) {
            return $store->getConfig($path);
        }

        $scope = ScopeInterface::SCOPE_STORE;
        $scopeId = null;

        //only get the parameters from the url in the admin
        if ($this->state->getAreaCode() === Area::AREA_ADMINHTML) {
            $scopeId = $this->request->getParam('store', null);
            if (!empty($this->request->getParam('website', null))) {
                $scope = ScopeInterface::SCOPE_WEBSITE;
                $scopeId = $this->request->getParam('website', null);
            }

            if ($scopeId === null) {
                $scope = 'default';
            }
        }

        return $this->config->getValue($path, $scope, $scopeId);
    }

    /**
     * @param string $type
     * @throws InvalidArgumentException
     */
    protected function validateRecommendationType($type)
    {
        if ($type === self::RECOMMENDATION_TYPE_UPSELL) {
            return;
        }

        if ($type === self::RECOMMENDATION_TYPE_CROSSSELL) {
            return;
        }

        if ($type === self::RECOMMENDATION_TYPE_FEATURED) {
            return;
        }

        if ($type == self:: RECCOMENDATION_TYPE_SHOPPINGCART) {
            return;
        }

        if ($type == self:: RECCOMENDATION_TYPE_SHOPPINGCART_FEATURED) {
            return;
        }

        throw new InvalidArgumentException(
            sprintf(
                '$type can be only of type string value: %s, %s, %s',
                self::RECOMMENDATION_TYPE_UPSELL,
                self::RECOMMENDATION_TYPE_CROSSSELL,
                self::RECOMMENDATION_TYPE_FEATURED,
                self:: RECCOMENDATION_TYPE_SHOPPINGCART,
                self::RECCOMENDATION_TYPE_SHOPPINGCART_FEATURED,
            )
        );
    }

    /**
     * @return string|null
     */
    public function getUserAgentString()
    {
        return $this->getStoreConfig('tweakwise/general/version') ?: null;
    }

    /**
     * @return string
     */
    public function getSalt(): string
    {
        $salt =  $this->getStoreConfig('tweakwise/general/salt' ?: null);
        if (empty($salt)) {
            $salt = sha1(random_bytes(18));
            $this->configWriter->save('tweakwise/general/salt', $salt, ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
            //clear config cache
            $this->cacheTypeList->cleanType('config');
        }

        return $salt;
    }

    /**
     * @param Store|null $store
     * @return int
     */
    public function isCategoryViewDefault(Store $store = null)
    {
        return $this->getStoreConfig('tweakwise/layered/default_category_view', $store);
    }

    /**
     * @return int
     */
    public function getProductCardLifetime(): int
    {
        return (int) $this->config->getValue(self::PRODUCT_CARD_LIFETIME_XML_PATH, ScopeInterface::SCOPE_STORE);
    }
}
