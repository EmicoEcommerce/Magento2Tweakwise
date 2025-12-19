<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Tweakwise\Magento2Tweakwise\Model\Client\Response\Suggestions;

use Tweakwise\Magento2Tweakwise\Model\Client\Response;
use Tweakwise\Magento2Tweakwise\Model\Client\Response\AutocompleteProductResponseInterface;
use function array_merge;

class ProductSuggestionsResponse extends Response implements AutocompleteProductResponseInterface
{
    /**
     * @return int[]
     */
    public function getProductIds()
    {
        $items = $this->getItems() ?? [];
        foreach ($this->getBlocks() ?? [] as $block) {
            foreach ($block as $bucket) {
                $items = array_merge($items, $bucket['items'] ?: []);
            }
        }

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
}
