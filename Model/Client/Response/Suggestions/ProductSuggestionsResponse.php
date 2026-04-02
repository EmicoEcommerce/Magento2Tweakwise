<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Tweakwise\Magento2Tweakwise\Model\Client\Response\Suggestions;

use Tweakwise\Magento2Tweakwise\Model\Client\Response;
use Tweakwise\Magento2Tweakwise\Model\Client\Response\AutocompleteProductResponseInterface;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\ItemType;

class ProductSuggestionsResponse extends Response implements AutocompleteProductResponseInterface
{
    /**
     * @return int[]
     */
    public function getProductIds()
    {
        $items = $this->getItems() ?? [];

        if (empty($items)) {
            return [];
        }

        return array_map(fn($item) => $this->helper->getStoreId($item->getId()), $items);
    }

    /**
     * @return array
     */
    public function getProductData()
    {
        $result = [];
        // @phpstan-ignore-next-line
        foreach ($this->getItems() as $item) {
            $result[] = [
                'id' => $this->helper->getStoreId($item->getId()),
                'tweakwise_price' => (float) $item->getPrice(),
                'tweakwise_final_price' => (float) $item->getFinalPrice(),
                'tweakwise_id' => $item->getId()
            ];
        }

        return $result;
    }

    /**
     * @param array $blocks
     *
     * @return $this
     */
    public function setBlocks(array $blocks): self
    {
        $blocks = $this->normalizeArray($blocks, 'block');
        if (empty($blocks)) {
            $this->data['blocks'] = [];
            return $this;
        }

        $allItems = [];
        $allGroups = [];

        foreach ($blocks as $key => $block) {
            if (!isset($block['items']) && !isset($block['groups'])) {
                continue;
            }

            if (isset($block['items'])) {
                array_push($allItems, ...$this->normalizeArray($block['items'], 'item'));
            }

            if (!isset($block['groups'])) {
                continue;
            }

            $blockGroups = $this->normalizeArray($block['groups'], 'group');
            array_push($allGroups, ...$blockGroups);
            $blocks[$key] = $this->resolveBlockGroups($block, $blockGroups);
        }

        $this->applyBackwardsCompatibleData($allGroups, $allItems);

        $this->data['blocks'] = $blocks;
        return $this;
    }

    /**
     * Convert a block's groups into items by running them through setGroups.
     *
     * @param array $block
     * @param array $blockGroups
     * @return array
     */
    private function resolveBlockGroups(array $block, array $blockGroups): array
    {
        $this->setGroups($blockGroups, false);
        $block['items'] = $this->convertItemTypesToArrays(
            $this->getDataValue('items') ?? []
        );
        unset($block['groups']);

        return $block;
    }

    /**
     * Set backwards-compatible items data from all collected groups and items.
     *
     * @param array $allGroups
     * @param array $allItems
     * @return void
     */
    private function applyBackwardsCompatibleData(array $allGroups, array $allItems): void
    {
        if (!empty($allGroups)) {
            $this->setGroups($allGroups);
        }

        if (!empty($allItems)) {
            $this->setItems($allItems);
        }
    }

    /**
     * Convert ItemType objects to plain arrays for blocks
     *
     * @param array $items
     * @return array
     */
    private function convertItemTypesToArrays(array $items): array
    {
        return array_map(
            fn($item) => $item instanceof ItemType ? $item->toArray() : $item,
            $items
        );
    }
}
