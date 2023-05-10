<?php

namespace Tweakwise\Magento2Tweakwise\Model\Client\Endpoints;

class TwnEndpoint
{
    private bool $isOnline;
    private $lastUsage;

    public string $hostname;

    public function __construct(String $hostname)
    {
        $this->isOnline = true;
        $this->lastUsage = date_create('now');
        $this->hostname = $hostname;
    }

    public function isTryable():bool
    {
        //TODO
        return true;
    }

    public function reset():void
    {
        $this->isOnline = true;
        $this->lastUsage = date_create('now');
    }

    public function ProcessResponse()
    {

    }
}
