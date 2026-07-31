<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Observer\Event;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;
use Throwable;
use Tweakwise\Magento2Tweakwise\Model\Analytics\CheckoutSessionDataProvider;
use Tweakwise\Magento2Tweakwise\Model\Analytics\PurchaseEventDataResolver;
use Tweakwise\Magento2Tweakwise\Model\PersonalMerchandisingConfig;

class StashPurchaseEvent implements ObserverInterface
{
    /**
     * @param PurchaseEventDataResolver $purchaseEventDataResolver
     * @param CheckoutSessionDataProvider $checkoutSessionDataProvider
     * @param PersonalMerchandisingConfig $config
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly PurchaseEventDataResolver $purchaseEventDataResolver,
        private readonly CheckoutSessionDataProvider $checkoutSessionDataProvider,
        private readonly PersonalMerchandisingConfig $config,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer): void
    {
        try {
            if (!$this->config->isAnalyticsEnabled()) {
                return;
            }

            /** @var Order $order */
            $order = $observer->getEvent()->getOrder();
            $purchaseEventData = $this->purchaseEventDataResolver->resolve($order);
            if (empty($purchaseEventData)) {
                return;
            }

            $this->checkoutSessionDataProvider->add('purchase_event', $purchaseEventData);
        } catch (Throwable $e) {
            $this->logger->error('Tweakwise purchase event could not be stashed', ['message' => $e->getMessage()]);
            return;
        }
    }
}
