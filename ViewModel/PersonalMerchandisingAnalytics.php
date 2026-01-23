<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\ViewModel;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2TweakwiseExport\Model\Helper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\RequestInterface;

/**
 * Class PersonalMerchandisingAnalytics
 *
 * ViewModel for personal merchandising analytics.
 */
class PersonalMerchandisingAnalytics implements ArgumentInterface
{
    /**
     * @param Config $tweakwiseConfig
     * @param Helper $helper
     * @param StoreManagerInterface $storeManager
     * @param RequestInterface $request
     * @param Json $jsonSerializer
     */
    public function __construct(
        private readonly Config $tweakwiseConfig,
        private readonly Helper $helper,
        private readonly StoreManagerInterface $storeManager,
        private readonly RequestInterface $request,
        private readonly Json $jsonSerializer,
    ) {
    }

    /**
     * Get the product key.
     *
     * @return string
     */
    public function getProductKey(): string
    {
        $productId = $this->request->getParam('id');
        $storeId = $this->storeManager->getStore()->getId();

        if (!$productId) {
            return '0';
        }

        return $this->helper->getTweakwiseId((int)$storeId, (int)$productId);
    }

    /**
     * Get the API URL.
     *
     * @return string
     */
    public function getApiUrl(): string
    {
        return 'https://navigator-analytics.tweakwise.com/api/';
    }

    /**
     * Get the instance key.
     *
     * @return string
     */
    public function getInstanceKey(): string
    {
        return $this->tweakwiseConfig->getGeneralAuthenticationKey();
    }

    /**
     * Get the cookie name.
     *
     * @return string
     */
    public function getCookieName(): string
    {
        return $this->tweakwiseConfig->getPersonalMerchandisingCookieName();
    }

    /**
     * Get the search query.
     *
     * @return string
     */
    public function getSearchQuery(): string
    {
        return $this->request->getParam('q') ?? '';
    }

    /**
     * Get the Tweakwise request ID.
     *
     * @return string
     */
    public function getTwRequestId(): string
    {
        return $this->request->getParam('tw_request_id') ?? '';
    }

    /**
     * @param array $analyticsTypes
     * @return string
     */
    public function getEventsData(array $analyticsTypes): string
    {
        $map = [
            'product'       => fn() => $this->getProductKey(),
            'search'        => fn() => $this->getSearchQuery(),
            'session_start' => fn() => 'session_start',
        ];

        $eventsData = array_map(
            fn($type) => [
                'type'  => $type,
                'value' => ($map[$type] ?? fn() => '')(),
            ],
            $analyticsTypes
        );

        return $this->jsonSerializer->serialize($eventsData);
    }
}
