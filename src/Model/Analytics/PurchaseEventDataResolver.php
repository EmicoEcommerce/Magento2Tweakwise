<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Model\Analytics;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item;
use Magento\Store\Model\StoreManagerInterface;
use Tweakwise\Magento2Tweakwise\Model\PersonalMerchandisingConfig;
use Tweakwise\Magento2TweakwiseExport\Model\Helper;

class PurchaseEventDataResolver
{
    public function __construct(
        private readonly Helper $helper,
        private readonly StoreManagerInterface $storeManager,
        private readonly PersonalMerchandisingConfig $config,
    ) {
    }

    /**
     * @param Order $order
     * @return array{productKeys: string[], revenue: float}|array{}
     */
    public function resolve(Order $order): array
    {
        $items = $order->getAllItems();
        $storeId = (int)$this->storeManager->getStore()->getId();
        $productTwId = $this->config->isGroupedProductsEnabled()
            ? $this->resolveGroupedProductKeys($items, $storeId)
            : $this->resolveSimpleProductKeys($items, $storeId);

        return [
            'productKeys' => $productTwId,
            'revenue' => (float)$order->getBaseSubtotal(),
        ];
    }

    /**
     * @param Item[] $items
     * @return string[]
     */
    private function resolveSimpleProductKeys(array $items, int $storeId): array
    {
        $items = array_values(array_filter(
            $items,
            fn (Item $item): bool => $item->getParentItem() === null
        ));

        return array_map(
            fn (Item $item) => $this->helper->getTweakwiseId($storeId, (int)$item->getProductId()),
            $items
        );
    }

    /**
     * Groups order items by their top-level parent (a configurable or bundle parent line item, or a
     * plain item without children). A parent with exactly one child (the common configurable-product
     * case) is reported as "childKey-parentKey", matching the format used everywhere else in this
     * module. A parent with zero or more than one child (a plain item, or a bundle with multiple
     * selected components) is reported as just the parent product itself: with several children there
     * is no single "the" child to pick, so guessing one would misrepresent what was actually bought.
     *
     * @param Item[] $items
     * @return string[]
     */
    private function resolveGroupedProductKeys(array $items, int $storeId): array
    {
        $parentItemsById = [];
        $childProductIdsByParentId = [];

        foreach ($items as $originalItem) {
            // Item::getParentItem() is declared to return OrderItemInterface (which has no getId()),
            // even though at runtime it's always another Item or null - narrow it explicitly.
            $parent = $originalItem->getParentItem();
            $parentItem = $parent instanceof Item ? $parent : $originalItem;
            $parentId = (int)$parentItem->getId();
            $parentItemsById[$parentId] = $parentItem;

            if ($parent !== null) {
                $childProductIdsByParentId[$parentId][] = (int)$originalItem->getProductId();
            }
        }

        $productTwId = [];

        foreach ($parentItemsById as $parentId => $parentItem) {
            $childProductIds = $childProductIdsByParentId[$parentId] ?? [];

            if (count($childProductIds) === 1) {
                $groupCode = (int)$this->helper->getTweakwiseId($storeId, (int)$parentItem->getProductId());
                $productTwId[] = $this->helper->getTweakwiseId($storeId, $childProductIds[0], $groupCode);
            } else {
                $productTwId[] = $this->helper->getTweakwiseId($storeId, (int)$parentItem->getProductId());
            }
        }

        return $productTwId;
    }
}
