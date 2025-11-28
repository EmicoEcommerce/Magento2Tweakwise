<?php

namespace Tweakwise\Magento2Tweakwise\Model\Observer;

use Magento\Framework\Event\Observer;

class CatalogSearchLastPageRedirect extends CatalogLastPageRedirect
{
    /**
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        if ($this->config->isSearchEnabled()) {
            parent::execute($observer);
        }
    }
}
