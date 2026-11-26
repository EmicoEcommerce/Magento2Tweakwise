<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Controller\Ajax;

use Emico\CodeCept\Test\Unit;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Mockery;
use Mockery\MockInterface;
use stdClass;
use Tweakwise\Magento2Tweakwise\Controller\Ajax\Assistant;
use Tweakwise\Magento2Tweakwise\Exception\AssistantException;
use Tweakwise\Magento2Tweakwise\Model\Assistant\PageContext;
use Tweakwise\Magento2Tweakwise\Model\Assistant\PageContextProvider;
use Tweakwise\Magento2Tweakwise\Model\Assistant\ResultMapper;
use Tweakwise\Magento2Tweakwise\Model\Client;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\AssistantRequest;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\AssistantRequestFactory;
use Tweakwise\Magento2Tweakwise\Model\Client\Response\AssistantResponse;
use Tweakwise\Magento2Tweakwise\Model\Config;

class AssistantTest extends Unit
{
    private RequestInterface&MockInterface $request;

    private Config&MockInterface $config;

    private Client&MockInterface $client;

    private FormKeyValidator&MockInterface $formKeyValidator;

    private AssistantRequest&MockInterface $assistantRequest;

    private CookieManagerInterface&MockInterface $cookieManager;

    private stdClass $result;

    // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    public function _before(): void
    {
        $this->request = Mockery::mock(RequestInterface::class);
        $this->config = Mockery::mock(Config::class);
        $this->config->shouldReceive('isAssistantEnabled')->andReturn(true)->byDefault();
        $this->config->shouldReceive('isPersonalMerchandisingActive')->andReturn(false)->byDefault();
        $this->client = Mockery::mock(Client::class);
        $this->formKeyValidator = Mockery::mock(FormKeyValidator::class);
        $this->formKeyValidator->shouldReceive('validate')->andReturn(true)->byDefault();
        $this->assistantRequest = Mockery::mock(AssistantRequest::class);
        $this->assistantRequest->shouldReceive(
            'setMessages',
            'setMagentoCategoryId',
            'setSearchTerm',
            'setSelectedFacets',
            'setHistory',
            'setProfileKey'
        )->andReturnSelf()->byDefault();
        $this->cookieManager = Mockery::mock(CookieManagerInterface::class);
        $this->result = new stdClass();
    }

    // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    public function _after(): void
    {
        Mockery::close();
    }

    public function testDisabledAssistantReturnsNotFound(): void
    {
        $this->config->shouldReceive('isAssistantEnabled')->andReturn(false);
        $this->client->shouldNotReceive('requestAssistant');

        $this->createController()->execute();

        $this->assertSame(404, $this->result->code);
    }

    public function testInvalidFormKeyIsRejected(): void
    {
        $this->formKeyValidator->shouldReceive('validate')->andReturn(false);
        $this->client->shouldNotReceive('requestAssistant');

        $controller = $this->createController();
        $controller->execute();

        $this->assertSame(403, $this->result->code);
        $this->assertFalse($controller->validateForCsrf($this->request));
        $this->assertNotNull($controller->createCsrfValidationException($this->request));
    }

    /**
     * @dataProvider invalidPayloads
     */
    public function testInvalidPayloadIsRejected(?string $payload): void
    {
        $this->request->shouldReceive('getParam')->with('payload')->andReturn($payload);
        $this->client->shouldNotReceive('requestAssistant');

        $this->createController()->execute();

        $this->assertSame(400, $this->result->code);
        $this->assertSame(['error' => 'invalid_request'], $this->result->data);
    }

    public static function invalidPayloads(): array
    {
        $valid = self::validPayload();

        return [
            'missing' => [null],
            'not json' => ['{'],
            'too large' => [str_repeat(' ', Assistant::MAX_PAYLOAD_LENGTH + 1)],
            'no messages' => [json_encode(['messages' => []] + $valid)],
            'last message from assistant' => [json_encode(
                ['messages' => [['role' => 'user', 'content' => 'a'], ['role' => 'assistant', 'content' => 'b']]]
                + $valid
            )],
            'empty message' => [json_encode(['messages' => [['role' => 'user', 'content' => '  ']]] + $valid)],
            'unknown role' => [json_encode(['messages' => [['role' => 'system', 'content' => 'a']]] + $valid)],
            'message too long' => [json_encode(
                ['messages' => [['role' => 'user', 'content' => str_repeat('a', Assistant::MAX_MESSAGE_LENGTH + 1)]]]
                + $valid
            )],
            'invalid facet' => [json_encode(['selectedFacets' => [['value' => 'x']]] + $valid)],
            'invalid history' => [json_encode(['history' => 'x'] + $valid)],
            'invalid context' => [json_encode(['context' => ['hash' => 'tampered']] + $valid)],
        ];
    }

    public function testSuccessfulRequestReturnsConversationAndResultUrl(): void
    {
        $this->request->shouldReceive('getParam')->with('payload')->andReturn(json_encode(self::validPayload()));
        $this->assistantRequest->shouldReceive('setMessages')
            ->once()
            ->with([['role' => 'user', 'content' => 'red shoes']])
            ->andReturnSelf();
        $this->assistantRequest->shouldReceive('setMagentoCategoryId')->once()->with(12)->andReturnSelf();
        $this->assistantRequest->shouldReceive('setHistory')->once()->with([['id' => 'h0']])->andReturnSelf();
        $this->assistantRequest->shouldReceive('setSelectedFacets')
            ->once()
            ->with([['attribute' => 'kleur', 'value' => 'Rood']])
            ->andReturnSelf();
        $this->assistantRequest->shouldNotReceive('setProfileKey');

        $response = new AssistantResponse(
            [
                'messages' => [['role' => 'assistant', 'content' => 'Here you go']],
                'navigation' => [],
                'history' => [['id' => 'h1']],
                'selectedFacets' => [['attribute' => 'kleur', 'value' => 'Rood']],
            ]
        );
        $this->client->shouldReceive('requestAssistant')->once()->with($this->assistantRequest)->andReturn($response);

        $this->createController([
            'url' => 'https://shop.test/women.html?q=purple&kleur[]=Rood',
            'filters' => ['q' => 'purple', 'kleur' => ['Rood']],
            'ajax' => true,
        ])->execute();

        $this->assertSame(200, $this->result->code);
        $this->assertSame('no-store, no-cache, must-revalidate, max-age=0', $this->result->headers['Cache-Control']);
        $this->assertSame($response->getMessages(), $this->result->data['messages']);
        $this->assertSame([['id' => 'h1']], $this->result->data['history']);
        $this->assertSame('https://shop.test/women.html?q=purple&kleur[]=Rood', $this->result->data['url']);
        $this->assertEquals((object)['q' => 'purple', 'kleur' => ['Rood']], $this->result->data['filters']);
        // The applied search term, which may come from the returned navigation
        $this->assertSame('purple', $this->result->data['searchTerm']);
        $this->assertTrue($this->result->data['ajax']);
    }

    public function testLongAssistantMessagesAreAccepted(): void
    {
        $messages = [
            ['role' => 'user', 'content' => 'jackets'],
            ['role' => 'assistant', 'content' => str_repeat('a', Assistant::MAX_MESSAGE_LENGTH + 1)],
            ['role' => 'user', 'content' => 'purple'],
        ];
        $this->request->shouldReceive('getParam')
            ->with('payload')
            ->andReturn(json_encode(['messages' => $messages] + self::validPayload()));
        $this->assistantRequest->shouldReceive('setMessages')->once()->with($messages)->andReturnSelf();
        $this->client->shouldReceive('requestAssistant')->once()->andReturn(new AssistantResponse(['messages' => [], 'navigation' => [], 'history' => [], 'selectedFacets' => []]));

        $this->createController()->execute();

        $this->assertSame(200, $this->result->code);
    }

    public function testOnlyTheLatestMessagesAreSent(): void
    {
        $messages = [];
        for ($i = 0; $i <= Assistant::MAX_MESSAGES; $i++) {
            $messages[] = ['role' => $i % 2 ? 'assistant' : 'user', 'content' => 'message ' . $i];
        }

        $this->request->shouldReceive('getParam')
            ->with('payload')
            ->andReturn(json_encode(['messages' => $messages] + self::validPayload()));
        $this->assistantRequest->shouldReceive('setMessages')
            ->once()
            ->with(array_slice($messages, -Assistant::MAX_MESSAGES))
            ->andReturnSelf();
        $this->client->shouldReceive('requestAssistant')->once()->andReturn(new AssistantResponse(['messages' => [], 'navigation' => [], 'history' => [], 'selectedFacets' => []]));

        $this->createController()->execute();

        $this->assertSame(200, $this->result->code);
    }

    public function testProfileKeyIsSentWhenPersonalMerchandisingIsActive(): void
    {
        $this->request->shouldReceive('getParam')->with('payload')->andReturn(json_encode(self::validPayload()));
        $this->config->shouldReceive('isPersonalMerchandisingActive')->andReturn(true);
        $this->config->shouldReceive('getPersonalMerchandisingCookieName')->andReturn('tw_profile');
        $this->cookieManager->shouldReceive('getCookie')->with('tw_profile')->andReturn('profile-1');
        $this->assistantRequest->shouldReceive('setProfileKey')->once()->with('profile-1')->andReturnSelf();
        $this->client->shouldReceive('requestAssistant')->andThrow(
            new AssistantException('failed', 500, 'ref')
        );

        $this->createController()->execute();

        $this->assertSame(502, $this->result->code);
    }

    public function testAssistantErrorReturnsReasonWithoutResponseBody(): void
    {
        $this->request->shouldReceive('getParam')->with('payload')->andReturn(json_encode(self::validPayload()));
        $this->client->shouldReceive('requestAssistant')->andThrow(
            new AssistantException('failed', 403, 'abc123', AssistantException::REASON_QUOTA_REACHED)
        );

        $this->createController()->execute();

        $this->assertSame(502, $this->result->code);
        $this->assertSame(
            ['error' => 'assistant_error', 'reason' => 'quota-reached', 'reference' => 'abc123'],
            $this->result->data
        );
    }

    private static function validPayload(): array
    {
        return [
            'messages' => [['role' => 'user', 'content' => ' red shoes ']],
            'history' => [['id' => 'h0']],
            'selectedFacets' => [['attribute' => 'kleur', 'value' => 'Rood']],
            'searchTerm' => null,
            'context' => ['type' => 'category', 'categoryId' => 12, 'baseUrl' => 'https://shop.test/women.html', 'hash' => 'valid'],
            'toolbar' => ['product_list_order' => 'price'],
        ];
    }

    private function createController(?array $mapped = null): Assistant
    {
        $jsonFactory = Mockery::mock(JsonFactory::class);
        $jsonFactory->shouldReceive('create')->andReturnUsing(function () {
            $json = Mockery::mock(Json::class);
            $json->shouldReceive('setHttpResponseCode')->andReturnUsing(function (int $code) use ($json) {
                $this->result->code = $code;

                return $json;
            });
            $json->shouldReceive('setHeader')->andReturnUsing(function (string $name, string $value) use ($json) {
                $this->result->headers[$name] = $value;

                return $json;
            });
            $json->shouldReceive('setData')->andReturnUsing(function (array $data) use ($json) {
                $this->result->data = $data;

                return $json;
            });

            return $json;
        });

        $pageContextProvider = Mockery::mock(PageContextProvider::class);
        $pageContextProvider->shouldReceive('fromSignedData')->andReturnUsing(
            static fn ($data, ?string $searchTerm) => ($data['hash'] ?? null) === 'valid'
                ? new PageContext($data['type'], (int)$data['categoryId'], $data['baseUrl'], $searchTerm)
                : null
        );

        $resultMapper = Mockery::mock(ResultMapper::class);
        $resultMapper->shouldReceive('map')
            ->with(Mockery::type(AssistantResponse::class), Mockery::type(PageContext::class), ['product_list_order' => 'price'])
            ->andReturn($mapped ?? ['url' => '', 'filters' => [], 'ajax' => false]);

        $factory = Mockery::mock(AssistantRequestFactory::class);
        $factory->shouldReceive('create')->andReturn($this->assistantRequest);

        return new Assistant(
            $this->request,
            $jsonFactory,
            $this->formKeyValidator,
            $this->config,
            $this->client,
            $factory,
            $pageContextProvider,
            $resultMapper,
            $this->cookieManager
        );
    }
}
