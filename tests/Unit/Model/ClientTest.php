<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model;

use Emico\CodeCept\Test\Unit;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\UrlInterface;
use Tweakwise\Magento2Tweakwise\Model\Client;
use Tweakwise\Magento2Tweakwise\Model\Client\EndpointManager;
use Tweakwise\Magento2Tweakwise\Model\Client\Request;
use Tweakwise\Magento2Tweakwise\Model\Client\ResponseFactory;
use Tweakwise\Magento2Tweakwise\Model\Client\Timer;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2TweakwiseExport\Model\Logger;
use Tweakwise\Test\Support\UnitTester;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\AnalyticsRequest;

class ClientTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    /**
     * @return void
     */
    public function testCreateGetRequestAddsInternalTrafficHeaderForMatchingIp(): void
    {
        $config = $this->createMock(Config::class);
        $config->method('getInternalIpAddresses')->willReturn(['127.0.0.1']);
        $config->method('getGeneralAuthenticationKey')->willReturn('123456');
        $config->method('getRecommendationsFeaturedCategory')->willReturn(false);

        $endpointManager = $this->createMock(EndpointManager::class);
        $endpointManager->method('getServerUrl')->willReturn('https://gateway.tweakwisenavigator.net');

        $remoteAddress = $this->createMock(RemoteAddress::class);
        $remoteAddress->method('getRemoteAddress')->willReturn('127.0.0.1');

        $client = $this->createClient($config, $endpointManager, $remoteAddress);

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
        $config = $this->createMock(Config::class);
        $config->method('getInternalIpAddresses')->willReturn(['10.0.0.99']);
        $config->method('getGeneralAuthenticationKey')->willReturn('123456');
        $config->method('getRecommendationsFeaturedCategory')->willReturn(false);

        $endpointManager = $this->createMock(EndpointManager::class);
        $endpointManager->method('getServerUrl')->willReturn('https://gateway.tweakwisenavigator.net');

        $remoteAddress = $this->createMock(RemoteAddress::class);
        $remoteAddress->method('getRemoteAddress')->willReturn('127.0.0.1');

        $client = $this->createClient($config, $endpointManager, $remoteAddress);

        $request = $this->createMock(Request::class);
        $request->method('getPath')->willReturn('navigation');
        $request->method('getPathSuffix')->willReturn('');
        $request->method('getParameters')->willReturn(['tn_ps' => '12']);

        $httpRequest = $client->createGetRequest($request);

        $this->assertSame('', $httpRequest->getHeaderLine('TWN-Source'));
    }

    /**
     * @return void
     */
    public function testCreatePostRequestAddsInternalTrafficHeaderForAnalyticsRequests(): void
    {
        $config = $this->createMock(Config::class);
        $config->method('getInternalIpAddresses')->willReturn(['127.0.0.1']);
        $config->method('getGeneralAuthenticationKey')->willReturn('123456');

        $urlBuilder = $this->createMock(UrlInterface::class);
        $urlBuilder->expects($this->once())
            ->method('getUrl')
            ->with('https://navigator-analytics.tweakwise.com/analytics')
            ->willReturn('https://navigator-analytics.tweakwise.com/analytics');

        $remoteAddress = $this->createMock(RemoteAddress::class);
        $remoteAddress->method('getRemoteAddress')->willReturn('127.0.0.1');

        $client = new Client(
            $config,
            $this->createMock(Logger::class),
            $this->createMock(ResponseFactory::class),
            $this->createMock(EndpointManager::class),
            $this->createMock(Timer::class),
            $urlBuilder,
            $remoteAddress
        );

        $request = $this->createMock(AnalyticsRequest::class);
        $request->method('getPath')->willReturn('analytics');
        $request->method('getApiUrl')->willReturn('https://navigator-analytics.tweakwise.com');
        $request->method('getParameters')->willReturn(['event' => 'click']);

        $httpRequest = $client->createPostRequest($request);

        $this->assertSame('Internal-Traffic', $httpRequest->getHeaderLine('TWN-Source'));
        $this->assertSame('123456', $httpRequest->getHeaderLine('Instance-Key'));
    }

    /**
     * @param Config $config
     * @param EndpointManager $endpointManager
     * @param RemoteAddress $remoteAddress
     * @return Client
     */
    private function createClient(
        Config $config,
        EndpointManager $endpointManager,
        RemoteAddress $remoteAddress
    ): Client {
        return new Client(
            $config,
            $this->createMock(Logger::class),
            $this->createMock(ResponseFactory::class),
            $endpointManager,
            $this->createMock(Timer::class),
            $this->createMock(UrlInterface::class),
            $remoteAddress
        );
    }
}
