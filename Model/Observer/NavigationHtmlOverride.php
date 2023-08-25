<?php
/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Tweakwise\Magento2Tweakwise\Model\Observer;

use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\NavigationContext\CurrentContext;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\ProductSearchRequest;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\LayeredNavigation\Block\Navigation;

/**
 * Class NavigationHtmlOverride
 * @package Tweakwise\Magento2Tweakwise\Model\Observer
 *
 * Change template of the navigation block.
 * Changing the template depends on configuration so this could not be done in layout, also since the original definition
 * of the block is a virtualType it could not be done in a plugin, hence the observer.
 */
class NavigationHtmlOverride implements ObserverInterface
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var CurrentContext
     */
    protected $currentContext;

    /**
     * NavigationHtmlOverride constructor.
     *
     * @param Config $config
     * @param CurrentContext $currentContext
     */
    public function __construct(
        Config $config,
        CurrentContext $currentContext
    ) {
        $this->config = $config;
        $this->currentContext = $currentContext;
    }

    /**
     * {@inheritdoc}
     */
    public function execute(Observer $observer)
    {
        $block = $observer->getData('block');
        if (!$block instanceof Navigation) {
            return;
        }

        if ($this->config->getUseDefaultLinkRenderer()) {
            return;
        }

        $searchEnabled = $this->config->isSearchEnabled();
        $navigationEnabled = $this->config->isLayeredEnabled();

        $isSearch = $this->currentContext->getRequest() instanceof ProductSearchRequest;
        $isNavigation = !$isSearch;

        if ($isSearch && $searchEnabled) {
            $block->setTemplate('Tweakwise_Magento2Tweakwise::layer/view.phtml');
        }

        if ($isNavigation && $navigationEnabled) {
            $block->setTemplate('Tweakwise_Magento2Tweakwise::layer/view.phtml');
        }
    }
}
