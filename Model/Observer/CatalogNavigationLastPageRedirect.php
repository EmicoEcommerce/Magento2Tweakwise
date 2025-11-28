<?php

namespace Tweakwise\Magento2Tweakwise\Model\Observer;

use Magento\Framework\Event\Observer;

class CatalogNavigationLastPageRedirect extends CatalogLastPageRedirect
{
    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        if ($this->config->isLayeredEnabled()) {
            parent::execute($observer);
        }
    }
}
