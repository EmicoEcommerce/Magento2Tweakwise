<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Model\Analytics\Tag;

use Magento\Bundle\Model\Product\Type as Bundle;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\Store\Model\StoreManagerInterface;
use Tweakwise\Magento2Tweakwise\Api\Data\TagInterface;
use Tweakwise\Magento2Tweakwise\Model\Analytics\CurrentProductResolver;
use Tweakwise\Magento2Tweakwise\Model\Analytics\ProductKeyResolver;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2TweakwiseExport\Model\Helper;

class ProductView implements TagInterface
{
    public function __construct(
        private readonly Config $tweakwiseConfig,
        private readonly StoreManagerInterface $storeManager,
        private readonly CurrentProductResolver $currentProductResolver,
        private readonly ProductKeyResolver $productKeyResolver,
    ) {
    }

    public function get(): string
    {
        $productId = $this->currentProductResolver->getProductId();

        if (!$productId) {
            return '0';
        }

        $storeId = (int)$this->storeManager->getStore()->getId();
        $groupedProductsEnabled = $this->tweakwiseConfig->isGroupedProductsEnabled();

        if (!$groupedProductsEnabled) {
            return $this->productKeyResolver->resolve((string)$productId, $storeId, false);
        }

        $rawId = (string)$this->getGroupedProductId($productId);

        return $this->productKeyResolver->resolve($rawId, $storeId, true);
    }

    /**
     * Final price of the currently viewed product, used as the client-side add-to-cart mixin's fallback
     * on product view pages, where there's no per-tile DOM wrapper to read a price from (see
     * js/mixins/catalog-add-to-cart-mixin.js).
     */
    public function getPrice(): float
    {
        $product = $this->currentProductResolver->getProduct();
        if ($product === null) {
            return 0.0;
        }

        return (float)$product->getFinalPrice();
    }

    private function getGroupedProductId(int $productId): int|string
    {
        $product = $this->currentProductResolver->getProduct();
        if ($product === null || $product->getTypeId() === Type::TYPE_SIMPLE) {
            return $productId;
        }

        /** @var Product $product */
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
