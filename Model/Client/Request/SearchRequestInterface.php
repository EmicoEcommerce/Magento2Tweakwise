<?php

namespace Tweakwise\Magento2Tweakwise\Model\Client\Request;

interface SearchRequestInterface
{
    /**
     * @param string $query The search query, this will be added to the tweakwise request as 'tn_q'
     */
    public function setSearch(string $query): void;
}
