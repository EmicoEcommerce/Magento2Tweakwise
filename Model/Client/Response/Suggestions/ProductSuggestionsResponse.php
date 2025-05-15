<?php

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
        $ids = [];
        foreach ($this->getItems() as $item) {
            $ids[] = $this->helper->getStoreId($item->getId());
        }

        return $ids;
    }

    /**
     * @return array
     */
    public function getProductData()
    {
        $result = [];
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
