<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Block\Catalog;

use Magento\Framework\View\Element\Template;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2TweakwiseExport\Model\Helper;
use Magento\Store\Model\StoreManagerInterface;

class PersonalMerchandisingAnalytics extends Template
{
    public function __construct(
        Template\Context $context,
        private readonly Config $tweakwiseConfig,
        private readonly Helper $helper,
        private readonly StoreManagerInterface $storeManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getProductKey(): int
    {
        $productId = $this->getRequest()->getParam('id');
        $storeId = $this->storeManager->getStore()->getId();

        if (!$productId) {
            return 0;
        }

        return $this->helper->getTweakwiseId($storeId, $productId);
    }

    public function getApiUrl(): string
    {
        return 'https://navigator-analytics.tweakwise.com/api/';
    }

    public function getInstanceKey(): string
    {
        return $this->tweakwiseConfig->getGeneralAuthenticationKey();
    }

    public function getCookieName(): string
    {
        return $this->tweakwiseConfig->getPersonalMerchandisingCookieName();
    }

    public function getSearchQuery(): string
    {
        return $this->getRequest()->getParam('q');
    }
}
