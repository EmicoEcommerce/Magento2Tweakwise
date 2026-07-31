<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Model\Analytics\Tag;

use Magento\Bundle\Model\Product\Type as Bundle;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\Store\Model\StoreManagerInterface;
use Tweakwise\Magento2Tweakwise\Api\Data\TagInterface;
use Tweakwise\Magento2Tweakwise\Model\Analytics\ProductKeyResolver;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2TweakwiseExport\Model\Helper;

/**
 * Resolves the productKey of the currently viewed product (request param "id"). Wired only into
 * catalog_product_view.xml's "data_layer" argument, mirroring Yireo_GoogleTagManager2's
 * DataLayer\Tag\Product\CurrentProduct.
 */
class ProductView implements TagInterface
{
    public function __construct(
        private readonly Config $tweakwiseConfig,
        private readonly StoreManagerInterface $storeManager,
        private readonly RequestInterface $request,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly ProductKeyResolver $productKeyResolver,
    ) {
    }

    public function get(): string
    {
        $productId = $this->request->getParam('id');

        if (!$productId) {
            return '0';
        }

        $storeId = (int)$this->storeManager->getStore()->getId();
        $groupedProductsEnabled = $this->tweakwiseConfig->isGroupedProductsEnabled();

        if (!$groupedProductsEnabled) {
            return $this->productKeyResolver->resolve((string)$productId, $storeId, false);
        }

        $rawId = (string)$this->getGroupedProductId((int)$productId);

        return $this->productKeyResolver->resolve($rawId, $storeId, true);
    }

    /**
     * Final price of the currently viewed product, used as the client-side add-to-cart mixin's fallback
     * on product view pages, where there's no per-tile DOM wrapper to read a price from (see
     * js/mixins/catalog-add-to-cart-mixin.js).
     */
    public function getPrice(): float
    {
        $productId = $this->request->getParam('id');
        if (!$productId) {
            return 0.0;
        }

        try {
            $product = $this->productRepository->getById((int)$productId);
        } catch (NoSuchEntityException $e) {
            return 0.0;
        }

        return (float)$product->getFinalPrice();
    }

    private function getGroupedProductId(int $productId): int|string
    {
        try {
            /** @var Product $product */
            $product = $this->productRepository->getById($productId);
        } catch (NoSuchEntityException $e) {
            return $productId;
        }

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
