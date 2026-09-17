<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model\Client;

use Emico\CodeCept\Test\Unit;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\RejectedPromise;
use Mockery;
use Mockery\MockInterface;
use stdClass;
use Tweakwise\Magento2Tweakwise\Exception\ApiException;
use Tweakwise\Magento2Tweakwise\Model\Client;
use Tweakwise\Magento2Tweakwise\Model\Client\Request;
use Tweakwise\Magento2Tweakwise\Model\Client\RequestPool;
use Tweakwise\Magento2Tweakwise\Model\Client\Response;
use Tweakwise\Test\Support\UnitTester;

class RequestPoolTest extends Unit
{
    protected UnitTester $tester;

    private Client&MockInterface $client;
    private RequestPool $pool;

    /**
     * @var string[]
     */
    private array $log = [];

    public function _before(): void
    {
        $this->client = Mockery::mock(Client::class);
        $this->pool = new RequestPool($this->client);
        $this->log = [];
    }

    public function _after(): void
    {
        Mockery::close();
    }

    public function testAllQueuedRequestsAreSentTogetherWhenTheFirstResponseIsResolved(): void
    {
        $upsell = $this->createRequest('recommendations/product', '/10/123');
        $related = $this->createRequest('recommendations/product', '/20/123');
        $upsellResponse = Mockery::mock(Response::class);
        $relatedResponse = Mockery::mock(Response::class);

        $this->client->shouldReceive('request')->with($upsell, true)->once()
            ->andReturn($this->createPromise('upsell', $upsellResponse));
        $this->client->shouldReceive('request')->with($related, true)->once()
            ->andReturn($this->createPromise('related', $relatedResponse));

        $this->pool->add($upsell);
        $this->pool->add($related);
        $this->assertSame([], $this->log, 'Queueing must not send anything yet');

        $this->assertSame($upsellResponse, $this->pool->resolve($upsell));
        $this->assertSame(['wait:upsell', 'wait:related'], $this->log, 'Resolving one request settles the whole batch');

        $this->assertSame($relatedResponse, $this->pool->resolve($related));
        $this->assertSame(['wait:upsell', 'wait:related'], $this->log, 'An already settled request is not sent again');
    }

    public function testIdenticalRequestsShareOneHttpCall(): void
    {
        $first = $this->createRequest('recommendations/product', '/10/123', ['tn_lang' => 'nl']);
        $second = $this->createRequest('recommendations/product', '/10/123', ['tn_lang' => 'nl']);
        $response = Mockery::mock(Response::class);

        $this->client->shouldReceive('request')->with($first, true)->once()
            ->andReturn($this->createPromise('first', $response));
        $this->client->shouldNotReceive('request')->with($second, true);

        $this->assertSame($response, $this->pool->resolve($first));
        $this->assertTrue($this->pool->has($second));
        $this->assertSame($response, $this->pool->resolve($second));
    }

    public function testRequestsWithDifferentParametersAreNotDeduplicated(): void
    {
        $first = $this->createRequest('recommendations/product', '/10/123', ['tn_lang' => 'nl']);
        $second = $this->createRequest('recommendations/product', '/10/123', ['tn_lang' => 'de']);

        $this->assertNotSame($this->pool->getKey($first), $this->pool->getKey($second));
    }

    public function testFailedRequestIsReportedThroughTheClientErrorPolicyWhenResolved(): void
    {
        $failing = $this->createRequest('recommendations/product', '/10/123');
        $healthy = $this->createRequest('recommendations/product', '/20/123');
        $healthyResponse = Mockery::mock(Response::class);
        $exception = new ApiException('Tweakwise is down', 500);

        $this->client->shouldReceive('request')->with($failing, true)->once()
            ->andReturn(new RejectedPromise($exception));
        $this->client->shouldReceive('request')->with($healthy, true)->once()
            ->andReturn($this->createPromise('healthy', $healthyResponse));
        $this->client->shouldReceive('handleApiException')->with($exception)->once()->andThrow($exception);

        $this->pool->add($failing);
        $this->pool->add($healthy);

        // The healthy request is unaffected by the failing one in the same batch.
        $this->assertSame($healthyResponse, $this->pool->resolve($healthy));

        $this->expectExceptionObject($exception);
        $this->pool->resolve($failing);
    }

    public function testInvalidRequestIsReportedThroughTheClientErrorPolicyWhenQueued(): void
    {
        $exception = new ApiException('Featured products without template ID was requested.');
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('getPath')->andReturn('recommendations/product');
        $request->shouldReceive('getPathSuffix')->andThrow($exception);

        $this->client->shouldNotReceive('request');
        $this->client->shouldReceive('handleApiException')->with($exception)->once()->andThrow($exception);

        $this->expectExceptionObject($exception);
        $this->pool->add($request);
    }

    private function createRequest(string $path, string $suffix, array $parameters = []): Request&MockInterface
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('getPath')->andReturn($path);
        $request->shouldReceive('getPathSuffix')->andReturn($suffix);
        $request->shouldReceive('getParameters')->andReturn($parameters);

        return $request;
    }

    /**
     * A pending promise that, like Guzzle's curl multi handler, only produces its value once it is waited for.
     */
    private function createPromise(string $name, Response $response): Promise
    {
        $holder = new stdClass();
        $holder->promise = new Promise(function () use ($holder, $name, $response) {
            $this->log[] = 'wait:' . $name;
            $holder->promise->resolve($response);
        });

        return $holder->promise;
    }
}
