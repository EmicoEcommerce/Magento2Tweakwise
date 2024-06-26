<?php

/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\FilterList;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Filter;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Filter\Item;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\FilterFactory;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\NavigationContext\CurrentContext;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\ProductNavigationRequest;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\ProductSearchRequest;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\FacetType\SettingsType;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\Layer\Filter\FilterInterface;
use Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory;
use Magento\Eav\Model\Entity\Attribute;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\FacetType;

class Tweakwise
{
    /**
     * @var FilterInterface[]
     */
    protected $filters;

    /**
     * @var FilterFactory
     */
    protected $filterFactory;

    /**
     * @var CurrentContext
     */
    protected $context;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var AttributeFactory
     */
    protected $attributeFactory;

    /**
     * Tweakwise constructor.
     *
     * @param FilterFactory $filterFactory
     * @param CurrentContext $context
     * @param Config $config
     * @param AttributeFactory $attributeFactory
     */
    public function __construct(
        FilterFactory $filterFactory,
        CurrentContext $context,
        Config $config,
        AttributeFactory $attributeFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly RequestInterface $request
    ) {
        $this->filterFactory = $filterFactory;
        $this->context = $context;
        $this->config = $config;
        $this->attributeFactory = $attributeFactory;
    }

    /**
     * @param Layer $layer
     * @return FilterInterface[]
     */
    public function getFilters(Layer $layer): array
    {
        if (!$this->filters) {
            $this->initFilters($layer);
        }

        return $this->filters;
    }

    /**
     * @param Layer $layer
     * @throws NoSuchEntityException
     */
    protected function initFilters(Layer $layer)
    {
        $request = $this->context->getRequest();
        if (!$request->hasParameter('tn_cid')) {
            $request->addCategoryFilter($this->getCurrentCategory($layer));
        }

        $facets = $this->context->getResponse()->getFacets();

        $facetAttributeNames = array_map(
            static function (FacetType $facet) {
                return $facet->getFacetSettings()->getAttributename();
            },
            $facets
        );

        $filterAttributes = $this->context
            ->getContext()
            ->getFilterAttributeMap($facetAttributeNames);

        $this->filters = [];
        foreach ($facets as $facet) {
            $attributeName = $facet->getFacetSettings()->getAttributename();
            $attribute = $filterAttributes[$attributeName]
                ?? $this->mockAttributeModel($attributeName);

            $filter = $this->filterFactory->create(
                [
                    'facet' => $facet,
                    'layer' => $layer,
                    'attribute' => $attribute
                ]
            );
            if ($this->shouldHideFacet($filter)) {
                continue;
            }

            $this->filters[] = $filter;

            foreach ($filter->getActiveItems() as $activeFilterItem) {
                if ($this->shouldHideActiveFilterItem($activeFilterItem, $request)) {
                    continue;
                }

                $layer->getState()->addFilter($activeFilterItem);
            }
        }
    }

    /**
     * @param Item $activeFilterItem
     * @param ProductNavigationRequest $request
     * @return bool
     */
    protected function shouldHideActiveFilterItem(Item $activeFilterItem, ProductNavigationRequest $request): bool
    {
        $source = $activeFilterItem
            ->getFilter()
            ->getFacet()
            ->getFacetSettings()
            ->getSource();
        // Add active category filter only on search pages
        $isCategory = $source === SettingsType::SOURCE_CATEGORY;
        if (!$isCategory) {
            return false;
        }

        // Add active category filter only on search pages
        return !($request instanceof ProductSearchRequest);
    }

    /**
     * @param Filter $filter
     * @return bool
     */
    protected function shouldHideFacet(Filter $filter): bool
    {
        if (!$this->config->getHideSingleOptions()) {
            return false;
        }

        return count($filter->getItems()) === 1;
    }

    /**
     * @param string $attributeName
     * @return Attribute
     */
    protected function mockAttributeModel(string $attributeName): Attribute
    {
        /** @var Attribute $attributeModel */
        $attributeModel = $this->attributeFactory->create();
        $attributeModel->setAttributeCode($attributeName);

        return $attributeModel;
    }

    /**
     * @param Layer $layer
     * @return CategoryInterface
     * @throws NoSuchEntityException
     */
    private function getCurrentCategory(Layer $layer): CategoryInterface
    {
        $layerCurrentCategory = $layer->getCurrentCategory();

        if (
            !$this->config->isPersonalMerchandisingActive() ||
            $layerCurrentCategory->getId() !== $this->storeManager->getStore()->getRootCategoryId()
        ) {
            return $layerCurrentCategory;
        }

        $requestCurrentCategoryId = $this->request->getParam('cc_id');
        if ($requestCurrentCategoryId) {
            try {
                return $this->categoryRepository->get($requestCurrentCategoryId);
            } catch (NoSuchEntityException $exception) {
                return $layerCurrentCategory;
            }
        }

        return $layerCurrentCategory;
    }
}
