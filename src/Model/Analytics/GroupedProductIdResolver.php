<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Model\Analytics;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Tweakwise\Magento2TweakwiseExport\Model\Helper;

class GroupedProductIdResolver
{
    public function resolve(Product $product): int|string
    {
        $productId = (int)$product->getId();

        if ($product->getTypeId() === Type::TYPE_SIMPLE) {
            return $productId;
        }

        $typeInstance = $product->getTypeInstance();
        if (!$typeInstance instanceof Configurable) {
            return $productId;
        }

        $usedProducts = $typeInstance->getUsedProducts($product);
        if (empty($usedProducts)) {
            return $productId;
        }

        $simpleId = (int)reset($usedProducts)->getId();
        if ($simpleId === 0 || $simpleId === $productId) {
            return $productId;
        }

        return $simpleId . Helper::GROUP_CODE_DELIMITER . $productId;
    }
}
