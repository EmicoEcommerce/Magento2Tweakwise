<?php

/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2Tweakwise\Model\Client\Response;

use Tweakwise\Magento2Tweakwise\Model\Client\Request;
use Tweakwise\Magento2Tweakwise\Model\Client\Response;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\ItemType;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2TweakwiseExport\Model\Helper;

class RecommendationsResponse extends Response
{
    /**
     * @var bool
     */
    private bool $proccessedGroupedProducts = false;

    /**
     * RecommendationsResponse constructor.
     *
     * @param Helper $helper
     * @param Request $request
     * @param Config $config
     * @param array|null $data
     */
    public function __construct(
        Helper $helper,
        Request $request,
        private readonly Config $config,
        array $data = null
    ) {
        parent::__construct(
            $helper,
            $request,
            $data
        );
    }

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
        if ($this->config->isGroupedProductsEnabled() && !$this->proccessedGroupedProducts) {
            // Manually group items since recommendations doesn't have a grouped call yet.
            $items = parent::getItems();
            $groups = [];
            if (empty($items)) {
                return $this->data['items'];
            }

            foreach ($items as $item) {
                $groups['group'][] = [
                    'code' => $item->getGroupCodeFromAttributes(),
                    'items' => ['item' => [$item->data]],
                ];
            }

            $this->setGroups($groups);
            $this->proccessedGroupedProducts = true;
        }

        return $this->data['items'];
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
