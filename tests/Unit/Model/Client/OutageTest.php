<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model\Client;

use Emico\CodeCept\Test\Unit;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request as HttpRequest;
use GuzzleHttp\Psr7\Response as HttpResponse;
use GuzzleHttp\Promise\Utils;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Mockery;
use ReflectionProperty;
use stdClass;
use Tweakwise\Magento2Tweakwise\Exception\ApiException;
use Tweakwise\Magento2Tweakwise\Model\Client;
use Tweakwise\Magento2Tweakwise\Model\Client\EndpointManager;
use Tweakwise\Magento2Tweakwise\Model\Client\Request;
use Tweakwise\Magento2Tweakwise\Model\Client\Response;
use Tweakwise\Magento2Tweakwise\Model\Client\ResponseFactory;
use Tweakwise\Magento2Tweakwise\Model\Client\Timer;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2TweakwiseExport\Model\Logger;
use ReflectionMethod;

class OutageTest extends Unit
{
    /**
     * @dataProvider requestModes
     */
    public function testPrimaryAndFallbackFailureMakeAtMostTwoAttempts(bool $async): void
    {
        $history = [];
        $handler = HandlerStack::create(new MockHandler([
            new ConnectException('Primary unavailable', new HttpRequest('GET', EndpointManager::SERVER_URL)),
            new ConnectException('Fallback unavailable', new HttpRequest('GET', EndpointManager::FALLBACK_SERVER_URL)),
        ]));
        $handler->push(Middleware::history($history));

        $config = Mockery::mock(Config::class)->makePartial();
        $config->shouldReceive('getInternalIpAddresses')->andReturn([]);
        $config->shouldReceive('getGeneralAuthenticationKey')->andReturn('testkey');

        $state = new stdClass();
        $state->fallback = false;
        $manager = Mockery::mock(EndpointManager::class);
        $manager->shouldReceive('getServerUrl')->andReturnUsing(
            static function () use ($state): string {
                return $state->fallback ? EndpointManager::FALLBACK_SERVER_URL : EndpointManager::SERVER_URL;
            }
        );
        $manager->shouldReceive('handleConnectException')->with(false)->once()->andReturnUsing(
            static function () use ($state): void {
                $state->fallback = true;
            }
        );
        $manager->shouldReceive('handleConnectException')->with(true)->once();
        $logger = Mockery::mock(Logger::class);
        $logger->shouldReceive('throwException')->andReturnUsing(
            static function (ApiException $e): void {
                throw $e;
            }
        );

        $client = $this->createClient($config, $manager, $logger);
        (new ReflectionProperty($client, 'client'))->setValue($client, new HttpClient(['handler' => $handler]));
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('getPath')->andReturn('navigation');
        $request->shouldReceive('getPathSuffix')->andReturn('');
        $request->shouldReceive('getParameters')->andReturn([]);

        try {
            $response = $client->request($request, $async);
            if ($async) {
                $response->wait();
            }
            $this->fail('An unreachable fallback must reject the request');
        } catch (ApiException $e) {
            $this->assertStringContainsString('Fallback unavailable', $e->getMessage());
        }

        $this->assertCount(2, $history);
        $this->assertSame('gateway.tweakwisenavigator.net', $history[0]['request']->getUri()->getHost());
        $this->assertSame('gateway.tweakwisenavigator.com', $history[1]['request']->getUri()->getHost());
    }

    public static function requestModes(): array
    {
        return ['synchronous' => [false], 'asynchronous' => [true]];
    }

    public function testConcurrentPrimaryFailuresEachRetryTheFallbackOnce(): void
    {
        $history = [];
        $handler = HandlerStack::create(new MockHandler([
            new ConnectException('Primary unavailable', new HttpRequest('GET', EndpointManager::SERVER_URL)),
            new ConnectException('Primary unavailable', new HttpRequest('GET', EndpointManager::SERVER_URL)),
            new HttpResponse(204),
            new HttpResponse(204),
        ]));
        $handler->push(Middleware::history($history));
        $config = Mockery::mock(Config::class)->makePartial();
        $config->shouldReceive('getInternalIpAddresses')->andReturn([]);
        $config->shouldReceive('getGeneralAuthenticationKey')->andReturn('testkey');
        $state = new stdClass();
        $state->fallback = false;
        $manager = Mockery::mock(EndpointManager::class);
        $manager->shouldReceive('getServerUrl')->andReturnUsing(
            static function () use ($state): string {
                return $state->fallback ? EndpointManager::FALLBACK_SERVER_URL : EndpointManager::SERVER_URL;
            }
        );
        $manager->shouldReceive('handleConnectException')->with(false)->twice()->andReturnUsing(
            static function () use ($state): void {
                $state->fallback = true;
            }
        );
        $manager->shouldNotReceive('handleConnectException')->with(true);
        $client = $this->createClient($config, $manager);
        (new ReflectionProperty($client, 'client'))->setValue($client, new HttpClient(['handler' => $handler]));
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('getPath')->andReturn('navigation');
        $request->shouldReceive('getPathSuffix')->andReturn('');
        $request->shouldReceive('getParameters')->andReturn([]);
        $results = Utils::unwrap([
            $client->request($request, true),
            $client->request($request, true),
        ]);

        $this->assertCount(2, $results);
        $this->assertCount(4, $history);
        $this->assertSame([
            'gateway.tweakwisenavigator.net',
            'gateway.tweakwisenavigator.net',
            'gateway.tweakwisenavigator.com',
            'gateway.tweakwisenavigator.com',
        ], array_map(static fn (array $entry): string => $entry['request']->getUri()->getHost(), $history));
    }

    public function testClientUsesConfiguredRequestAndConnectTimeouts(): void
    {
        $config = Mockery::mock(Config::class);
        $config->shouldReceive('getUserAgentString')->andReturn('test');
        $config->shouldReceive('getTimeout')->andReturn(2);
        $config->shouldReceive('getConnectTimeout')->andReturn(1);
        $client = $this->createClient($config);
        $method = new ReflectionMethod($client, 'getClient');
        $httpClient = $method->invoke($client);

        $this->assertSame(2, $httpClient->getConfig('timeout'));
        $this->assertSame(1, $httpClient->getConfig('connect_timeout'));
    }

    public function testFailureFlagPreventsAnyFurtherHttpRequests(): void
    {
        $config = Mockery::mock(Config::class);
        $config->shouldReceive('getTweakwiseExceptionTrown')->andReturn(true);
        $config->shouldReceive('getInternalIpAddresses')->andReturn([]);
        $config->shouldReceive('getGeneralAuthenticationKey')->andReturn('testkey');
        $client = $this->createClient($config);
        $httpClient = $this->createMock(HttpClient::class);
        $httpClient->expects($this->never())->method('sendAsync');
        (new ReflectionProperty($client, 'client'))->setValue($client, $httpClient);
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('getPath')->andReturn('navigation');
        $request->shouldReceive('getPathSuffix')->andReturn('');
        $request->shouldReceive('getParameters')->andReturn([]);

        $this->expectException(ApiException::class);
        $client->request($request);
    }

    private function createClient(
        Config $config,
        ?EndpointManager $endpointManager = null,
        ?Logger $logger = null
    ): Client {
        $manager = Mockery::mock(EndpointManager::class);
        $manager->shouldReceive('getServerUrl')->andReturn(EndpointManager::SERVER_URL);
        $timer = Mockery::mock(Timer::class);
        $timer->shouldReceive('startTimer');
        $timer->shouldReceive('endTimer');
        $timer->shouldReceive('getTime')->andReturn(0.01);
        $factory = Mockery::mock(ResponseFactory::class);
        $factory->shouldReceive('create')->andReturn(Mockery::mock(Response::class));
        $defaultLogger = Mockery::mock(Logger::class);
        $defaultLogger->shouldReceive('debug');

        return new Client(
            $config,
            $logger ?? $defaultLogger,
            $factory,
            $endpointManager ?? $manager,
            $timer,
            Mockery::mock(RemoteAddress::class)
        );
    }
}
