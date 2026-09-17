<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model;

use Emico\CodeCept\Test\Unit;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Mockery;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2Tweakwise\Model\Client;
use Tweakwise\Magento2Tweakwise\Model\Client\EndpointManager;
use Tweakwise\Magento2Tweakwise\Model\Client\Request;
use Tweakwise\Test\Support\UnitTester;

class ClientTest extends Unit
{
    protected UnitTester $tester;

    /**
     * @return void
     */
    public function testCreateGetRequestAddsInternalTrafficHeaderForMatchingIp(): void
    {
        $config = Mockery::mock(Config::class);
        $config->shouldReceive('getInternalIpAddresses')->andReturn(['84.35.11.109']);
        $config->shouldReceive('getGeneralAuthenticationKey')->andReturn('12345abc');
        $config->shouldReceive('getRecommendationsFeaturedCategory')->andReturn(false);

        $remoteAddress = Mockery::mock(RemoteAddress::class);
        $remoteAddress->shouldReceive('getRemoteAddress')->andReturn('84.35.11.109');

        $endpointManager = Mockery::mock(EndpointManager::class);
        $endpointManager->shouldReceive('getServerUrl')->andReturn('https://gateway.tweakwisenavigator.net');

        $this->tester->mockService(Config::class, $config);
        $this->tester->mockService(RemoteAddress::class, $remoteAddress);
        $this->tester->mockService(EndpointManager::class, $endpointManager);

        /** @var Client $client */
        $client = $this->tester->getObjectManager()->get(Client::class);

        $request = $this->createMock(Request::class);
        $request->method('getPath')->willReturn('navigation');
        $request->method('getPathSuffix')->willReturn('');
        $request->method('getParameters')->willReturn(['tn_ps' => '12']);

        $httpRequest = $client->createGetRequest($request);

        $this->assertSame('Internal-Traffic', $httpRequest->getHeaderLine('TWN-Source'));
    }

    /**
     * @return void
     */
    public function testCreateGetRequestSkipsInternalTrafficHeaderForNonMatchingIp(): void
    {
        $config = Mockery::mock(Config::class);
        $config->shouldReceive('getInternalIpAddresses')->andReturn(['10.0.0.99']);
        $config->shouldReceive('getGeneralAuthenticationKey')->andReturn('12345abc');
        $config->shouldReceive('getRecommendationsFeaturedCategory')->andReturn(false);

        $remoteAddress = Mockery::mock(RemoteAddress::class);
        $remoteAddress->shouldReceive('getRemoteAddress')->andReturn('84.35.11.109');

        $endpointManager = Mockery::mock(EndpointManager::class);
        $endpointManager->shouldReceive('getServerUrl')->andReturn('https://gateway.tweakwisenavigator.net');

        $this->tester->mockService(Config::class, $config);
        $this->tester->mockService(RemoteAddress::class, $remoteAddress);
        $this->tester->mockService(EndpointManager::class, $endpointManager);

        /** @var Client $client */
        $client = $this->tester->getObjectManager()->get(Client::class);

        $request = $this->createMock(Request::class);
        $request->method('getPath')->willReturn('navigation');
        $request->method('getPathSuffix')->willReturn('');
        $request->method('getParameters')->willReturn(['tn_ps' => '12']);

        $httpRequest = $client->createGetRequest($request);

        $this->assertSame('', $httpRequest->getHeaderLine('TWN-Source'));
    }
}
