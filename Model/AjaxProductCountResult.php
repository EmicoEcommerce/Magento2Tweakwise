<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Model;

use Magento\Catalog\Model\Layer\Resolver;
use Magento\Framework\App\Response\HttpInterface as HttpResponseInterface;
use Magento\Framework\Controller\AbstractResult;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Lightweight AJAX result that returns only the product count for the current filter selection.
 * Used when formFilters mode is active to preview the result count before applying filters.
 */
class AjaxProductCountResult extends AbstractResult
{
    /**
     * @param Resolver $layerResolver
     * @param Json $serializer
     */
    public function __construct(
        private readonly Resolver $layerResolver,
        private readonly Json $serializer,
    ) {
    }

    /**
     * @param HttpResponseInterface $response
     * @return $this
     */
    protected function render(HttpResponseInterface $response): static
    {
        $productCount = $this->getProductCount();
        $responseData = $this->serializer->serialize(['product_count' => $productCount]);

        $response->setHeader('Content-Type', 'application/json', true);
        $response->setHeader('Cache-Control', 'no-cache, no-store, must-revalidate', true);
        $response->appendBody($responseData);

        return $this;
    }

    /**
     * @return int
     */
    private function getProductCount(): int
    {
        $layer = $this->layerResolver->get();
        return (int) $layer->getProductCollection()->getSize();
    }
}
