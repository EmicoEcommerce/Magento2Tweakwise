<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Observer\Event;

use Exception;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Model\Quote\Item;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Tweakwise\Magento2Tweakwise\Model\Client;
use Tweakwise\Magento2Tweakwise\Model\Client\RequestFactory;
use Tweakwise\Magento2Tweakwise\Model\PersonalMerchandisingConfig;
use Tweakwise\Magento2Tweakwise\Service\Event\EventService;
use Tweakwise\Magento2TweakwiseExport\Model\Helper;

class SendAddToCartEvent implements ObserverInterface
{
    /**
     * @param PersonalMerchandisingConfig $config
     * @param RequestFactory $requestFactory
     * @param Client $client
     * @param LoggerInterface $logger
     * @param Helper $helper
     * @param StoreManagerInterface $storeManager
     * @param EventService $eventService
     */
    public function __construct(
        private readonly PersonalMerchandisingConfig $config,
        private readonly RequestFactory $requestFactory,
        private readonly Client $client,
        private readonly LoggerInterface $logger,
        private readonly Helper $helper,
        private readonly StoreManagerInterface $storeManager,
        private readonly EventService $eventService,
    ) {
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        try {
            if (!$this->config->isAnalyticsEnabled()) {
                return;
            }

            $this->sendAddToCartEvent($observer->getEvent()->getProduct(), $observer->getEvent()->getQuoteItem());
        } catch (Exception $e) {
            $this->logger->error('Tweakwise Add To Cart event could not be sent', ['message' => $e->getMessage()]);
            return;
        }
    }

    /**
     * @param ProductInterface $product
     * @param CartItemInterface $quoteItem
     * @return void
     * @throws NoSuchEntityException
     */
    protected function sendAddToCartEvent(ProductInterface $product, CartItemInterface $quoteItem): void
    {
        if (!$product instanceof Product || !$quoteItem instanceof Item) {
            return;
        }

        $tweakwiseRequest = $this->requestFactory->create();
        $totalAmount = $quoteItem->getQtyToAdd() * $product->getPriceModel()->getFinalPrice($quoteItem->getQtyToAdd(), $product);

        $tweakwiseRequest->setParameter('ProfileKey', $this->config->getProfileKey());
        $tweakwiseRequest->setParameter('SessionKey', $this->eventService->getSessionKey());
        $tweakwiseRequest->setParameter(
            'ProductKey',
            $this->helper->getTweakwiseId(
                (int)$this->storeManager->getStore()->getId(),
                (int)$quoteItem->getProductId()
            )
        );
        $tweakwiseRequest->setParameter('Quantity', (string)$quoteItem->getQtyToAdd());
        $tweakwiseRequest->setParameter('TotalAmount', (string)$totalAmount);
        $tweakwiseRequest->setPath('addtocart');

        $this->client->request($tweakwiseRequest);
    }
}
