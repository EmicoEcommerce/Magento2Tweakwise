<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Model\Client;

use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\Utils;
use Magento\Framework\Profiler;
use Throwable;
use Tweakwise\Magento2Tweakwise\Exception\ApiException;
use Tweakwise\Magento2Tweakwise\Model\Client;

/**
 * Collects Tweakwise requests and sends all queued requests concurrently as soon as the first response is needed.
 *
 * Every request that is queued before the first call to resolve() is executed in the same Guzzle multi-curl batch,
 * so a page that needs multiple Tweakwise calls (e.g. upsell and related recommendations on a product page) waits
 * for the slowest call instead of the sum of all calls. Requests are deduplicated on their resulting URL, so blocks
 * asking for the same data share one HTTP round trip.
 */
class RequestPool
{
    /**
     * @var array<string, PromiseInterface>
     */
    private array $pending = [];

    /**
     * @var array<string, Response>
     */
    private array $responses = [];

    /**
     * @var array<string, ApiException>
     */
    private array $failures = [];

    /**
     * @param Client $client
     */
    public function __construct(private readonly Client $client)
    {
    }

    /**
     * Identifies a request by the URL it will produce, so identical requests are only sent once.
     *
     * @param Request $request
     * @return string
     * @throws ApiException
     */
    public function getKey(Request $request): string
    {
        $key = trim($request->getPath(), '/') . $request->getPathSuffix();
        $parameters = $request->getParameters();
        if ($parameters) {
            $key .= '?' . http_build_query($parameters);
        }

        return $key;
    }

    /**
     * Queue a request. Nothing is sent until a response is resolved; queueing the same request twice is a no-op.
     *
     * @param Request $request
     * @return void
     * @throws ApiException
     */
    public function add(Request $request): void
    {
        $key = $this->keyFor($request);
        if ($this->isKnown($key)) {
            return;
        }

        $promise = $this->client->request($request, true);
        if (!$promise instanceof PromiseInterface) {
            throw new ApiException(sprintf('Expected a promise for request "%s"', $key));
        }

        $this->pending[$key] = $promise;
    }

    /**
     * @param Request $request
     * @return bool
     * @throws ApiException
     */
    public function has(Request $request): bool
    {
        return $this->isKnown($this->keyFor($request));
    }

    /**
     * Returns the response for the request, sending it together with every other queued request when needed.
     *
     * @param Request $request
     * @return Response
     * @throws ApiException
     */
    public function resolve(Request $request): Response
    {
        $this->add($request);
        $key = $this->keyFor($request);

        if (isset($this->pending[$key])) {
            $this->settle();
        }

        if (isset($this->failures[$key])) {
            $this->client->handleApiException($this->failures[$key]);
        }

        if (!isset($this->responses[$key])) {
            throw new ApiException(sprintf('No response received for request "%s"', $key));
        }

        return $this->responses[$key];
    }

    /**
     * Send every queued request concurrently and wait until all of them have finished.
     *
     * @return void
     */
    public function settle(): void
    {
        if (!$this->pending) {
            return;
        }

        $promises = $this->pending;
        $this->pending = [];

        Profiler::start('tweakwise::request-pool::settle');
        try {
            $results = Utils::settle($promises)->wait();
        } finally {
            Profiler::stop('tweakwise::request-pool::settle');
        }

        foreach ($results as $key => $result) {
            $value = $result['value'] ?? null;
            if ($result['state'] === PromiseInterface::FULFILLED && $value instanceof Response) {
                $this->responses[$key] = $value;
                continue;
            }

            $this->failures[$key] = $this->toApiException($result['reason'] ?? null, (string)$key);
        }
    }

    /**
     * @param string $key
     * @return bool
     */
    private function isKnown(string $key): bool
    {
        return isset($this->pending[$key]) || isset($this->responses[$key]) || isset($this->failures[$key]);
    }

    /**
     * Determine the key, applying the same error policy as a direct request would for invalid requests.
     *
     * @param Request $request
     * @return string
     * @throws ApiException
     */
    private function keyFor(Request $request): string
    {
        try {
            return $this->getKey($request);
        } catch (ApiException $e) {
            $this->client->handleApiException($e);
        }

        throw new ApiException('Unable to determine request key');
    }

    /**
     * @param mixed $reason
     * @param string $key
     * @return ApiException
     */
    private function toApiException(mixed $reason, string $key): ApiException
    {
        if ($reason instanceof ApiException) {
            return $reason;
        }

        if ($reason instanceof Throwable) {
            return new ApiException($reason->getMessage(), (int)$reason->getCode(), $reason);
        }

        return new ApiException(sprintf('Request "%s" failed: %s', $key, is_scalar($reason) ? $reason : 'unknown'));
    }
}
