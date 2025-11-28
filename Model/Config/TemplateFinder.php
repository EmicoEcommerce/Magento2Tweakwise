<?php

namespace Tweakwise\Magento2Tweakwise\Model\Config;

use Magento\Framework\Registry;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2Tweakwise\Model\Config\Source\RecommendationOption;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product;
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
     * TemplateFinder constructor.
     * @param Config $config
     */
    public function __construct(Config $config, Registry $registry, CategoryRepository $categoryRepository)
    {
        $this->config = $config;
        $this->registry = $registry;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @param Product $product
     * @param string $type
     * @return int|string
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
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
     * @param Category $category
     * @param string $type
     * @return int|string
     */
    public function forCategory(Category $category, $type)
    {
        $attribute = $this->getAttribute($type);
        $templateId = (int) $category->getData($attribute);

        if ($templateId === RecommendationOption::OPTION_CODE) {
            $groupAttribute = $this->getGroupCodeAttribute($type);
            return (string) $category->getData($groupAttribute);
        }

        if ($templateId) {
            return $templateId;
        }

        if ($category->getParentId()) {
            $parent = $category->getParentCategory();
            return $this->forCategory($parent, $type);
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
