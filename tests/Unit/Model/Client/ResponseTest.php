<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model\Client;

use Emico\CodeCept\Test\Unit;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tweakwise\Magento2Tweakwise\Model\Client\Request;
use Tweakwise\Magento2Tweakwise\Model\Client\Response;
use Tweakwise\Magento2TweakwiseExport\Model\Helper;

class ResponseTest extends Unit
{
    use MockeryPHPUnitIntegration;

    private function createResponse(array $data): Response
    {
        return new Response(Mockery::mock(Helper::class), Mockery::mock(Request::class), $data);
    }

    public function testGetRequestIdReadsTheTwnRequestIdHeader(): void
    {
        $response = $this->createResponse(['headers' => ['Twn-Request-Id' => ['abc-123']]]);

        $this->assertSame('abc-123', $response->getRequestId());
    }

    public function testGetRequestIdIsCaseInsensitiveToTheHeaderName(): void
    {
        $response = $this->createResponse(['headers' => ['twn-request-id' => ['abc-123']]]);

        $this->assertSame('abc-123', $response->getRequestId());
    }

    public function testGetRequestIdReturnsEmptyStringWhenHeaderIsMissing(): void
    {
        $response = $this->createResponse(['headers' => ['Content-Type' => ['application/xml']]]);

        $this->assertSame('', $response->getRequestId());
    }

    public function testGetRequestIdReturnsEmptyStringWhenNoHeadersAreSet(): void
    {
        $response = $this->createResponse([]);

        $this->assertSame('', $response->getRequestId());
    }
}
