<?php

/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2Tweakwise\Model;

use Tweakwise\Magento2Tweakwise\Exception\ApiException;
use Tweakwise\Magento2Tweakwise\Model\Client\EndpointManager;
use Tweakwise\Magento2Tweakwise\Model\Client\Request;
use Tweakwise\Magento2Tweakwise\Model\Client\Response;
use Tweakwise\Magento2Tweakwise\Model\Client\ResponseFactory;
use Tweakwise\Magento2Tweakwise\Model\Client\Timer;
use Tweakwise\Magento2TweakwiseExport\Model\Logger;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Request as HttpRequest;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\RequestOptions;
use Magento\Framework\Profiler;
use Psr\Http\Message\ResponseInterface;
use SimpleXMLElement;
use GuzzleHttp\Client as HttpClient;

class Client
{
    /**
     * Defaults
     */
    public const REQUEST_TIMEOUT = 5;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Logger
     */
    protected $log;

    /**
     * @var HttpClient
     */
    protected $client;

    /**
     * @var ResponseFactory
     */
    protected $responseFactory;

    /**
     * @var EndpointManager
     */
    protected $endpointManager;

    /**
     * @var Timer
     */
    protected $timer;

    /**
     * Client constructor.
     *
     * @param Config $config
     * @param Logger $log
     * @param ResponseFactory $responseFactory
     * @param EndpointManager $endpointManager
     */
    public function __construct(
        Config $config,
        Logger $log,
        ResponseFactory $responseFactory,
        EndpointManager $endpointManager,
        Timer $timer
    ) {
        $this->config = $config;
        $this->log = $log;
        $this->timer = $timer;
        $this->responseFactory = $responseFactory;
        $this->endpointManager = $endpointManager;
    }

    /**
     * @return HttpClient
     */
    protected function getClient(): HttpClient
    {
        if (!$this->client) {
            $options = [
                RequestOptions::TIMEOUT => self::REQUEST_TIMEOUT,
                RequestOptions::HEADERS => [
                    'user-agent' => $this->config->getUserAgentString(),
                    'Accept-Encoding' => 'gzip, deflate'
                ]
            ];
            $this->client = new HttpClient($options);
        }

        return $this->client;
    }

    /**
     * @param Request $tweakwiseRequest
     * @return HttpRequest
     */
    protected function createHttpRequest(Request $tweakwiseRequest): HttpRequest
    {
        if ($tweakwiseRequest->isPostRequest()) {
            return $this->createPostRequest($tweakwiseRequest);
        } else {
            return $this->createGetRequest($tweakwiseRequest);
        }
    }

    /**
     * @param Request $tweakwiseRequest
     * @return HttpRequest
     */
    protected function createPostRequest(Request $tweakwiseRequest): HttpRequest
    {
        $path = $tweakwiseRequest->getPath();
        $headers = [];

        $headers['Content-Type'] = 'application/json';
        $headers['Instance-Key'] = $this->config->getGeneralAuthenticationKey();
        $body = json_encode($tweakwiseRequest->getParameters());
        //post request are used for the analytics api
        $uri = new Uri($tweakwiseRequest->getApiUrl() . '/' . $path);
        return new HttpRequest('POST', $uri, $headers, $body);
    }

    /**
     * @param Request $tweakwiseRequest
     * @return HttpRequest
     */
    protected function createGetRequest(Request $tweakwiseRequest): HttpRequest
    {
        $path = $tweakwiseRequest->getPath();
        $pathSuffix = $tweakwiseRequest->getPathSuffix();

        $headers = [];

        if ($path === "recommendations/featured") {
            if ($this->config->getRecommendationsFeaturedCategory()) {
                $tweakwiseRequest->setCategory();
            }
        }

        $url = sprintf(
            '%s/%s/%s%s',
            rtrim($this->endpointManager->getServerUrl(), '/'),
            trim($path, '/'),
            $this->config->getGeneralAuthenticationKey(),
            $pathSuffix
        );

        if ($tweakwiseRequest->getParameters()) {
            $query = http_build_query($tweakwiseRequest->getParameters());
            $url   = sprintf('%s?%s', $url, $query);
        }

        $uri = new Uri($url);
        return new HttpRequest('GET', $uri, $headers);
    }

    /**
     * Method performs request and normalize response from TW. Parsers XML result and throws API exception on TW errors.
     *
     * @param Request $tweakwiseRequest
     * @param bool $async
     * @return Response|PromiseInterface
     */
    protected function doRequest(Request $tweakwiseRequest, bool $async = false)
    {
        $client = $this->getClient();
        $httpRequest = $this->createHttpRequest($tweakwiseRequest);
        $this->timer->startTimer($tweakwiseRequest->getPath());

        $responsePromise = $client
            ->sendAsync($httpRequest)
            ->then(
                function (ResponseInterface $response) use ($tweakwiseRequest, $httpRequest) {
                    return $this->handleRequestSuccess(
                        $response,
                        $httpRequest,
                        $tweakwiseRequest,
                    );
                },
                function (GuzzleException $e) use ($tweakwiseRequest, $async) {
                    // Timeout uses Guzzle ConnectException, ConnectException is more general but it also makes sense
                    // to use this if the default server is unreachable for some reason
                    if ($e instanceof ConnectException && !$this->endpointManager->isFallback()) {
                        $this->endpointManager->handleConnectException();
                        return $this->doRequest($tweakwiseRequest, $async);
                    }

                    throw new ApiException($e->getMessage(), $e->getCode(), $e);
                }
            );

        if ($async) {
            return $responsePromise;
        }

        return $responsePromise->wait(true);
    }

    /**
     * @param ResponseInterface $httpResponse
     * @param HttpRequest $httpRequest
     * @param Request $tweakwiseRequest
     * @return Response
     * @throws ApiException
     */
    public function handleRequestSuccess(
        ResponseInterface $httpResponse,
        HttpRequest $httpRequest,
        Request $tweakwiseRequest,
    ): Response {
        $this->timer->endTimer($tweakwiseRequest->getPath());
        $requestUrl = (string)$httpRequest->getUri();
        $statusCode = $httpResponse->getStatusCode();

        $this->log->debug(
            sprintf(
                '[Request][%.5f] %s',
                $this->timer->getTime($tweakwiseRequest->getPath()),
                $requestUrl
            )
        );

        if ($statusCode === 204) {
            return $this->responseFactory->create($tweakwiseRequest, []);
        }

        if ($statusCode !== 200) {
            throw new ApiException(
                sprintf(
                    'Invalid response received by Tweakwise server, response code is not 200. Request "%s"',
                    $requestUrl
                ),
                $statusCode
            );
        }

        $xmlPreviousErrors = libxml_use_internal_errors(true);
        try {
            $xmlElement = simplexml_load_string($httpResponse->getBody(), SimpleXMLElement::class, LIBXML_NOCDATA);
            if ($xmlElement === false) {
                $errors = libxml_get_errors();
                throw new ApiException(
                    sprintf(
                        'Invalid response received by Tweakwise server, xml load fails. Request "%s", XML Errors: %s',
                        $requestUrl,
                        implode(PHP_EOL, $errors)
                    )
                );
            }
        } finally {
            libxml_use_internal_errors($xmlPreviousErrors);
        }

        $result = $this->xmlToArray($xmlElement);
        return $this->responseFactory->create($tweakwiseRequest, $result);
    }

    /**
     * @param SimpleXMLElement $element
     * @return array
     */
    protected function xmlToArray(SimpleXMLElement $element): array
    {
        $result = [];
        foreach ($element->attributes() as $attribute => $value) {
            $result['@' . $attribute] = (string)$value;
        }

        /** @var SimpleXMLElement $node */
        foreach ((array)$element as $index => $node) {
            if ($index === '@attributes') {
                continue;
            }

            $result[$index] = $this->xmlToArrayValue($node);
        }

        return $result;
    }

    /**
     * @param mixed $value
     * @return array|string
     */
    protected function xmlToArrayValue($value)
    {
        if ($value instanceof SimpleXMLElement) {
            return $this->xmlToArray($value);
        }

        if (is_array($value)) {
            $values = [];
            foreach ($value as $element) {
                $values[] = $this->xmlToArrayValue($element);
            }

            return $values;
        }

        return (string)$value;
    }

    /**
     * Public request method to TW api. Used to disable TW on exceptions.
     *
     * @param Request $request
     * @param bool $async
     * @return Response|PromiseInterface
     * @throws \Exception
     */
    public function request(Request $request, bool $async = false)
    {
        Profiler::start('tweakwise::request::' . $request->getPath());
        try {
            return $this->doRequest($request, $async);
        } catch (ApiException $e) {
            $this->config->setTweakwiseExceptionThrown(true);
            //don't log 404 messages.
            if ($e->getCode() !== 404) {
                $this->log->throwException($e);
            } else {
                throw ($e);
            }
        } finally {
            Profiler::stop('tweakwise::request::' . $request->getPath());
        }
    }
}
