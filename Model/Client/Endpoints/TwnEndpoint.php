<?php

namespace Tweakwise\Magento2Tweakwise\Model\Client\Endpoints;

use Tweakwise\Magento2Tweakwise\Model\Client\Response\HttpResponseInfo;
use Tweakwise\Magento2Tweakwise\Model\Client\Response\ResponseAction;

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

    public function processResponse(HttpResponseInfo $responseInfo)
    {
        $responseAction = new ResponseAction();

        if ($responseInfo->isTimedOut()) {
          $this->isOnline = true;
          $this->lastUsage = date_create('now');
          return $responseAction->retry;
        }

        if ($responseInfo->isSuccess()) {
            $this->isOnline = true;
            $this->lastUsage = date_create('now');
            return $responseAction->returnResult;
        }

        if ($responseInfo->isRetryable()) {
            $this->isOnline = false;
            $this->lastUsage = date_create('now');
            return $responseAction->retry;
        }

        return $responseAction->throwError;
    }
}
