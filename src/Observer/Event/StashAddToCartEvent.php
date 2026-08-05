<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Observer\Event;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Model\Quote\Item;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Throwable;
use Tweakwise\Magento2Tweakwise\Model\Analytics\CheckoutSessionDataProvider;
use Tweakwise\Magento2Tweakwise\Model\Analytics\ProductKeyResolver;
use Tweakwise\Magento2Tweakwise\Model\PersonalMerchandisingConfig;
use Tweakwise\Magento2TweakwiseExport\Model\Helper;

class StashAddToCartEvent implements ObserverInterface
{
    public function __construct(
        private readonly PersonalMerchandisingConfig $config,
        private readonly LoggerInterface $logger,
        private readonly ProductKeyResolver $productKeyResolver,
        private readonly StoreManagerInterface $storeManager,
        private readonly CheckoutSessionDataProvider $checkoutSessionDataProvider,
    ) {
    }

    public function execute(Observer $observer): void
    {
        try {
            if (!$this->config->isAnalyticsEnabled()) {
                return;
            }

            $this->stashAddToCartEvent($observer->getEvent()->getProduct(), $observer->getEvent()->getQuoteItem());
        } catch (Throwable $e) {
            $this->logger->error('Tweakwise add to cart event could not be stashed', ['message' => $e->getMessage()]);
            return;
        }
    }

    /**
     * @throws NoSuchEntityException
     */
    protected function stashAddToCartEvent(ProductInterface $product, CartItemInterface $quoteItem): void
    {
        if (!$product instanceof Product || !$quoteItem instanceof Item) {
            return;
        }

        // Qty-independent to match the instant client-side push (see ProductView::getPrice()).
        $totalAmount = $quoteItem->getQtyToAdd() * (float)$product->getFinalPrice();
        $storeId = (int)$this->storeManager->getStore()->getId();
        $groupedProductsEnabled = $this->config->isGroupedProductsEnabled();

        // $product is still the parent; Configurable::_prepareProduct() already pointed $quoteItem at the child.
        $childProductId = $quoteItem->getProductId();
        if ($groupedProductsEnabled && !empty($quoteItem->getQtyOptions())) {
            $childProductId = array_key_first($quoteItem->getQtyOptions());
        }

        $rawId = $groupedProductsEnabled
            ? $childProductId . Helper::GROUP_CODE_DELIMITER . $product->getId()
            : (string)$childProductId;

        $this->checkoutSessionDataProvider->add('addtocart_event', [
            'productKey' => $this->productKeyResolver->resolve($rawId, $storeId, $groupedProductsEnabled),
            'quantity' => (float)$quoteItem->getQtyToAdd(),
            'totalAmount' => (float)$totalAmount,
        ]);
    }
}
