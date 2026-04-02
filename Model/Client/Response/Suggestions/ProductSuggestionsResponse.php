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

        $items = [];
        $groups = [];

        foreach ($blocks as $key => $block) {
            if (!isset($block['items']) && !isset($block['groups'])) {
                continue;
            }

            if (isset($block['items'])) {
                foreach ($this->setBlockItems($block['items'] ?? []) as $item) {
                    $items[] = $item;
                }
            }

            if (!isset($block['groups'])) {
                continue;
            }

            $blockGroups = $this->normalizeArray($block['groups'] ?? [], 'group');
            foreach ($blockGroups as $group) {
                $groups[] = $group;
            }

            $this->setGroups($blockGroups, false);
            $blocks[$key]['items'] = $this->convertItemTypesToArrays(
                $this->getDataValue('items') ?? []
            );
            unset($blocks[$key]['groups']);
        }

        if (!empty($groups)) {
            //backwards compatibility, set all groups
            $this->setGroups($groups);
        }

        if (!empty($items)) {
            //backwards compatibility, set all items
            $this->setItems($items);
        }

        $this->data['blocks'] = $blocks;
        return $this;
    }

    /**
     * @param array $blockItems
     *
     * @return array
     */
    private function setBlockItems(array $blockItems): array
    {
        $items = [];
        if ($blockItems) {
            $blockItems = $this->normalizeArray($blockItems, 'item');
            foreach ($blockItems as $item) {
                $items[] = $item;
            }
        }
        return $items;
    }

    /**
     * Convert ItemType objects to plain arrays for block storage
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
