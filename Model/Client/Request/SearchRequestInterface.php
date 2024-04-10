<?php

namespace Tweakwise\Magento2Tweakwise\Model\Client\Request;

interface SearchRequestInterface
{
    /**
     * @param string $query
     */
    public function setSearch(string $query): void;
}
