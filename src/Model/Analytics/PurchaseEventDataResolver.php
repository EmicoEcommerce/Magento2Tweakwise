<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Model\Analytics;

use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item;
use Magento\Store\Model\StoreManagerInterface;
use Tweakwise\Magento2Tweakwise\Model\PersonalMerchandisingConfig;
use Tweakwise\Magento2TweakwiseExport\Model\Helper;

class PurchaseEventDataResolver
{
    public function __construct(
        private readonly ProductKeyResolver $productKeyResolver,
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
            fn (Item $item) => $this->productKeyResolver->resolve((string)$item->getProductId(), $storeId, false),
            $items
        );
    }

    /**
     * Groups order items by their top-level parent (a configurable or bundle parent line item, or a
     * plain item without children). A configurable line is reported as "childKey-parentKey"; any other
     * line (a plain item, a bundle or a grouped product) is reported as the parent product grouped with
     * itself ("parentKey-parentKey"), which is how the grouped export writes those items.
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

            if ($parent === null) {
                continue;
            }

            $childProductIdsByParentId[$parentId][] = (int)$originalItem->getProductId();
        }

        $productTwId = [];

        foreach ($parentItemsById as $parentId => $parentItem) {
            $childProductIds = $childProductIdsByParentId[$parentId] ?? [];
            $isConfigurableChild = $parentItem->getProductType() === Configurable::TYPE_CODE
                && count($childProductIds) === 1;

            $rawId = $isConfigurableChild
                ? $childProductIds[0] . Helper::GROUP_CODE_DELIMITER . $parentItem->getProductId()
                : (string)$parentItem->getProductId();

            $productTwId[] = $this->productKeyResolver->resolve($rawId, $storeId, true);
        }

        return $productTwId;
    }
}
