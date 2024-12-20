<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Sales\Block\Order\Items;
use Tweakwise\Magento2Tweakwise\Exception\ApiException;
use Tweakwise\Magento2Tweakwise\Model\Client;
use Tweakwise\Magento2Tweakwise\Model\Client\RequestFactory;
use Magento\Framework\App\Request\Http as Request;
use Tweakwise\Magento2Tweakwise\Model\PersonalMerchandisingConfig;
use Tweakwise\Magento2TweakwiseExport\Model\Helper;
use Magento\Store\Model\StoreManagerInterface;

class TweakwiseCheckout implements ObserverInterface
{
    /**
     * Constructor.
     *
     * @param RequestFactory              $requestFactory
     * @param Client                      $client
     * @param Helper                      $helper
     * @param StoreManagerInterface       $storeManager
     * @param PersonalMerchandisingConfig $config
     */
    public function __construct(
        private readonly RequestFactory $requestFactory,
        private readonly Client $client,
        private readonly Helper $helper,
        private readonly StoreManagerInterface $storeManager,
        private readonly PersonalMerchandisingConfig $config
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
        if ($this->config->isAnalyticsEnabled()) {
            $order = $observer->getEvent()->getOrder();
            // Get the order items
            $items = $order->getAllItems();

            $this->sendCheckout($items);
        }
    }

    /**
     * @param Items $items
     *
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function sendCheckout($items): void
    {
        $storeId = (int)$this->storeManager->getStore()->getId();
        $profileKey = $this->config->getProfileKey();
        $tweakwiseRequest = $this->requestFactory->create();

        $tweakwiseRequest->setParameter('profileKey', $profileKey);
        $tweakwiseRequest->setPath('purchase');

        foreach ($items as $item) {
            $productTwId[] = $this->helper->getTweakwiseId($storeId, (int)$item->getProductId());
        }

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
