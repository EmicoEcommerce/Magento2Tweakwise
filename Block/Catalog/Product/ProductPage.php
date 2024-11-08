<?php

namespace Tweakwise\Magento2Tweakwise\Block\Catalog\Product;

use Magento\Framework\View\Element\Template;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2Tweakwise\Model\PersonalMerchandising;
use Tweakwise\Magento2TweakwiseExport\Model\Helper;
use Magento\Store\Model\StoreManagerInterface;

class ProductPage extends Template
{
    public function __construct(
        Template\Context $context,
        private readonly Config $tweakwiseConfig,
        private readonly Helper $helper,
        private PersonalMerchandising $personalMerchandising,
        private readonly StoreManagerInterface $storeManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getProductKey()
    {
        $productId = $this->getRequest()->getParam('id');
        $storeId = $this->storeManager->getStore()->getId();

        return $this->helper->getTweakwiseId($storeId, $productId);
    }

    public function getApiUrl()
    {
        return 'https://navigator-analytics.tweakwise.com/api/pageview';
    }

    public function getInstanceKey()
    {
        return $this->tweakwiseConfig->getGeneralAuthenticationKey();
    }

    public function getCookieName()
    {
        return $this->tweakwiseConfig->getPersonalMerchandisingCookieName();
    }
}
