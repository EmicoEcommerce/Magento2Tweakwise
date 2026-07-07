<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Model\AjaxResultInitializer;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\NavigationContext;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\PropertiesType;

/**
 * Initializes a CountNavigationContext for category pages and returns the total
 * product count from the Tweakwise API response.
 * Applies filter query params directly to the NavigationContext so that the
 * count reflects the current checkbox selection, regardless of the URL strategy.
 */
class CategoryCountInitializer extends AbstractCountInitializer
{
    /**
     * @param Registry $registry
     * @param CategoryRepositoryInterface $categoryRepository
     * @param NavigationContext $navigationContext
     */
    public function __construct(
        private readonly Registry $registry,
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly NavigationContext $navigationContext,
    ) {
    }

    /**
     * @param RequestInterface $request
     * @return int
     * @throws NoSuchEntityException
     */
    public function initializeForCount(
        RequestInterface $request
    ): int {
        $category = $this->initializeRegistry($request);
        $this->navigationContext->getRequest()->addCategoryFilter(
            $category instanceof Category ? $category : (int) $category->getId()
        );
        $this->applyFilterParams($request, $this->navigationContext);

        /** @var PropertiesType $properties */
        $properties = $this->navigationContext->getResponse()->getValue('properties');

        return $properties->getNumberOfItems();
    }

    /**
     * @param RequestInterface $request
     * @return CategoryInterface
     * @throws NoSuchEntityException
     */
    private function initializeRegistry(RequestInterface $request): CategoryInterface
    {
        $existing = $this->registry->registry('current_category');
        if ($existing) {
            return $existing;
        }

        $categoryId = (int) $request->getParam('__tw_object_id');
        if ($categoryId === 0) {
            throw new NoSuchEntityException(__('No category provided for product count request.'));
        }

        $category = $this->categoryRepository->get($categoryId);
        $this->registry->register('current_category', $category);

        return $category;
    }
}
