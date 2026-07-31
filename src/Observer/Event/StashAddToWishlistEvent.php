<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Observer\Event;

use Magento\Catalog\Api\Data\ProductExtension;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Throwable;
use Tweakwise\Magento2Tweakwise\Model\Analytics\CustomerSessionDataProvider;
use Tweakwise\Magento2Tweakwise\Model\PersonalMerchandisingConfig;
use Tweakwise\Magento2TweakwiseExport\Model\Helper;

class StashAddToWishlistEvent implements ObserverInterface
{
    public function __construct(
        private readonly PersonalMerchandisingConfig $config,
        private readonly LoggerInterface $logger,
        private readonly Helper $helper,
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

        $productId = (int)$product->getId();
        $groupCode = null;
        $storeId = (int)$this->storeManager->getStore()->getId();

        if ($this->config->isGroupedProductsEnabled()) {
            /** @var ProductExtension $extensionAttributes */
            $extensionAttributes = $product->getExtensionAttributes();
            $children = $extensionAttributes->getConfigurableProductLinks();
            $groupCode = (int)$this->helper->getTweakwiseId($storeId, $productId);
            if (!empty($children)) {
                $productId = (int)reset($children);
            }
        }

        $this->customerSessionDataProvider->add('addtowishlist_event', [
            'productKey' => $this->helper->getTweakwiseId(
                $storeId,
                $productId,
                $groupCode,
            ),
        ]);
    }
}
