<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Plugin\ProductList;

use Magento\Catalog\Block\Product\AbstractProduct;
use Magento\Catalog\Model\Product;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\StoreManagerInterface;
use Tweakwise\Magento2Tweakwise\Model\Analytics\GroupedProductIdResolver;
use Tweakwise\Magento2Tweakwise\Model\Analytics\ProductKeyResolver;
use Tweakwise\Magento2Tweakwise\Model\PersonalMerchandisingConfig;

class AddProductAnalyticsData
{
    public function __construct(
        private readonly PersonalMerchandisingConfig $tweakwiseConfig,
        private readonly StoreManagerInterface $storeManager,
        private readonly ProductKeyResolver $productKeyResolver,
        private readonly GroupedProductIdResolver $groupedProductIdResolver,
        private readonly Json $jsonSerializer,
    ) {
    }

    /**
     * Keyed by the product's Magento id, always present on the add-to-cart form regardless of how a
     * shop customises the tile markup - unlike a Tweakwise-specific data attribute would be.
     */
    public function afterGetProductDetailsHtml(AbstractProduct $subject, string $html, Product $product): string
    {
        if (!$this->tweakwiseConfig->isAnalyticsEnabled()) {
            return $html;
        }

        $storeId = (int)$this->storeManager->getStore()->getId();
        $groupedProductsEnabled = $this->tweakwiseConfig->isGroupedProductsEnabled();
        $rawId = $groupedProductsEnabled
            ? (string)$this->groupedProductIdResolver->resolve($product)
            : (string)$product->getId();

        $data = $this->jsonSerializer->serialize([
            'productKey' => $this->productKeyResolver->resolve($rawId, $storeId, $groupedProductsEnabled),
            'price' => (float)$product->getFinalPrice(),
        ]);

        return $html . '<script>(window.tweakwiseListingProductData = window.tweakwiseListingProductData || {})['
            . (int)$product->getId() . '] = ' . $data . ';</script>';
    }
}
