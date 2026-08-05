<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Model\Analytics;

use Tweakwise\Magento2TweakwiseExport\Model\Helper;

class ProductKeyResolver
{
    public function __construct(
        private readonly Helper $helper
    ) {
    }

    public function resolve(string $rawId, int $storeId, bool $groupedProductsEnabled): string
    {
        if (!$groupedProductsEnabled) {
            return $this->helper->getTweakwiseId($storeId, (int)$rawId);
        }

        $itemId = $rawId;
        $groupCode = null;
        if (str_contains($itemId, Helper::GROUP_CODE_DELIMITER)) {
            [$itemId, $groupCode] = explode(Helper::GROUP_CODE_DELIMITER, $itemId, 2);
        }

        $itemTweakwiseId = $this->helper->getTweakwiseId($storeId, (int)$itemId);
        $groupTweakwiseId = ($groupCode === null || $groupCode === '')
            ? $itemTweakwiseId
            : $this->helper->getTweakwiseId($storeId, (int)$groupCode);

        return $itemTweakwiseId . Helper::GROUP_CODE_DELIMITER . $groupTweakwiseId;
    }
}
