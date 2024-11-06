<?php

namespace Tweakwise\Magento2Tweakwise\Block\Catalog\Product;

use Magento\Framework\View\Element\Template;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2TweakwiseExport\Model\Helper;
use

class ProductPage extends Template
{
    public function __construct(
        Template\Context $context,
        private readonly Config $tweakwiseConfig,
        private readonly Helper $helper,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getProfileKey()
    {
        //return $this->tweakwiseConfig->getOrSetProfileKey();
    }

    public function getProductKey()
    {
        return 'test';
    }

    public function getApiUrl()
    {
        return 'https://navigator-analytics.tweakwise.com/api/pageview';
    }

    public function getInstanceKey()
    {
        return $this->tweakwiseConfig->getGeneralAuthenticationKey();
    }
}
