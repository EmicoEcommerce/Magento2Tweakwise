<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2Tweakwise\Model;

use InvalidArgumentException;
use JsonException;
use Tweakwise\Magento2Tweakwise\Exception\ApiException;
use Tweakwise\Magento2Tweakwise\Exception\AssistantException;
use Tweakwise\Magento2Tweakwise\Model\Client\EndpointManager;
use Tweakwise\Magento2Tweakwise\Model\Client\Request;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\AssistantRequest;
use Tweakwise\Magento2Tweakwise\Model\Client\Response\AssistantResponse;
use Tweakwise\Magento2Tweakwise\Model\Client\Response;
use Tweakwise\Magento2Tweakwise\Model\Client\ResponseFactory;
use Tweakwise\Magento2Tweakwise\Model\Client\Timer;
use Tweakwise\Magento2TweakwiseExport\Model\Logger;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Request as HttpRequest;
use GuzzleHttp\RequestOptions;
use Magento\Framework\Profiler;
use Psr\Http\Message\ResponseInterface;
use SimpleXMLElement;
use GuzzleHttp\Client as HttpClient;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use GuzzleHttp\Psr7\Uri;

class Client
{
    /**
     * @deprecated use Config::REQUEST_TIMEOUT
     */
    public const REQUEST_TIMEOUT = Config::REQUEST_TIMEOUT;

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
        Timer $timer,
        /**
         * @var RemoteAddress
         */
        private readonly RemoteAddress $remoteAddress
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
        // @phpstan-ignore-next-line
        if (!$this->client) {
            $options = [
                RequestOptions::TIMEOUT => $this->config->getTimeout(),
                RequestOptions::CONNECT_TIMEOUT => $this->config->getConnectTimeout(),
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
     * @return array
     */
    protected function buildInternalTrafficHeader(): array
    {
        $internalIps = $this->config->getInternalIpAddresses();
        if (empty($internalIps)) {
            return [];
        }

        $visitorIp = $this->remoteAddress->getRemoteAddress();
        if (!in_array($visitorIp, $internalIps, true)) {
            return [];
        }

        return ['TWN-Source' => 'Internal-Traffic'];
    }

    /**
     * @param Request $tweakwiseRequest
     * @return HttpRequest
     */
    public function createGetRequest(Request $tweakwiseRequest): HttpRequest
    {
        $path = $tweakwiseRequest->getPath();
        $pathSuffix = $tweakwiseRequest->getPathSuffix();

        $headers = $this->buildInternalTrafficHeader();

        if ($path === 'recommendations/featured') {
            if ($this->config->getRecommendationsFeaturedCategory()) {
                // @phpstan-ignore-next-line
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
            $url = sprintf('%s?%s', $url, $query);
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
        $httpRequest = $this->createGetRequest($tweakwiseRequest);
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
                function (GuzzleException $e) use ($tweakwiseRequest, $httpRequest) {
                    // Timeout uses Guzzle ConnectException, ConnectException is more general but it also makes sense
                    // to use this if the default server is unreachable for some reason
                    if ($e instanceof ConnectException) {
                        $isFallback = $httpRequest->getUri()->getHost() ===
                            parse_url(EndpointManager::FALLBACK_SERVER_URL, PHP_URL_HOST);
                        $this->endpointManager->handleConnectException($isFallback);
                        if (!$isFallback) {
                            return $this->doRequest($tweakwiseRequest, true);
                        }
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
                        implode(PHP_EOL, $errors) // @phpstan-ignore-line
                    )
                );
            }
        } finally {
            libxml_use_internal_errors($xmlPreviousErrors);
        }

        $result = $this->xmlToArray($xmlElement);
        $result['headers'] = $httpResponse->getHeaders();
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
     * @return Response|PromiseInterface|void
     * @throws \Exception
     */
    public function request(Request $request, bool $async = false)
    {
        if ($this->config->getTweakwiseExceptionThrown()) {
            throw new ApiException('Tweakwise API is not reachable');
        }

        Profiler::start('tweakwise::request::' . $request->getPath());
        try {
            return $this->doRequest($request, $async);
        } catch (ApiException $e) {
            $this->handleApiException($e);
        } finally {
            Profiler::stop('tweakwise::request::' . $request->getPath());
        }
    }

    /**
     * Error policy for a failed TW request: 404 responses are passed through untouched, any other failure disables
     * TW for the remainder of the page request and is logged. Shared with RequestPool for asynchronously sent
     * requests, whose failures only surface when the response is resolved.
     *
     * @param ApiException $e
     * @return void
     * @throws ApiException
     */
    public function handleApiException(ApiException $e): void
    {
        //don't log 404 messages.
        if ($e->getCode() === 404) {
            throw $e;
        }

        $this->config->setTweakwiseExceptionThrown(true);
        $this->log->throwException($e);
    }

    /**
     * Sends a JSON POST request to the AI Shopping Assistant endpoint.
     *
     * Failures throw an AssistantException and never set the global Tweakwise exception flag, so regular navigation
     * keeps working. Only the status code, duration and an error reference are logged, never the conversation.
     *
     * @param AssistantRequest $request
     * @return AssistantResponse
     * @throws AssistantException
     */
    public function requestAssistant(AssistantRequest $request): AssistantResponse
    {
        $reference = bin2hex(random_bytes(6));
        $timerName = $request->getPath();
        $this->timer->startTimer($timerName);
        $statusCode = 0;

        try {
            $httpResponse = $this->sendAssistantRequest($request, $reference);
            $statusCode = $httpResponse->getStatusCode();
            $response = $this->parseAssistantResponse($httpResponse, $reference);
        } catch (AssistantException $e) {
            $this->timer->endTimer($timerName);
            $this->log->error(
                sprintf(
                    '[AI assistant] Request failed. Status: %d, duration: %.3fs, reference: %s',
                    $e->getCode(),
                    $this->timer->getTime($timerName),
                    $reference
                )
            );
            throw $e;
        }

        $this->timer->endTimer($timerName);
        $this->log->debug(
            sprintf(
                '[AI assistant] Status: %d, duration: %.3fs',
                $statusCode,
                $this->timer->getTime($timerName)
            )
        );

        return $response;
    }

    /**
     * @param AssistantRequest $request
     * @return HttpRequest
     * @throws JsonException
     */
    public function createAssistantPostRequest(AssistantRequest $request): HttpRequest
    {
        $url = sprintf(
            '%s/%s/%s',
            rtrim($this->endpointManager->getServerUrl(), '/'),
            trim((string)$request->getPath(), '/'),
            rawurlencode($this->config->getGeneralAuthenticationKey())
        );

        $headers = array_merge(
            [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            $this->buildInternalTrafficHeader()
        );

        $body = json_encode($request->getBody(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        return new HttpRequest('POST', new Uri($url), $headers, $body);
    }

    /**
     * Sends the request, failing over to the fallback gateway when the primary gateway cannot be reached. A slow
     * assistant response (operation timeout) does not mark the gateway as down.
     *
     * @param AssistantRequest $request
     * @param string $reference
     * @return ResponseInterface
     * @throws AssistantException
     */
    protected function sendAssistantRequest(AssistantRequest $request, string $reference): ResponseInterface
    {
        try {
            $httpRequest = $this->createAssistantPostRequest($request);
        } catch (JsonException $e) {
            throw new AssistantException('Tweakwise AI assistant request could not be created', 0, $reference, null, $e);
        }

        try {
            return $this->getClient()->send(
                $httpRequest,
                [
                    RequestOptions::TIMEOUT => Config::ASSISTANT_REQUEST_TIMEOUT,
                    RequestOptions::HTTP_ERRORS => false,
                ]
            );
        } catch (ConnectException $e) {
            if (stripos($e->getMessage(), 'Operation timed out') !== false) {
                throw new AssistantException('Tweakwise AI assistant timed out', 0, $reference, null, $e);
            }

            $isFallback = $httpRequest->getUri()->getHost() ===
                parse_url(EndpointManager::FALLBACK_SERVER_URL, PHP_URL_HOST);
            $this->endpointManager->handleConnectException($isFallback);
            if (!$isFallback) {
                return $this->sendAssistantRequest($request, $reference);
            }

            throw new AssistantException('Tweakwise AI assistant is not reachable', 0, $reference, null, $e);
        } catch (GuzzleException $e) {
            throw new AssistantException('Tweakwise AI assistant request failed', 0, $reference, null, $e);
        }
    }

    /**
     * @param ResponseInterface $httpResponse
     * @param string $reference
     * @return AssistantResponse
     * @throws AssistantException
     */
    protected function parseAssistantResponse(ResponseInterface $httpResponse, string $reference): AssistantResponse
    {
        $statusCode = $httpResponse->getStatusCode();
        $body = (string)$httpResponse->getBody();

        if ($statusCode !== 200) {
            throw new AssistantException(
                sprintf('Tweakwise AI assistant responded with status %d', $statusCode),
                $statusCode,
                $reference,
                $this->getAssistantErrorReason($statusCode, $body)
            );
        }

        try {
            return AssistantResponse::fromArray(json_decode($body, true, 512, JSON_THROW_ON_ERROR));
        } catch (JsonException | InvalidArgumentException $e) {
            throw new AssistantException('Invalid Tweakwise AI assistant response', $statusCode, $reference, null, $e);
        }
    }

    /**
     * @param int $statusCode
     * @param string $body
     * @return string|null
     */
    protected function getAssistantErrorReason(int $statusCode, string $body): ?string
    {
        if (stripos($body, 'quota') === false) {
            return null;
        }

        if ($statusCode === 403) {
            return AssistantException::REASON_QUOTA_REACHED;
        }

        if ($statusCode === 503) {
            return AssistantException::REASON_QUOTA_UNAVAILABLE;
        }

        return null;
    }
}
