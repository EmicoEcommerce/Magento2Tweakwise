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
use GuzzleHttp\RequestOptions;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;
use Mockery;
use Mockery\MockInterface;
use ReflectionProperty;
use stdClass;
use Tweakwise\Magento2Tweakwise\Exception\ApiException;
use Tweakwise\Magento2Tweakwise\Exception\AssistantException;
use Tweakwise\Magento2Tweakwise\Model\Client;
use Tweakwise\Magento2Tweakwise\Model\Client\EndpointManager;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\AssistantRequest;
use Tweakwise\Magento2Tweakwise\Model\Client\ResponseFactory;
use Tweakwise\Magento2Tweakwise\Model\Client\Timer;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2TweakwiseExport\Model\Helper;
use Tweakwise\Magento2TweakwiseExport\Model\Logger;

/**
 * All assistant responses are fixtures, the live AI endpoint is never called.
 */
class AssistantClientTest extends Unit
{
    private const SECRET_MESSAGE = 'my secret shoe size is 44';

    private array $history = [];

    /** @var string[] */
    private array $logLines = [];

    public function testSuccessfulRequestIsPostedAsJsonToTheInstanceEndpoint(): void
    {
        $client = $this->createClient([new HttpResponse(200, [], $this->fixture('success.json'))]);

        $response = $client->requestAssistant($this->createRequest());

        $this->assertCount(1, $this->history);
        /** @var HttpRequest $httpRequest */
        $httpRequest = $this->history[0]['request'];
        $this->assertSame('POST', $httpRequest->getMethod());
        $this->assertSame(
            'https://gateway.tweakwisenavigator.net/ai/assistant/instance%2Fkey',
            (string)$httpRequest->getUri()
        );
        $this->assertSame('application/json', $httpRequest->getHeaderLine('Content-Type'));
        $this->assertSame(Config::ASSISTANT_REQUEST_TIMEOUT, $this->history[0]['options'][RequestOptions::TIMEOUT]);

        $body = json_decode((string)$httpRequest->getBody(), true);
        $this->assertSame('1000012', $body['categoryId']);
        $this->assertSame(
            [['type' => 'message', 'role' => 'user', 'content' => self::SECRET_MESSAGE]],
            $body['messages']
        );
        $this->assertSame([['id' => 'previous', 'state' => ['nested' => true]]], $body['history']);
        $this->assertSame([['attribute' => 'kleur', 'value' => 'Rood']], $body['selectedFacets']);
        $this->assertSame('shoes', $body['searchTerm']);
        $this->assertArrayNotHasKey('profileKey', $body);

        $this->assertCount(2, $response->getMessages());
        $this->assertSame([['id' => 'h1', 'state' => 'opaque']], $response->getHistory());
        $this->assertSame('running shoes', $response->getSearchTerm());
        $this->assertCount(3, $response->getSelectedFacets());
    }

    public function testGroupedProductsUseTheGroupedEndpoint(): void
    {
        $client = $this->createClient([new HttpResponse(200, [], $this->fixture('success.json'))]);

        $client->requestAssistant($this->createRequest(true));

        $this->assertSame('/ai/assistant/grouped/instance%2Fkey', $this->history[0]['request']->getUri()->getPath());
    }

    /**
     * @dataProvider errorResponses
     */
    public function testErrorResponsesAreMappedToAssistantExceptions(
        int $status,
        string $fixture,
        ?string $reason
    ): void {
        $client = $this->createClient([new HttpResponse($status, [], $this->fixture($fixture))]);

        try {
            $client->requestAssistant($this->createRequest());
            $this->fail('An error response must throw');
        } catch (AssistantException $e) {
            $this->assertSame($status, $e->getCode());
            $this->assertSame($reason, $e->getReason());
            $this->assertNotSame('', $e->getReference());
            $this->assertStringNotContainsString($this->fixture($fixture), $e->getMessage());
        }

        $this->assertNoMessageContentLogged();
    }

    public static function errorResponses(): array
    {
        return [
            'status 400' => [400, 'error-400.txt', null],
            '403 feature' => [403, 'error-403-feature.txt', null],
            '403 quota' => [403, 'error-403-quota.txt', AssistantException::REASON_QUOTA_REACHED],
            'status 500' => [500, 'error-500.txt', null],
            'status 503' => [503, 'error-503.txt', null],
            '503 quota' => [503, 'error-503-quota.txt', AssistantException::REASON_QUOTA_UNAVAILABLE],
            'invalid json' => [200, 'invalid.json', null],
            'invalid schema' => [200, 'invalid-schema.json', null],
        ];
    }

    public function testUnreachablePrimaryGatewayFailsOverToFallback(): void
    {
        $state = new stdClass();
        $state->fallback = false;
        $manager = Mockery::mock(EndpointManager::class);
        $manager->shouldReceive('getServerUrl')->andReturnUsing(
            static fn (): string => $state->fallback
                ? EndpointManager::FALLBACK_SERVER_URL
                : EndpointManager::SERVER_URL
        );
        $manager->shouldReceive('handleConnectException')->with(false)->once()->andReturnUsing(
            static function () use ($state): void {
                $state->fallback = true;
            }
        );
        $client = $this->createClient(
            [
                new ConnectException('Could not resolve host', new HttpRequest('POST', EndpointManager::SERVER_URL)),
                new HttpResponse(200, [], $this->fixture('success.json')),
            ],
            $manager
        );

        $client->requestAssistant($this->createRequest());

        $this->assertCount(2, $this->history);
        $this->assertSame('gateway.tweakwisenavigator.net', $this->history[0]['request']->getUri()->getHost());
        $this->assertSame('gateway.tweakwisenavigator.com', $this->history[1]['request']->getUri()->getHost());
        $this->assertSame($this->history[0]['request']->getBody()->__toString(), (string)$this->history[1]['request']->getBody());
    }

    public function testUnavailableGatewaysAreReportedAsAssistantFailure(): void
    {
        $state = new stdClass();
        $state->fallback = false;
        $manager = Mockery::mock(EndpointManager::class);
        $manager->shouldReceive('getServerUrl')->andReturnUsing(
            static function () use ($state): string {
                if ($state->fallback) {
                    throw new ApiException('Tweakwise gateways are temporarily unavailable');
                }

                return EndpointManager::SERVER_URL;
            }
        );
        $manager->shouldReceive('handleConnectException')->with(false)->once()->andReturnUsing(
            static function () use ($state): void {
                $state->fallback = true;
            }
        );
        $client = $this->createClient(
            [new ConnectException('Could not resolve host', new HttpRequest('POST', EndpointManager::SERVER_URL))],
            $manager
        );

        try {
            $client->requestAssistant($this->createRequest());
            $this->fail('Expected an AssistantException');
        } catch (AssistantException $e) {
            $this->assertInstanceOf(ApiException::class, $e->getPrevious());
        }

        $this->assertStringContainsString('[AI assistant] Request failed', (string)end($this->logLines));
    }

    public function testSlowAssistantDoesNotMarkGatewayDown(): void
    {
        $manager = Mockery::mock(EndpointManager::class);
        $manager->shouldReceive('getServerUrl')->andReturn(EndpointManager::SERVER_URL);
        $manager->shouldNotReceive('handleConnectException');
        $client = $this->createClient(
            [
                new ConnectException(
                    'cURL error 28: Operation timed out after 30000 milliseconds',
                    new HttpRequest('POST', EndpointManager::SERVER_URL)
                ),
            ],
            $manager
        );

        $this->expectException(AssistantException::class);
        $client->requestAssistant($this->createRequest());
    }

    public function testFailureDoesNotDisableRegularNavigation(): void
    {
        $config = $this->createConfig();
        $config->shouldNotReceive('setTweakwiseExceptionThrown');
        $client = $this->createClient([new HttpResponse(500, [], $this->fixture('error-500.txt'))], null, $config);

        try {
            $client->requestAssistant($this->createRequest());
        } catch (AssistantException $e) {
            // Expected
        }

        $this->assertCount(1, $this->history);
    }

    public function testSuccessLogsNoMessageContent(): void
    {
        $client = $this->createClient([new HttpResponse(200, [], $this->fixture('success.json'))]);

        $client->requestAssistant($this->createRequest());

        $this->assertNotEmpty($this->logLines);
        $this->assertNoMessageContentLogged();
    }

    public function testProfileKeyIsSentWhenSet(): void
    {
        $request = $this->createRequest();
        $request->setProfileKey(' profile-1 ');
        $this->assertSame('profile-1', $request->getBody()['profileKey']);

        $request->setProfileKey('  ');
        $this->assertArrayNotHasKey('profileKey', $request->getBody());
    }

    public function testBlankSearchTermIsSentAsNull(): void
    {
        $request = $this->createRequest();
        $request->setSearchTerm('  ');

        $this->assertNull($request->getBody()['searchTerm']);
    }

    private function assertNoMessageContentLogged(): void
    {
        foreach ($this->logLines as $line) {
            $this->assertStringNotContainsString(self::SECRET_MESSAGE, $line);
            $this->assertStringNotContainsString('previous', $line);
            $this->assertStringNotContainsString('quota', $line);
        }
    }

    private function createRequest(bool $grouped = false): AssistantRequest
    {
        $store = Mockery::mock(Store::class);
        $store->shouldReceive('getId')->andReturn(3);
        $storeManager = Mockery::mock(StoreManager::class);
        $storeManager->shouldReceive('getStore')->andReturn($store);
        $helper = Mockery::mock(Helper::class);
        $helper->shouldReceive('getTweakwiseId')->with(3, 12)->andReturn('1000012');
        $config = Mockery::mock(Config::class);
        $config->shouldReceive('isGroupedProductsEnabled')->andReturn($grouped);

        $request = new AssistantRequest($helper, $storeManager, $config);

        return $request
            ->setMessages([['role' => 'user', 'content' => self::SECRET_MESSAGE]])
            ->setMagentoCategoryId(12)
            ->setSearchTerm('shoes')
            ->setSelectedFacets([['attribute' => 'kleur', 'value' => 'Rood']])
            ->setHistory([['id' => 'previous', 'state' => ['nested' => true]]]);
    }

    private function createConfig(): MockInterface
    {
        $config = Mockery::mock(Config::class);
        $config->shouldReceive('getInternalIpAddresses')->andReturn([]);
        $config->shouldReceive('getGeneralAuthenticationKey')->andReturn('instance/key');

        return $config;
    }

    private function createClient(array $responses, ?EndpointManager $manager = null, ?Config $config = null): Client
    {
        $this->history = [];
        $this->logLines = [];
        $handler = HandlerStack::create(new MockHandler($responses));
        $handler->push(Middleware::history($this->history));

        if ($manager === null) {
            $manager = Mockery::mock(EndpointManager::class);
            $manager->shouldReceive('getServerUrl')->andReturn(EndpointManager::SERVER_URL);
        }

        $timer = Mockery::mock(Timer::class);
        $timer->shouldReceive('startTimer');
        $timer->shouldReceive('endTimer');
        $timer->shouldReceive('getTime')->andReturn(0.01);
        $logger = Mockery::mock(Logger::class);
        $logger->shouldReceive('debug', 'error')->andReturnUsing(function (string $message): void {
            $this->logLines[] = $message;
        });

        $client = new Client(
            $config ?? $this->createConfig(),
            $logger,
            Mockery::mock(ResponseFactory::class),
            $manager,
            $timer,
            Mockery::mock(RemoteAddress::class)
        );
        (new ReflectionProperty($client, 'client'))->setValue($client, new HttpClient(['handler' => $handler]));

        return $client;
    }

    private function fixture(string $name): string
    {
        return (string)file_get_contents(codecept_data_dir('assistant/' . $name));
    }
}
