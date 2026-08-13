<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Model\Catalog\Layer;

use Tweakwise\Magento2Tweakwise\Model\Client\Request\ProductNavigationRequest;

/**
 * NavigationContext variant for product count requests.
 * Overrides initializeRequest to force tn_ps=1 so only the total count is fetched
 * from the Tweakwise API, without retrieving any product data.
 */
class CountNavigationContext extends NavigationContext
{
    /**
     * @param ProductNavigationRequest $request
     * @return $this
     */
    protected function initializeRequest(ProductNavigationRequest $request): static
    {
        parent::initializeRequest($request);
        $request->setLimit(1);

        return $this;
    }
}
