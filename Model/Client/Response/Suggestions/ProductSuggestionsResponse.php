<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Tweakwise\Magento2Tweakwise\Model\Client\Response\Suggestions;

use Tweakwise\Magento2Tweakwise\Model\Client\Response;
use Tweakwise\Magento2Tweakwise\Model\Client\Response\AutocompleteProductResponseInterface;

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
            ];
        }

        return $result;
    }

    public function setBlocks(array $blocks): self
    {
        $blocks = $this->normalizeArray($blocks, 'block');
        $items = [];
        foreach ($blocks as $block) {
            $blockItems = $block['items'] ?? [];
            $blockItems = $this->normalizeArray($blockItems, 'item');
            foreach ($blockItems as $item) {
                $items[] = $item;
            }
        }

        $this->setItems($items);
        $this->data['blocks'] = $blocks;
        return $this;
    }
}
