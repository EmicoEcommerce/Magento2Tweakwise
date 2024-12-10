<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;
use Magento\Framework\Stdlib\CookieManagerInterface;
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
     * @param CookieManagerInterface      $cookieManager
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
    public function execute(Observer $observer)
    {
        if ($this->config->isAnalyticsEnabled('tweakwise/personal_merchandising/analytics_enabled')) {
            $order = $observer->getEvent()->getOrder();
            // Get the order items
            $items = $order->getAllItems();

            $this->sendCheckout($items);

        }
    }

    /**
     * @param $items
     *
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function sendCheckout($items)
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

        try {
            $this->client->request($tweakwiseRequest);
        } catch (\Exception $e) {
            // Do nothing
        }
    }
}
