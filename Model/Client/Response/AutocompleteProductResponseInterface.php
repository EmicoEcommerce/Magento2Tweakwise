<?php

namespace Tweakwise\Magento2Tweakwise\Model\Client\Response;

interface AutocompleteProductResponseInterface
{
    /**
     * @return int[]
     */
    public function getProductIds();
}
