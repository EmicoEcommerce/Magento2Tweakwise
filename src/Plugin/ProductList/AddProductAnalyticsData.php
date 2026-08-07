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
use Tweakwise\Magento2TweakwiseExport\Model\Helper;

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
     * Keyed by the product's Magento id, always present on the add-to-cart form.
     */
    public function afterGetProductDetailsHtml(AbstractProduct $subject, string $html, Product $product): string
    {
        if (!$this->tweakwiseConfig->isAnalyticsEnabled()) {
            return $html;
        }

        $storeId = (int)$this->storeManager->getStore()->getId();
        $groupedProductsEnabled = $this->tweakwiseConfig->isGroupedProductsEnabled();

        // Prefer the child Tweakwise already matched (same field ProductListItem uses); guess via
        // GroupedProductIdResolver only for tiles Tweakwise didn't source (e.g. related/upsell).
        $tweakwiseMatchedChildId = $product->getData('tw_id');
        if ($tweakwiseMatchedChildId) {
            $rawId = $tweakwiseMatchedChildId . Helper::GROUP_CODE_DELIMITER . $product->getId();
        } elseif ($groupedProductsEnabled) {
            $rawId = (string)$this->groupedProductIdResolver->resolve($product);
        } else {
            $rawId = (string)$product->getId();
        }

        // Bypass the indexed final_price attribute (can be stale, e.g. under schedule-based indexing)
        // so this always matches the live calculation StashAddToCartEvent uses on a fresh product.
        $price = (float)$product->getPriceModel()->getFinalPrice(null, $product);

        $data = $this->jsonSerializer->serialize([
            'productKey' => $this->productKeyResolver->resolve($rawId, $storeId, $groupedProductsEnabled),
            'price' => $price,
        ]);

        return $html . '<script>(window.tweakwiseListingProductData = window.tweakwiseListingProductData || {})['
            . (int)$product->getId() . '] = ' . $data . ';</script>';
    }
}
