<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Model\AjaxResultInitializer;

use Magento\Framework\App\RequestInterface;

/**
 * Initializes only the layer (no layout) for product count AJAX requests.
 */
interface CountInitializerInterface
{
    /**
     * @param RequestInterface $request
     * @return void
     */
    public function initializeForCount(
        RequestInterface $request
    ): void;
}
