<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Model\Config;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Category\Collection;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\Registry;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2Tweakwise\Model\Config\Source\RecommendationOption;
use Magento\Catalog\Model\CategoryRepository;

class TemplateFinder
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var CategoryRepository
     */
    protected $categoryRepository;

    /**
     * @param Config $config
     */
    public function __construct(
        Config $config,
        Registry $registry,
        CategoryRepository $categoryRepository,
        private readonly CategoryCollectionFactory $categoryCollectionFactory,
    ) {
        $this->config = $config;
        $this->registry = $registry;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @param Product $product
     * @param string $type
     * @return int|string
     * @SuppressWarnings("PHPMD.CyclomaticComplexity")
     * @SuppressWarnings("PHPMD.NPathComplexity")
     */
    public function forProduct(Product $product, $type)
    {
        $attribute = $this->getAttribute($type);
        $templateId = (int) $product->getData($attribute);

        //first try product template
        if ($templateId === RecommendationOption::OPTION_CODE) {
            $groupAttribute = $this->getGroupCodeAttribute($type);
            return (string) $product->getData($groupAttribute);
        }

        if ($templateId) {
            return $templateId;
        }

        //try template from the most recent category
        $category = $this->registry->registry('current_category');

        if ($category) {
            $templateId = $this->forCategory($category, $type);

            if ($templateId) {
                return $templateId;
            }
        }

        //try default product category
        $category = $product->getCategory();
        // @phpstan-ignore-next-line
        if ($category) {
            $templateId = $this->forCategory($category, $type);
        }

        if ($templateId) {
            return $templateId;
        }

        //try template for other categories of the product
        $categoryIds = $product->getCategoryIds();

        foreach ($categoryIds as $categoryId) {
            $category = $this->categoryRepository->get($categoryId);
            // @phpstan-ignore-next-line
            $templateId = $this->forCategory($category, $type);

            if ($templateId) {
                return $templateId;
            }
        }

        $defaultTemplateId = $this->config->getRecommendationsTemplate($type);

        if ($defaultTemplateId === RecommendationOption::OPTION_CODE) {
            return $this->config->getRecommendationsGroupCode($type);
        }

        return $defaultTemplateId;
    }

    /**
     * Find the recommendation template for a category by walking up the category path.
     * Uses a single batch collection load for all path categories to avoid N+1 DB queries.
     *
     * @param Category $category
     * @param string $type
     * @return int|string|null
     */
    public function forCategory(Category $category, $type)
    {
        $attribute = $this->getAttribute($type);
        $groupAttribute = $this->getGroupCodeAttribute($type);
        $pathIds = array_reverse($category->getPathIds());

        $collection = $this->categoryCollectionFactory->create()
            ->addAttributeToFilter('entity_id', ['in' => $pathIds])
            ->addAttributeToSelect([$attribute, $groupAttribute]);

        /** @var array<int, Category> $categoriesById */
        $categoriesById = $this->createCategoriesByIdLookup($collection);

        foreach ($pathIds as $pathId) {
            $pathCategory = $categoriesById[(int) $pathId] ?? null;

            if (!$pathCategory) {
                continue;
            }

            $value = $this->resolveRecommendationValue(
                $pathCategory,
                $attribute,
                $groupAttribute,
            );

            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @return array<int, Category>
     */
    private function createCategoriesByIdLookup(Collection $collection): array
    {
        $categoriesById = [];
        foreach ($collection as $item) {
            $categoriesById[(int) $item->getId()] = $item;
        }

        return $categoriesById;
    }

    /**
     * @return int|string|null
     */
    private function resolveRecommendationValue(Category $category, string $attribute, string $groupAttribute)
    {
        $templateId = (int) $category->getData($attribute);

        if ($templateId === RecommendationOption::OPTION_CODE) {
            return (string) $category->getData($groupAttribute);
        }

        if ($templateId) {
            return $templateId;
        }

        return null;
    }

    /**
     * @param string $type
     * @return string
     */
    protected function getAttribute($type)
    {
        return sprintf('tweakwise_%s_template', $type);
    }

    /**
     * @param string $type
     * @return string
     */
    protected function getGroupCodeAttribute($type)
    {
        return sprintf('tweakwise_%s_group_code', $type);
    }
}
