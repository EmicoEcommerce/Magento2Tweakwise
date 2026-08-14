<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Model\AjaxResultInitializer;

use Magento\Framework\App\RequestInterface;

/**
 * Initializes only the layer (no layout) for product count AJAX requests.
 * Returns the total product count for the current filter selection.
 */
interface CountInitializerInterface
{
    /**
     * @param RequestInterface $request
     * @return int
     */
    public function initializeForCount(
        RequestInterface $request
    ): int;
}
