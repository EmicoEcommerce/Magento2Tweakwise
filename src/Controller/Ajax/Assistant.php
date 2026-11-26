<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Controller\Ajax;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Tweakwise\Magento2Tweakwise\Exception\AssistantException;
use Tweakwise\Magento2Tweakwise\Model\Assistant\PageContext;
use Tweakwise\Magento2Tweakwise\Model\Assistant\PageContextProvider;
use Tweakwise\Magento2Tweakwise\Model\Assistant\ResultMapper;
use Tweakwise\Magento2Tweakwise\Model\Client;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\AssistantRequest;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\AssistantRequestFactory;
use Tweakwise\Magento2Tweakwise\Model\Config;

/**
 * Proxy for the Tweakwise AI Shopping Assistant. Accepts a form-key protected POST with a JSON "payload" field.
 */
class Assistant implements HttpPostActionInterface, CsrfAwareActionInterface
{
    public const MAX_MESSAGES = 40;
    public const MAX_MESSAGE_LENGTH = 1000;
    public const MAX_SELECTED_FACETS = 50;
    public const MAX_PAYLOAD_LENGTH = 262144;

    /**
     * @param RequestInterface $request
     * @param JsonFactory $jsonFactory
     * @param FormKeyValidator $formKeyValidator
     * @param Config $config
     * @param Client $client
     * @param AssistantRequestFactory $assistantRequestFactory
     * @param PageContextProvider $pageContextProvider
     * @param ResultMapper $resultMapper
     * @param CookieManagerInterface $cookieManager
     */
    public function __construct(
        private readonly RequestInterface $request,
        private readonly JsonFactory $jsonFactory,
        private readonly FormKeyValidator $formKeyValidator,
        private readonly Config $config,
        private readonly Client $client,
        private readonly AssistantRequestFactory $assistantRequestFactory,
        private readonly PageContextProvider $pageContextProvider,
        private readonly ResultMapper $resultMapper,
        private readonly CookieManagerInterface $cookieManager
    ) {
    }

    /**
     * @return Json
     */
    public function execute(): Json
    {
        if (!$this->config->isAssistantEnabled()) {
            return $this->createResult(404, ['error' => 'disabled']);
        }

        if (!$this->formKeyValidator->validate($this->request)) {
            return $this->createResult(403, ['error' => 'invalid_form_key']);
        }

        $payload = $this->getPayload();
        $context = $payload !== null
            ? $this->pageContextProvider->fromSignedData(
                $payload['context'] ?? null,
                is_string($payload['searchTerm'] ?? null) ? $payload['searchTerm'] : null
            )
            : null;
        $messages = $payload !== null ? $this->validateMessages($payload['messages'] ?? null) : null;
        $selectedFacets = $payload !== null ? $this->validateSelectedFacets($payload['selectedFacets'] ?? []) : null;
        $history = $payload['history'] ?? [];

        if ($context === null || $messages === null || $selectedFacets === null || !is_array($history)) {
            return $this->createResult(400, ['error' => 'invalid_request']);
        }

        $assistantRequest = $this->createAssistantRequest($context, $messages, $selectedFacets, $history);

        try {
            $response = $this->client->requestAssistant($assistantRequest);
        } catch (AssistantException $e) {
            return $this->createResult(
                502,
                ['error' => 'assistant_error', 'reason' => $e->getReason(), 'reference' => $e->getReference()]
            );
        }

        $toolbar = is_array($payload['toolbar'] ?? null) ? $payload['toolbar'] : [];
        $result = $this->resultMapper->map($response, $context, $toolbar);

        return $this->createResult(
            200,
            [
                'messages' => $response->getMessages(),
                'history' => $response->getHistory(),
                'selectedFacets' => $response->getSelectedFacets(),
                'searchTerm' => $response->getSearchTerm(),
                'url' => $result['url'],
                'filters' => (object)$result['filters'],
                'ajax' => $result['ajax'],
            ]
        );
    }

    /**
     * @param RequestInterface $request
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return new InvalidRequestException($this->createResult(403, ['error' => 'invalid_form_key']));
    }

    /**
     * @param RequestInterface $request
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return $this->formKeyValidator->validate($request);
    }

    /**
     * @param PageContext $context
     * @param array $messages
     * @param array $selectedFacets
     * @param array $history
     * @return AssistantRequest
     */
    private function createAssistantRequest(
        PageContext $context,
        array $messages,
        array $selectedFacets,
        array $history
    ): AssistantRequest {
        /** @var AssistantRequest $assistantRequest */
        $assistantRequest = $this->assistantRequestFactory->create();
        $assistantRequest
            ->setMessages($messages)
            ->setMagentoCategoryId($context->getCategoryId())
            ->setSearchTerm($context->getSearchTerm())
            ->setSelectedFacets($selectedFacets)
            ->setHistory($history);

        if ($this->config->isPersonalMerchandisingActive()) {
            $profileKey = $this->cookieManager->getCookie($this->config->getPersonalMerchandisingCookieName());
            if (is_string($profileKey)) {
                $assistantRequest->setProfileKey($profileKey);
            }
        }

        return $assistantRequest;
    }

    /**
     * @return array|null
     */
    private function getPayload(): ?array
    {
        $payload = $this->request->getParam('payload');
        if (!is_string($payload) || $payload === '' || strlen($payload) > self::MAX_PAYLOAD_LENGTH) {
            return null;
        }

        $data = json_decode($payload, true);

        return is_array($data) ? $data : null;
    }

    /**
     * Returns the messages when valid: at most MAX_MESSAGES user/assistant messages, ending with a non-empty user
     * message.
     *
     * @param mixed $messages
     * @return array|null
     */
    private function validateMessages($messages): ?array
    {
        if (!is_array($messages) || !$messages || count($messages) > self::MAX_MESSAGES || !array_is_list($messages)) {
            return null;
        }

        $result = [];
        foreach ($messages as $message) {
            $role = $message['role'] ?? null;
            $content = $message['content'] ?? null;
            if (
                !in_array($role, ['user', 'assistant'], true)
                || !is_string($content)
                || trim($content) === ''
                || mb_strlen($content) > self::MAX_MESSAGE_LENGTH
            ) {
                return null;
            }

            $result[] = ['role' => $role, 'content' => trim($content)];
        }

        $last = end($result);

        return $last['role'] === 'user' ? $result : null;
    }

    /**
     * @param mixed $selectedFacets
     * @return array|null
     */
    private function validateSelectedFacets($selectedFacets): ?array
    {
        if (!is_array($selectedFacets) || count($selectedFacets) > self::MAX_SELECTED_FACETS) {
            return null;
        }

        $result = [];
        foreach ($selectedFacets as $facet) {
            $attribute = $facet['attribute'] ?? null;
            $value = $facet['value'] ?? null;
            if (!is_string($attribute) || $attribute === '' || !(is_scalar($value) || $value === null)) {
                return null;
            }

            $result[] = ['attribute' => $attribute, 'value' => $value];
        }

        return $result;
    }

    /**
     * @param int $statusCode
     * @param array $data
     * @return Json
     */
    private function createResult(int $statusCode, array $data): Json
    {
        $result = $this->jsonFactory->create();
        $result->setHttpResponseCode($statusCode);
        $result->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0', true);
        $result->setData($data);

        return $result;
    }
}
