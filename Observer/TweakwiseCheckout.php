<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order\Item;
use Psr\Log\LoggerInterface;
use Tweakwise\Magento2Tweakwise\Model\Client;
use Tweakwise\Magento2Tweakwise\Model\Client\RequestFactory;
use Tweakwise\Magento2Tweakwise\Model\PersonalMerchandisingConfig;
use Tweakwise\Magento2Tweakwise\Service\Event\EventService;
use Tweakwise\Magento2TweakwiseExport\Model\Helper;
use Magento\Store\Model\StoreManagerInterface;
use Throwable;

class TweakwiseCheckout implements ObserverInterface
{
    /**
     * @param RequestFactory $requestFactory
     * @param Client $client
     * @param Helper $helper
     * @param StoreManagerInterface $storeManager
     * @param PersonalMerchandisingConfig $config
     * @param EventService $eventService
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly RequestFactory $requestFactory,
        private readonly Client $client,
        private readonly Helper $helper,
        private readonly StoreManagerInterface $storeManager,
        private readonly PersonalMerchandisingConfig $config,
        private readonly EventService $eventService,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param Observer $observer
     *
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute(Observer $observer): void
    {
        try {
            if (!$this->config->isAnalyticsEnabled()) {
                return;
            }

            $order = $observer->getEvent()->getOrder();
            $totalExclTax = (float)$order->getBaseSubtotal();
            // Get the order items
            $items = $order->getAllItems();

            $this->sendCheckout($items, $totalExclTax);
        } catch (Throwable $e) {
            $this->logger->error('Tweakwise checkout event could not be sent', ['message' => $e->getMessage()]);
            return;
        }
    }

    /**
     * @param Item[] $items
     * @param float $totalExclTax
     *
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function sendCheckout($items, float $totalExclTax): void
    {
        if (empty($items) && !is_array($items)) {
            return;
        }

        $storeId = (int)$this->storeManager->getStore()->getId();
        $profileKey = $this->config->getProfileKey();
        $tweakwiseRequest = $this->requestFactory->create();

        $tweakwiseRequest->setParameter('SessionKey', $this->eventService->getSessionKey());
        $tweakwiseRequest->setProfileKey($profileKey);
        $tweakwiseRequest->setParameter('Revenue', (string)$totalExclTax);
        $tweakwiseRequest->setPath('purchase');

        if ($this->config->isGroupedProductsEnabled()) {
            $filteredItems = [];

            foreach ($items as $originalItem) {
                $returnedItem = $originalItem->getParentItem() ?? $originalItem;
                $returnedItem->setData('groupCode', $originalItem->getProductId());
                $filteredItems[(int)$returnedItem->getId()] = $returnedItem;
            }

            $items = array_values($filteredItems);
        } else {
            $items = array_values(array_filter(
                $items,
                fn (Item $item): bool => $item->getParentItem() === null
            ));
        }

        foreach ($items as $item) {
            $originalItem = $item->getProductId();
            if ($this->config->isGroupedProductsEnabled()) {
                $originalItem = $item->getData('groupCode');
                if (!empty($originalItem)) {
                    $groupcode = (int)$this->helper->getTweakwiseId($storeId, (int)$item->getProductId());
                }

                $productTwId[] = $this->helper->getTweakwiseId($storeId, (int)$originalItem, $groupcode ?? null);
            } else {
                $productTwId[] = $this->helper->getTweakwiseId($storeId, (int)$item->getProductId());
            }
        }

        $tweakwiseRequest->setParameterArray('ProductKeys', $productTwId);
        $this->client->request($tweakwiseRequest);
    }
}
