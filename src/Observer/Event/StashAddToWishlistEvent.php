<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Observer\Event;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Throwable;
use Tweakwise\Magento2Tweakwise\Model\Analytics\CustomerSessionDataProvider;
use Tweakwise\Magento2Tweakwise\Model\Analytics\GroupedProductIdResolver;
use Tweakwise\Magento2Tweakwise\Model\Analytics\ProductKeyResolver;
use Tweakwise\Magento2Tweakwise\Model\PersonalMerchandisingConfig;

class StashAddToWishlistEvent implements ObserverInterface
{
    public function __construct(
        private readonly PersonalMerchandisingConfig $config,
        private readonly LoggerInterface $logger,
        private readonly ProductKeyResolver $productKeyResolver,
        private readonly GroupedProductIdResolver $groupedProductIdResolver,
        private readonly StoreManagerInterface $storeManager,
        private readonly CustomerSessionDataProvider $customerSessionDataProvider,
    ) {
    }

    public function execute(Observer $observer): void
    {
        try {
            if (!$this->config->isAnalyticsEnabled()) {
                return;
            }

            $this->stashAddToWishlistEvent($observer->getEvent()->getProduct());
        } catch (Throwable $e) {
            $this->logger->error('Tweakwise add to wishlist event could not be stashed', ['message' => $e->getMessage()]);
            return;
        }
    }

    /**
     * @throws NoSuchEntityException
     */
    protected function stashAddToWishlistEvent(ProductInterface $product): void
    {
        if (!$product instanceof Product) {
            return;
        }

        $storeId = (int)$this->storeManager->getStore()->getId();
        $groupedProductsEnabled = $this->config->isGroupedProductsEnabled();
        $rawId = $groupedProductsEnabled
            ? (string)$this->groupedProductIdResolver->resolve($product)
            : (string)$product->getId();

        $this->customerSessionDataProvider->add('addtowishlist_event', [
            'productKey' => $this->productKeyResolver->resolve($rawId, $storeId, $groupedProductsEnabled),
        ]);
    }
}
