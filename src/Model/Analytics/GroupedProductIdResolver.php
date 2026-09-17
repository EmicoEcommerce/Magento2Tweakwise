<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Model\Analytics;

use Magento\Bundle\Model\Product\Type as Bundle;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Tweakwise\Magento2TweakwiseExport\Model\Helper;

class GroupedProductIdResolver
{
    public function resolve(Product $product): int|string
    {
        $productId = (int)$product->getId();

        if ($product->getTypeId() === Type::TYPE_SIMPLE) {
            return $productId;
        }

        $associatedProducts = $this->getAssociatedProducts($product);
        if (empty($associatedProducts)) {
            return $productId;
        }

        $firstAssociatedProduct = reset($associatedProducts);
        $simpleId = $firstAssociatedProduct->getId();
        if ($simpleId === 0 || $simpleId === $productId) {
            return $productId;
        }

        return $simpleId . Helper::GROUP_CODE_DELIMITER . $productId;
    }

    private function getAssociatedProducts(Product $product): array
    {
        $typeInstance = $product->getTypeInstance();
        return match (true) {
            $typeInstance instanceof Configurable => $typeInstance->getUsedProducts($product),
            $typeInstance instanceof Grouped => $typeInstance->getAssociatedProducts($product),
            $typeInstance instanceof Bundle => $typeInstance->getSelectionsCollection(
                $typeInstance->getOptionsIds($product),
                $product
            )->getItems(),
            default => [],
        };
    }
}
