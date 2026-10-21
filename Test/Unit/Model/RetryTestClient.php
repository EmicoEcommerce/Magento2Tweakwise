<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Test\Unit\Model;

use GuzzleHttp\Client as HttpClient;
use Tweakwise\Magento2Tweakwise\Model\Client;
use Tweakwise\Magento2Tweakwise\Model\Client\Request;

class RetryTestClient extends Client
{
    public function setHttpClient(HttpClient $client): void
    {
        $this->client = $client;
    }

    public function runRequest(Request $request, bool $async = false)
    {
        return $this->doRequest($request, $async);
    }
}
