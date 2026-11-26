<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Model\Assistant;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\Strategy\QueryParameterStrategy;
use Tweakwise\Magento2Tweakwise\Model\Client\Response\AssistantResponse;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\FacetType\SettingsType;
use Tweakwise\Magento2TweakwiseExport\Model\Helper;

/**
 * Maps an assistant response to the filter parameters and result URL of the storefront.
 *
 * Selected facets are matched to the facets of the returned navigation on attributename or urlkey and applied with the
 * facet urlkey, like regular filter links. Facets that cannot be matched are dropped. A selected category facet is
 * mapped back to the Magento category. On category pages a search term redirects to the search results page, because
 * category pages do not support searching within the category.
 */
class ResultMapper
{
    public const TOOLBAR_PARAMETERS = [
        QueryParameterStrategy::PARAM_ORDER,
        QueryParameterStrategy::PARAM_DIRECTION,
        QueryParameterStrategy::PARAM_LIMIT,
        QueryParameterStrategy::PARAM_MODE,
    ];

    private const RANGE_PATTERN = '/^\s*(-?\d+(?:[.,]\d+)?)\s*-\s*(-?\d+(?:[.,]\d+)?)\s*$/';

    /**
     * @param CategoryRepositoryInterface $categoryRepository
     * @param StoreManagerInterface $storeManager
     * @param UrlInterface $url
     * @param Helper $helper
     */
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly StoreManagerInterface $storeManager,
        private readonly UrlInterface $url,
        private readonly Helper $helper
    ) {
    }

    /**
     * @param AssistantResponse $response
     * @param PageContext $context
     * @param array<string, string> $toolbarParameters
     * @return array{url: string, filters: array<string, string|string[]>, ajax: bool}
     */
    public function map(AssistantResponse $response, PageContext $context, array $toolbarParameters = []): array
    {
        $facets = $this->getNavigationFacets($response->getNavigation());
        $filters = [];
        $category = null;

        foreach ($response->getSelectedFacets() as $selectedFacet) {
            $facet = $this->findFacet($facets, (string)$selectedFacet['attribute']);
            if ($facet === null) {
                continue;
            }

            $settings = $facet['facetsettings'] ?? [];
            if ($this->isCategoryFacet($settings)) {
                $category = $this->resolveCategory($facet, $selectedFacet['value']) ?? $category;
                continue;
            }

            $urlKey = (string)$settings['urlkey'];
            if (($settings['selectiontype'] ?? '') === SettingsType::SELECTION_TYPE_SLIDER) {
                $range = $this->normalizeRange($selectedFacet['value']);
                if ($range !== null) {
                    $filters[$urlKey] = $range;
                }

                continue;
            }

            $values = $this->splitValues($selectedFacet['value']);
            if ($values) {
                $filters[$urlKey] = $values;
            }
        }

        $searchTerm = trim((string)$response->getSearchTerm());
        $toolbar = $this->filterToolbarParameters($toolbarParameters);

        if ($context->isSearch() || $searchTerm !== '') {
            return $this->mapSearchResult($context, $filters, $toolbar, $searchTerm, $category);
        }

        $ajax = true;
        $baseUrl = $context->getBaseUrl();
        if ($category !== null && (int)$category->getId() !== $context->getCategoryId()) {
            // phpcs:ignore Magento2.Legacy.ObsoleteMethods.FoundObsoleteMethod
            $baseUrl = (string)$category->getUrl();
            $ajax = false;
        }

        return [
            'url' => $this->buildUrl($baseUrl, $filters + $toolbar),
            'filters' => $filters,
            'ajax' => $ajax,
        ];
    }

    /**
     * @param PageContext $context
     * @param array $filters
     * @param array $toolbar
     * @param string $searchTerm
     * @param CategoryInterface|null $category
     * @return array{url: string, filters: array<string, string|string[]>, ajax: bool}
     */
    private function mapSearchResult(
        PageContext $context,
        array $filters,
        array $toolbar,
        string $searchTerm,
        ?CategoryInterface $category
    ): array {
        if ($searchTerm === '') {
            $searchTerm = (string)$context->getSearchTerm();
        }

        $searchFilters = [QueryParameterStrategy::PARAM_SEARCH => $searchTerm];
        if ($category === null && !$context->isSearch()) {
            $category = $this->getCategory($context->getCategoryId());
        }

        $categoryPath = $category !== null ? $this->getCategoryPath($category) : '';
        if ($categoryPath !== '') {
            $searchFilters[QueryParameterStrategy::PARAM_CATEGORY] = $categoryPath;
        }

        $filters = $searchFilters + $filters;

        return [
            'url' => $this->buildUrl($this->url->getUrl('catalogsearch/result'), $filters + $toolbar),
            'filters' => $filters,
            'ajax' => $context->isSearch(),
        ];
    }

    /**
     * @param array $navigation
     * @return array
     */
    private function getNavigationFacets(array $navigation): array
    {
        $facets = $navigation['facets'] ?? [];
        if (isset($facets['facet'])) {
            $facets = $facets['facet'];
        }

        if (!is_array($facets)) {
            return [];
        }

        if (isset($facets['facetsettings'])) {
            $facets = [$facets];
        }

        return array_values(
            array_filter(
                $facets,
                static fn ($facet): bool => is_array($facet)
                    && !empty($facet['facetsettings']['urlkey'])
                    && is_string($facet['facetsettings']['urlkey'])
            )
        );
    }

    /**
     * @param array $facets
     * @param string $attribute
     * @return array|null
     */
    private function findFacet(array $facets, string $attribute): ?array
    {
        $attribute = mb_strtolower(trim($attribute));
        if ($attribute === '') {
            return null;
        }

        foreach ($facets as $facet) {
            $settings = $facet['facetsettings'];
            if (mb_strtolower((string)$settings['urlkey']) === $attribute) {
                return $facet;
            }
        }

        foreach ($facets as $facet) {
            $attributeName = $facet['facetsettings']['attributename'] ?? '';
            if (is_string($attributeName) && mb_strtolower($attributeName) === $attribute) {
                return $facet;
            }
        }

        return null;
    }

    /**
     * @param array $settings
     * @return bool
     */
    private function isCategoryFacet(array $settings): bool
    {
        return strtoupper((string)($settings['source'] ?? '')) === SettingsType::SOURCE_CATEGORY;
    }

    /**
     * @param mixed $value
     * @return string[]
     */
    private function splitValues($value): array
    {
        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        }

        if (!is_scalar($value)) {
            return [];
        }

        $values = array_map('trim', explode('|', (string)$value));

        return array_values(array_unique(array_filter($values, static fn (string $item): bool => $item !== '')));
    }

    /**
     * Converts a range such as "5-50" to the slider format {from}-{to}.
     *
     * @param mixed $value
     * @return string|null
     */
    private function normalizeRange($value): ?string
    {
        if (is_array($value) && count($value) === 2) {
            $value = implode('-', array_values($value));
        }

        if (!is_scalar($value) || !preg_match(self::RANGE_PATTERN, (string)$value, $matches)) {
            return null;
        }

        $from = (float)str_replace(',', '.', $matches[1]);
        $to = (float)str_replace(',', '.', $matches[2]);
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        return sprintf('%s-%s', $this->formatNumber($from), $this->formatNumber($to));
    }

    /**
     * @param float $number
     * @return string
     */
    private function formatNumber(float $number): string
    {
        return rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');
    }

    /**
     * @param array $facet
     * @param mixed $value
     * @return CategoryInterface|null
     */
    private function resolveCategory(array $facet, $value): ?CategoryInterface
    {
        $values = $this->splitValues($value);
        $value = end($values);
        if ($value === false) {
            return null;
        }

        $tweakwiseId = $this->findCategoryAttributeId($facet['attributes'] ?? [], $value) ?? $value;
        if (!ctype_digit($tweakwiseId) || strlen($tweakwiseId) < 6) {
            return null;
        }

        $storeId = (int)$this->storeManager->getStore()->getId();
        $categoryId = $this->helper->getStoreId((int)$tweakwiseId);
        if ((string)$this->helper->getTweakwiseId($storeId, $categoryId) !== $tweakwiseId) {
            return null;
        }

        return $this->getCategory($categoryId);
    }

    /**
     * @param mixed $attributes
     * @param string $value
     * @return string|null
     */
    private function findCategoryAttributeId($attributes, string $value): ?string
    {
        if (isset($attributes['attribute'])) {
            $attributes = $attributes['attribute'];
        }

        if (!is_array($attributes)) {
            return null;
        }

        if (isset($attributes['title'])) {
            $attributes = [$attributes];
        }

        foreach ($attributes as $attribute) {
            if (!is_array($attribute)) {
                continue;
            }

            $attributeId = isset($attribute['attributeid']) ? (string)$attribute['attributeid'] : null;
            $title = isset($attribute['title']) ? (string)$attribute['title'] : null;
            if ($attributeId !== null && ($attributeId === $value || mb_strtolower((string)$title) === mb_strtolower($value))) {
                return $attributeId;
            }

            $childId = $this->findCategoryAttributeId($attribute['children'] ?? [], $value);
            if ($childId !== null) {
                return $childId;
            }
        }

        return null;
    }

    /**
     * Returns the category when it exists and is visible in the current store.
     *
     * @param int $categoryId
     * @return CategoryInterface|null
     */
    private function getCategory(int $categoryId): ?CategoryInterface
    {
        if ($categoryId <= 0) {
            return null;
        }

        $store = $this->storeManager->getStore();
        try {
            /** @var Category $category */
            $category = $this->categoryRepository->get($categoryId, (int)$store->getId());
        } catch (NoSuchEntityException $e) {
            return null;
        }

        // @phpstan-ignore-next-line
        $rootCategoryId = (int)$store->getRootCategoryId();
        $pathIds = array_map('intval', explode('/', (string)$category->getPath()));
        if (!$category->getIsActive() || !in_array($rootCategoryId, $pathIds, true)) {
            return null;
        }

        return $category;
    }

    /**
     * Category path of Magento ids below the global root, as used by the "categorie" search filter.
     *
     * @param CategoryInterface $category
     * @return string
     */
    private function getCategoryPath(CategoryInterface $category): string
    {
        $pathIds = array_map('intval', explode('/', (string)$category->getPath()));
        $pathIds = array_filter($pathIds, static fn (int $id): bool => $id > 1);

        return implode(QueryParameterStrategy::CATEGORY_TREE_SEPARATOR, $pathIds);
    }

    /**
     * @param array $parameters
     * @return array<string, string>
     */
    private function filterToolbarParameters(array $parameters): array
    {
        $result = [];
        foreach (self::TOOLBAR_PARAMETERS as $name) {
            $value = $parameters[$name] ?? null;
            if (is_scalar($value) && preg_match('/^[\w\-]{1,64}$/', (string)$value)) {
                $result[$name] = (string)$value;
            }
        }

        return $result;
    }

    /**
     * @param string $baseUrl
     * @param array<string, string|string[]> $parameters
     * @return string
     */
    private function buildUrl(string $baseUrl, array $parameters): string
    {
        $query = [];
        foreach ($parameters as $name => $value) {
            if (is_array($value)) {
                foreach ($value as $item) {
                    $query[] = rawurlencode((string)$name) . '[]=' . rawurlencode((string)$item);
                }

                continue;
            }

            $query[] = rawurlencode((string)$name) . '=' . rawurlencode((string)$value);
        }

        $baseUrl = strtok($baseUrl, '?') ?: $baseUrl;

        return $query ? $baseUrl . '?' . implode('&', $query) : $baseUrl;
    }
}
