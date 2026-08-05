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
use Tweakwise\Magento2Tweakwise\Model\PersonalMerchandisingConfig;
use Tweakwise\Magento2TweakwiseExport\Model\Helper;

class StashAddToCartEvent implements ObserverInterface
{
    public function __construct(
        private readonly PersonalMerchandisingConfig $config,
        private readonly LoggerInterface $logger,
        private readonly Helper $helper,
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

        $totalAmount = $quoteItem->getQtyToAdd() * $product->getPriceModel()->getFinalPrice($quoteItem->getQtyToAdd(), $product);

        $productId = $quoteItem->getProductId();
        $groupCode = null;

        if ($this->config->isGroupedProductsEnabled()) {
            // Use $product (not $quoteItem->getProductId()) for the group code: for configurable products,
            // Magento's Configurable::_prepareProduct() already points the quote item at the *child* simple
            // product, while $product here is still the original parent passed by checkout_cart_product_add_after.
            $groupCode = (int)$this->helper->getTweakwiseId((int)$this->storeManager->getStore()->getId(), (int)$product->getId());
            if (!empty($quoteItem->getQtyOptions())) {
                $productId = array_key_first($quoteItem->getQtyOptions());
            }
        }

        $productId = $this->helper->getTweakwiseId(
            (int)$this->storeManager->getStore()->getId(),
            (int)$productId,
            $groupCode,
        );

        $this->checkoutSessionDataProvider->add('addtocart_event', [
            'productKey' => $productId,
            'quantity' => (float)$quoteItem->getQtyToAdd(),
            'totalAmount' => (float)$totalAmount,
        ]);
    }
}
