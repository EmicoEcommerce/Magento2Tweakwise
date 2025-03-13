<?php

/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2Tweakwise\Model\Client\Response;

use Tweakwise\Magento2Tweakwise\Model\Client\Response;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\ItemType;

class RecommendationsResponse extends Response
{
    /**
     * @param array $recommendation
     */
    public function setRecommendation(array $recommendation)
    {
        if (!empty($recommendation) && !isset($recommendation['items'])) {
            // In this case multiple recommendations are given (group code)
            $recommendations = $recommendation;
            foreach ($recommendations as $recommendationEntry) {
                $this->setData($recommendationEntry);
            }

            return;
        }

        $this->setData($recommendation);
    }

    /**
     * @return ItemType[]
     */
    public function getItems(): array
    {
        return $this->getDataValue('items') ?: [];
    }

    public function replaceItems(array $items)
    {
        $this->data['items'] = $items;
    }

    /**
     * @return int[]
     */
    public function getProductIds(): array
    {
        $ids = [];
        foreach ($this->getItems() as $item) {
            $ids[] = $this->helper->getStoreId($item->getId());
        }

        return $ids;
    }
}
