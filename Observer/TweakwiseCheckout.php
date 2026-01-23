<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Block\Order\Items;
use Tweakwise\Magento2Tweakwise\Model\Client;
use Tweakwise\Magento2Tweakwise\Model\Client\RequestFactory;
use Tweakwise\Magento2Tweakwise\Model\PersonalMerchandisingConfig;
use Tweakwise\Magento2Tweakwise\Service\Event\EventService;
use Tweakwise\Magento2TweakwiseExport\Model\Helper;
use Magento\Store\Model\StoreManagerInterface;

class TweakwiseCheckout implements ObserverInterface
{
    /**
     * @param RequestFactory $requestFactory
     * @param Client $client
     * @param Helper $helper
     * @param StoreManagerInterface $storeManager
     * @param PersonalMerchandisingConfig $config
     * @param EventService $eventService
     */
    public function __construct(
        private readonly RequestFactory $requestFactory,
        private readonly Client $client,
        private readonly Helper $helper,
        private readonly StoreManagerInterface $storeManager,
        private readonly PersonalMerchandisingConfig $config,
        private readonly EventService $eventService,
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
        if (!$this->config->isAnalyticsEnabled()) {
            return;
        }

        $order = $observer->getEvent()->getOrder();
        $totalExclTax = (float)$order->getBaseSubtotal();
        // Get the order items
        $items = $order->getAllItems();

        $this->sendCheckout($items, $totalExclTax);
    }

    /**
     * @param Items $items
     * @param float $totalExclTax
     *
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function sendCheckout($items, float $totalExclTax): void
    {
        $storeId = (int)$this->storeManager->getStore()->getId();
        $profileKey = $this->config->getProfileKey();
        $tweakwiseRequest = $this->requestFactory->create();

        $tweakwiseRequest->setParameter('SessionKey', $this->eventService->getSessionKey());
        $tweakwiseRequest->setParameter('ProfileKey', $profileKey);
        $tweakwiseRequest->setParameter('Revenue', (string)$totalExclTax);
        $tweakwiseRequest->setPath('purchase');

        // @phpstan-ignore-next-line
        foreach ($items as $item) {
            $productTwId[] = $this->helper->getTweakwiseId($storeId, (int)$item->getProductId());
        }

        // @phpstan-ignore-next-line
        $tweakwiseRequest->setParameterArray('ProductKeys', $productTwId);

        // @phpcs:disable
        try {
            $this->client->request($tweakwiseRequest);
        } catch (\Exception $e) {
            // Do nothing so that the checkout process can continue
        }
        // @phpcs:enable
    }
}
