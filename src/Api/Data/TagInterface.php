<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Api\Data;

use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * A single analytics value describing the current page (e.g. the viewed product's key, the search
 * term). Wired into the "tweakwise.analytics" block's "data_layer" argument per layout XML handle, so
 * adding a new page type's tag is a layout change, not a change to PersonalMerchandisingAnalytics.
 * Mirrors Yireo_GoogleTagManager2's Api\Data\TagInterface.
 */
interface TagInterface extends ArgumentInterface
{
    public function get(): string;
}
