<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Test\Unit\Model;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Response as HttpResponse;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\UrlInterface;
use Magento\Variable\Model\ResourceModel\Variable as VariableResource;
use Magento\Variable\Model\Variable;
use Magento\Variable\Model\VariableFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use stdClass;
use Tweakwise\Magento2Tweakwise\Exception\ApiException;
use Tweakwise\Magento2Tweakwise\Model\Client\EndpointManager;
use Tweakwise\Magento2Tweakwise\Model\Client\Request;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\AnalyticsRequest;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\PurchaseRequest;
use Tweakwise\Magento2Tweakwise\Model\Client\Response;
use Tweakwise\Magento2Tweakwise\Model\Client\ResponseFactory;
use Tweakwise\Magento2Tweakwise\Model\Client\Timer;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2TweakwiseExport\Model\Logger;

class ClientRetryTest extends TestCase
{
    private const NOW = 1_800_000_000;

    public function testExpiredGetTimerRetriesFallbackOnlyOnce(): void
    {
        foreach ([0, 1, 100, 299, 300] as $secondsExpired) {
            foreach ([false, true] as $async) {
                $manager = $this->createEndpointManager(self::NOW - $secondsExpired, true);
                $requests = (object) ['items' => []];
                $client = $this->createClient($manager, $requests);

                $this->assertConnectionFailure($client, $this->createRequest(), $async);

                $this->assertSame([
                    EndpointManager::SERVER_URL . '/navigation/test-key',
                    EndpointManager::FALLBACK_SERVER_URL . '/navigation/test-key',
                ], array_map('strval', array_map(static function (RequestInterface $request) {
                    return $request->getUri();
                }, $requests->items)));
            }
        }
    }

    public function testActiveGetTimerSkipsPrimaryAndDoesNotRetryFallback(): void
    {
        foreach ([false, true] as $async) {
            $manager = $this->createEndpointManager(self::NOW + 100, false);
            $requests = (object) ['items' => []];
            $client = $this->createClient($manager, $requests);

            $this->assertConnectionFailure($client, $this->createRequest(), $async);

            $this->assertCount(1, $requests->items);
            $this->assertSame('gateway.tweakwisenavigator.com', $requests->items[0]->getUri()->getHost());
        }
    }

    public function testGetWithNoTimerRetriesFallbackAndCanSucceed(): void
    {
        foreach ([false, true] as $async) {
            $manager = $this->createEndpointManager(0, true);
            $requests = (object) ['items' => []];
            $client = $this->createClient($manager, $requests, true);
            $response = $client->runRequest($this->createRequest(), $async);
            if ($async) {
                $response = $response->wait();
            }

            $this->assertInstanceOf(Response::class, $response);
            $this->assertCount(2, $requests->items);
            $this->assertSame('gateway.tweakwisenavigator.com', $requests->items[1]->getUri()->getHost());
        }
    }

    public function testPostConnectionFailureIsNotRetriedOrMarkedAsGatewayDowntime(): void
    {
        foreach ([AnalyticsRequest::class, PurchaseRequest::class] as $requestClass) {
            foreach ([false, true] as $async) {
                $manager = $this->createMock(EndpointManager::class);
                $manager->expects($this->never())->method('getServerUrl');
                $manager->expects($this->never())->method('handleConnectException');
                $requests = (object) ['items' => []];
                $client = $this->createClient($manager, $requests);

                $this->assertConnectionFailure($client, $this->createRequest($requestClass), $async);

                $this->assertCount(1, $requests->items);
                $this->assertSame('POST', $requests->items[0]->getMethod());
                $this->assertSame(
                    'https://navigator-analytics.tweakwise.com/api/purchase',
                    (string) $requests->items[0]->getUri()
                );
                $this->assertSame('test-key', $requests->items[0]->getHeaderLine('Instance-Key'));
                $this->assertSame('{"ProfileKey":"profile"}', (string) $requests->items[0]->getBody());
            }
        }
    }

    public function testPostConnectionFailureWithRealEndpointManagerDoesNotLoop(): void
    {
        foreach ([AnalyticsRequest::class, PurchaseRequest::class] as $requestClass) {
            foreach ([false, true] as $async) {
                $manager = $this->createEndpointManager(0, null);
                $requests = (object) ['items' => []];
                $client = $this->createClient($manager, $requests);

                $this->assertConnectionFailure($client, $this->createRequest($requestClass), $async);

                $this->assertCount(1, $requests->items);
                $this->assertFalse($manager->isFallback());
            }
        }
    }

    public function testNonConnectionFailureIsNotRetried(): void
    {
        $manager = $this->createEndpointManager(0, false);
        $requests = (object) ['items' => []];
        $client = $this->createClient($manager, $requests, false, false);

        try {
            $client->runRequest($this->createRequest());
            $this->fail('Expected API failure.');
        } catch (ApiException $exception) {
            $this->assertInstanceOf(RequestException::class, $exception->getPrevious());
        }

        $this->assertCount(1, $requests->items);
    }

    public function testConnectionFailureAlwaysMarksFallbackWithoutExtendingAnActiveTimer(): void
    {
        foreach ([self::NOW + 100, self::NOW + 600] as $downUntil) {
            $manager = $this->createEndpointManager($downUntil, false);
            $manager->handleConnectException();

            $this->assertTrue($manager->isFallback());
            $this->assertSame(EndpointManager::FALLBACK_SERVER_URL, $manager->getServerUrl());
        }
    }

    private function createEndpointManager(int $downUntil, ?bool $save): EndpointManager
    {
        $state = (object) ['downUntil' => $downUntil];
        $variable = $this->createMock(Variable::class);
        $variable->method('__call')->with('getCode', [])->willReturn(EndpointManager::VARIABLE_NAME);
        $variable->method('getValue')->with(Variable::TYPE_TEXT)->willReturnCallback(
            static function () use ($state): string {
                return (string) $state->downUntil;
            }
        );
        $variable->expects($save === null ? $this->any() : ($save ? $this->once() : $this->never()))
            ->method('setData')
            ->with('plain_value', self::NOW + EndpointManager::DOWN_PERIOD)
            ->willReturnCallback(static function (...$arguments) use ($state, $variable) {
                $state->downUntil = $arguments[1];
                return $variable;
            });
        $factory = $this->createStub(VariableFactory::class);
        $factory->method('create')->willReturn($variable);
        $resource = $this->createMock(VariableResource::class);
        $resource->expects($save === null ? $this->any() : $this->once())->method('loadByCode')
            ->with($variable, EndpointManager::VARIABLE_NAME);
        $resource->expects($save === null ? $this->any() : ($save ? $this->once() : $this->never()))
            ->method('save')->with($variable);
        $clock = $this->createStub(DateTime::class);
        $clock->method('gmtTimestamp')->willReturn(self::NOW);

        return new EndpointManager($factory, $resource, $clock);
    }

    private function createRequest(string $class = Request::class): Request
    {
        $request = $this->createStub($class);
        $isPost = $class !== Request::class;
        $request->method('isPostRequest')->willReturn($isPost);
        $request->method('getPath')->willReturn($isPost ? 'purchase' : 'navigation');
        $request->method('getPathSuffix')->willReturn('');
        $request->method('getParameters')->willReturn($isPost ? ['ProfileKey' => 'profile'] : []);
        if ($isPost) {
            $request->method('getApiurl')->willReturn('https://navigator-analytics.tweakwise.com/api');
        }

        return $request;
    }

    private function createClient(
        EndpointManager $manager,
        stdClass $requests,
        bool $fallbackSucceeds = false,
        bool $connectionFailure = true
    ): RetryTestClient {
        $config = $this->createStub(Config::class);
        $config->method('getGeneralAuthenticationKey')->willReturn('test-key');
        $urlBuilder = $this->createStub(UrlInterface::class);
        $urlBuilder->method('getUrl')->willReturnArgument(0);
        $factory = $this->createStub(ResponseFactory::class);
        $factory->method('create')->willReturn($this->createStub(Response::class));
        $client = new RetryTestClient(
            $config,
            $this->createStub(Logger::class),
            $factory,
            $manager,
            $this->createStub(Timer::class),
            $urlBuilder
        );
        $handler = static function (RequestInterface $request) use (
            $requests,
            $fallbackSucceeds,
            $connectionFailure
        ) {
            $requests->items[] = $request;
            if (count($requests->items) > 2) {
                return Create::rejectionFor(new RequestException(
                    'Retry exceeded two attempts; bounded regression stopped the loop.',
                    $request
                ));
            }
            if ($fallbackSucceeds && count($requests->items) === 2) {
                return Create::promiseFor(new HttpResponse(204));
            }
            $exception = $connectionFailure
                ? new ConnectException('Simulated connection failure', $request)
                : new RequestException('Simulated HTTP failure', $request);
            return Create::rejectionFor($exception);
        };
        $client->setHttpClient(new HttpClient(['handler' => HandlerStack::create($handler)]));

        return $client;
    }

    private function assertConnectionFailure(RetryTestClient $client, Request $request, bool $async): void
    {
        try {
            $response = $client->runRequest($request, $async);
            if ($async) {
                $response->wait();
            }
            $this->fail('Expected API connection failure.');
        } catch (ApiException $exception) {
            $this->assertInstanceOf(ConnectException::class, $exception->getPrevious());
        }
    }
}
