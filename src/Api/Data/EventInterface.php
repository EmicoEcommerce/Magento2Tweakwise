<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Api\Data;

use Magento\Framework\View\Element\Block\ArgumentInterface;

/**
 * Zero or more occurrences of a discrete analytics event (e.g. a placed order), as opposed to
 * TagInterface's single always-present page description. Wired into the "tweakwise.analytics" block's
 * "data_layer_events" argument per layout XML handle. Mirrors Yireo_GoogleTagManager2's
 * Api\Data\EventInterface.
 */
interface EventInterface extends ArgumentInterface
{
    /**
     * @return array[]
     */
    public function get(): array;
}
