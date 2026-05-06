<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Model\AjaxResultInitializer;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\Framework\App\Request\Http as MagentoHttpRequest;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\NavigationContext;

/**
 * Initializes only the category layer for product count AJAX requests.
 * Applies filter query params directly to the NavigationContext so that the
 * count reflects the current checkbox selection, regardless of the URL strategy.
 */
class CategoryCountInitializer implements CountInitializerInterface
{
    /**
     * Query parameters that carry Tweakwise system data or Magento toolbar state,
     * not user-selected attribute filter values.
     */
    private const IGNORED_PARAMS = [
        '__tw_ajax_type',
        '__tw_object_id',
        '__tw_original_url',
        '__tw_hash',
        'p',
        'product_list_order',
        'product_list_limit',
        'product_list_mode',
        'q',
        '_',
        'categorie',
    ];

    /**
     * @param Resolver $layerResolver
     * @param Registry $registry
     * @param CategoryRepositoryInterface $categoryRepository
     * @param NavigationContext $navigationContext
     */
    public function __construct(
        private readonly Resolver $layerResolver,
        private readonly Registry $registry,
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly NavigationContext $navigationContext,
    ) {
    }

    /**
     * @param RequestInterface $request
     * @return void
     * @throws NoSuchEntityException
     */
    public function initializeForCount(
        RequestInterface $request
    ): void {
        $this->layerResolver->create(Resolver::CATALOG_LAYER_CATEGORY);
        $category = $this->initializeRegistry($request);
        $this->navigationContext->getRequest()->addCategoryFilter($category);
        $this->applyFilterParams($request);
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

        $categoryId = (int) $request->getParam('__tw_object_id') !== 0
            ? (int) $request->getParam('__tw_object_id')
            : 2;
        $category = $this->categoryRepository->get($categoryId);
        $this->registry->register('current_category', $category);

        return $category;
    }

    /**
     * Reads attribute filter values from the query string and applies them directly
     * to the Tweakwise navigation request, bypassing the URL strategy.
     * This ensures the count reflects all checked checkboxes in the form.
     *
     * @param RequestInterface $request
     * @return void
     */
    private function applyFilterParams(RequestInterface $request): void
    {
        if (!$request instanceof MagentoHttpRequest) {
            return;
        }

        $navigationRequest = $this->navigationContext->getRequest();

        foreach ($request->getQuery() as $attribute => $value) {
            if (in_array(strtolower((string) $attribute), self::IGNORED_PARAMS, true)) {
                continue;
            }

            $values = is_array($value) ? $value : [$value];
            foreach ($values as $singleValue) {
                if ($singleValue === '' || $singleValue === null) {
                    continue;
                }

                $navigationRequest->addAttributeFilter((string) $attribute, $singleValue);
            }
        }
    }
}
