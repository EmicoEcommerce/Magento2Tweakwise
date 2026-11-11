<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\LayoutInterface;
use Tweakwise\Magento2Tweakwise\Model\Config;

class RemoveEsiNavigation implements ObserverInterface
{
    public function __construct(
        private readonly Config $config,
    ) {
    }

    public function execute(Observer $observer): void
    {
        if ($observer->getData('full_action_name') !== 'magento_pagecache_block_esi') {
            return;
        }

        if (!$this->config->isLayeredEnabled() && !$this->config->isSearchEnabled()) {
            return;
        }

        // ESI reloads the page layout without the original search or category parameters.
        // Navigation unrelated to the requested block must not make API calls during layout preparation.
        /** @var LayoutInterface $layout */
        $layout = $observer->getData('layout');
        $layout->getUpdate()->addHandle('tweakwise_esi');
    }
}
