<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model;

use GuzzleHttp\Psr7\Request as HttpRequest;
use Tweakwise\Magento2Tweakwise\Model\Client;
use Tweakwise\Magento2Tweakwise\Model\Client\Request;

class ClientExposed extends Client
{
    /**
     * @param Request $request
     * @return HttpRequest
     */
    public function createGetRequestPublic(Request $request): HttpRequest
    {
        return parent::createGetRequest($request);
    }

    /**
     * @param Request $request
     * @return HttpRequest
     */
    public function createPostRequestPublic(Request $request): HttpRequest
    {
        return parent::createPostRequest($request);
    }
}
