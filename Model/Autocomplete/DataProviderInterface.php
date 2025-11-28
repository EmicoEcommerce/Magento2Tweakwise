<?php

namespace Tweakwise\Magento2Tweakwise\Model\Autocomplete;

use Magento\Search\Model\Autocomplete\DataProviderInterface as MagentoDataProviderInterface;

interface DataProviderInterface extends MagentoDataProviderInterface
{
    /**
     * Should indicate if this data provider is active
     *
     * @return bool
     */
    public function isSupported(): bool;
}
