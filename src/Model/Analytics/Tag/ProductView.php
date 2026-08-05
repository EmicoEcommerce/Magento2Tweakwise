<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Model\Analytics\Tag;

use Magento\Catalog\Model\Product;
use Magento\Store\Model\StoreManagerInterface;
use Tweakwise\Magento2Tweakwise\Api\Data\TagInterface;
use Tweakwise\Magento2Tweakwise\Model\Analytics\CurrentProductResolver;
use Tweakwise\Magento2Tweakwise\Model\Analytics\GroupedProductIdResolver;
use Tweakwise\Magento2Tweakwise\Model\Analytics\ProductKeyResolver;
use Tweakwise\Magento2Tweakwise\Model\Config;

class ProductView implements TagInterface
{
    public function __construct(
        private readonly Config $tweakwiseConfig,
        private readonly StoreManagerInterface $storeManager,
        private readonly CurrentProductResolver $currentProductResolver,
        private readonly ProductKeyResolver $productKeyResolver,
        private readonly GroupedProductIdResolver $groupedProductIdResolver,
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

        $product = $this->currentProductResolver->getProduct();
        if ($product === null) {
            $rawId = (string)$productId;
        } else {
            /** @var Product $product */
            $rawId = (string)$this->groupedProductIdResolver->resolve($product);
        }

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

        /** @var Product $product */
        return (float)$product->getFinalPrice();
    }
}
