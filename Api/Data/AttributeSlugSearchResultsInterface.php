<?php

namespace Tweakwise\Magento2Tweakwise\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface AttributeSlugSearchResultsInterface extends SearchResultsInterface
{
    /**
     * @return AttributeSlugInterface[]
     */
    public function getItems(): array;
}
