<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Model;

use Magento\Framework\App\Response\HttpInterface as HttpResponseInterface;
use Magento\Framework\Controller\AbstractResult;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Lightweight AJAX result that returns only the product count for the current filter selection.
 * Used when formFilters mode is active to preview the result count before applying filters.
 */
class AjaxProductCountResult extends AbstractResult
{
    /** @var int */
    private int $productCount = 0;

    /**
     * @param Json $serializer
     */
    public function __construct(
        private readonly Json $serializer,
    ) {
    }

    /**
     * @param int $count
     * @return void
     */
    public function setCount(int $count): void
    {
        $this->productCount = $count;
    }

    /**
     * @param HttpResponseInterface $response
     * @return $this
     */
    protected function render(HttpResponseInterface $response): static
    {
        $responseData = $this->serializer->serialize(['product_count' => $this->productCount]);

        $response->setHeader('Content-Type', 'application/json', true);
        $response->setHeader('Cache-Control', 'no-cache, no-store, must-revalidate', true);
        $response->appendBody($responseData);

        return $this;
    }
}
