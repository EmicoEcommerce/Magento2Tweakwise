<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Model\AjaxResultInitializer;

use Magento\Framework\App\RequestInterface;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\NavigationContext;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\PropertiesType;

/**
 * Initializes a CountNavigationContext for search pages and returns the total
 * product count from the Tweakwise API response.
 */
class SearchCountInitializer extends AbstractCountInitializer
{
    /**
     * @param NavigationContext $navigationContext
     */
    public function __construct(
        private readonly NavigationContext $navigationContext,
    ) {
    }

    /**
     * @param RequestInterface $request
     * @return int
     */
    public function initializeForCount(
        RequestInterface $request
    ): int {
        /** @var PropertiesType $properties */
        $properties = $this->navigationContext->getResponse()->getValue('properties');

        return $properties->getNumberOfItems();
    }
}
