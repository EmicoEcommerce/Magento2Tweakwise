<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url;

use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Filter\Item;
use Tweakwise\Magento2TweakwiseExport\Model\Helper as ExportHelper;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\CatalogUrlRewrite\Model\CategoryUrlRewriteGenerator;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\UrlRewrite\Model\UrlFinderInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;

class StrategyHelper
{
    /**
     * @var ExportHelper
     */
    private $exportHelper;

    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Local cache: [categoryId][storeId] => CategoryInterface
     *
     * @var array<int, array<int, CategoryInterface>>
     */
    private array $categoryCache = [];

    /**
     * StrategyHelper constructor.
     * @param ExportHelper $exportHelper
     * @param CategoryRepositoryInterface $categoryRepository
     * @param StoreManagerInterface $storeManager
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param UrlFinderInterface $urlFinder
     */
    public function __construct(
        ExportHelper $exportHelper,
        CategoryRepositoryInterface $categoryRepository,
        StoreManagerInterface $storeManager,
        private readonly CategoryCollectionFactory $categoryCollectionFactory,
        private readonly UrlFinderInterface $urlFinder
    ) {
        $this->exportHelper = $exportHelper;
        $this->categoryRepository = $categoryRepository;
        $this->storeManager = $storeManager;
    }

    /**
     * Pre-load all category entities and their URL request paths in two batch queries.
     * Call this before iterating over filter items to avoid N+1 queries.
     *
     * @param Item[] $items
     * @param int $storeId
     * @return void
     */
    public function warmUp(array $items, int $storeId): void
    {
        $categoryIds = $this->collectCategoryIds($items);
        if (empty($categoryIds)) {
            return;
        }

        $uncachedIds = array_filter(
            $categoryIds,
            fn(int $id) => !isset($this->categoryCache[$id][$storeId])
        );

        if (empty($uncachedIds)) {
            return;
        }

        $collection = $this->categoryCollectionFactory->create();
        $collection->setStoreId($storeId);
        $collection->addAttributeToSelect(['name', 'url_key', 'url_path', 'is_active']);
        $collection->addFieldToFilter('entity_id', ['in' => array_values($uncachedIds)]);
        $collection->load();

        $loadedIds = [];

        /** @var CategoryInterface $category */
        foreach ($collection->getItems() as $category) {
            $id = (int) $category->getId();
            $this->categoryCache[$id][$storeId] = $category;
            $loadedIds[] = $id;
        }

        $this->preloadUrlRewrites($loadedIds, $storeId);
    }

    /**
     * @param Item $item
     * @return CategoryInterface
     * @throws NoSuchEntityException
     */
    public function getCategoryFromItem(Item $item): CategoryInterface
    {
        $tweakwiseCategoryId = (int) $item->getAttribute()->getAttributeId();
        $categoryId = (int) $this->exportHelper->getStoreId($tweakwiseCategoryId);

        try {
            $storeId = (int) $this->storeManager->getStore()->getId();
        } catch (NoSuchEntityException $exception) {
            $storeId = 0;
        }

        if (isset($this->categoryCache[$categoryId][$storeId])) {
            return $this->categoryCache[$categoryId][$storeId];
        }

        return $this->categoryRepository->get($categoryId, $storeId !== 0 ? $storeId : null);
    }

    /**
     * Recursively collect all Magento category IDs from a flat or nested item list.
     *
     * @param Item[] $items
     * @return int[]
     */
    private function collectCategoryIds(array $items): array
    {
        $ids = [];
        foreach ($items as $item) {
            $tweakwiseCategoryId = (int) $item->getAttribute()->getAttributeId();
            $ids[] = (int) $this->exportHelper->getStoreId($tweakwiseCategoryId);

            $children = $item->getChildren();
            if (empty($children)) {
                continue;
            }

            foreach ($this->collectCategoryIds($children) as $childId) {
                $ids[] = $childId;
            }
        }

        return array_unique($ids);
    }

    /**
     * Bulk-load URL rewrites for the given category IDs and pre-populate `request_path`
     * on each cached Category object so that Category::getUrl() skips its own DB lookup.
     *
     * @param int[] $categoryIds
     * @param int $storeId
     * @return void
     */
    private function preloadUrlRewrites(array $categoryIds, int $storeId): void
    {
        if (empty($categoryIds)) {
            return;
        }

        $rewrites = $this->urlFinder->findAllByData(
            [
                UrlRewrite::ENTITY_ID => $categoryIds,
                UrlRewrite::ENTITY_TYPE => CategoryUrlRewriteGenerator::ENTITY_TYPE,
                UrlRewrite::STORE_ID => $storeId,
                UrlRewrite::REDIRECT_TYPE => 0,
            ]
        );

        foreach ($rewrites as $rewrite) {
            $categoryId = (int) $rewrite->getEntityId();
            if (!isset($this->categoryCache[$categoryId][$storeId])) {
                continue;
            }

            $category = $this->categoryCache[$categoryId][$storeId];
            if (!$category instanceof DataObject) {
                continue;
            }

            if ($category->getData('url') !== null) {
                continue;
            }

            $category->setData('request_path', $rewrite->getRequestPath());
        }
    }
}
