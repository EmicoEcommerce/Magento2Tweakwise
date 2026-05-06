<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Model\AjaxResultInitializer;

use Magento\Catalog\Model\Layer\Resolver;
use Magento\Framework\App\Request\Http as MagentoHttpRequest;
use Magento\Framework\App\RequestInterface;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\NavigationContext;

/**
 * Initializes only the search layer for product count AJAX requests.
 * Applies filter query params directly to the NavigationContext so that the
 * count reflects the current checkbox selection, regardless of the URL strategy.
 */
class SearchCountInitializer implements CountInitializerInterface
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
     * @param NavigationContext $navigationContext
     */
    public function __construct(
        private readonly Resolver $layerResolver,
        private readonly NavigationContext $navigationContext,
    ) {
    }

    /**
     * @param RequestInterface $request
     * @return void
     */
    public function initializeForCount(
        RequestInterface $request
    ): void {
        $this->layerResolver->create(Resolver::CATALOG_LAYER_SEARCH);
        $this->applyFilterParams($request);
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
