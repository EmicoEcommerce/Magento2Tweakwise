<?php

namespace Tweakwise\Magento2Tweakwise\Model;

class PersonalMerchandisingConfig extends Config
{
    /**
     * @param Store|null $store
     * @return bool
     */
    public function isAnalyticsEnabled(Store $store = null)
    {
        return (bool)$this->getStoreConfig('tweakwise/personal_merchandising/analytics_enabled', $store);
    }
}
