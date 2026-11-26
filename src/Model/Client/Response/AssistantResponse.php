<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Model\Client\Response;

use InvalidArgumentException;

/**
 * Successful response of the AI Shopping Assistant endpoint.
 */
class AssistantResponse
{
    /**
     * @param array<int, array{type: string, role: string, content: string}> $messages
     * @param array $navigation
     * @param array $history
     * @param array<int, array{attribute: string, value: mixed}> $selectedFacets
     * @param string|null $searchTerm
     */
    public function __construct(
        private readonly array $messages,
        private readonly array $navigation,
        private readonly array $history,
        private readonly array $selectedFacets,
        private readonly ?string $searchTerm
    ) {
    }

    /**
     * @param mixed $data Decoded JSON body
     * @return self
     * @throws InvalidArgumentException When the body does not match the documented schema
     */
    public static function fromArray($data): self
    {
        if (!is_array($data)) {
            throw new InvalidArgumentException('Assistant response is not an object');
        }

        foreach (['messages', 'navigation', 'history', 'selectedFacets'] as $key) {
            if (!isset($data[$key]) || !is_array($data[$key])) {
                throw new InvalidArgumentException(sprintf('Assistant response has no valid "%s"', $key));
            }
        }

        $messages = [];
        foreach ($data['messages'] as $message) {
            if (
                !is_array($message)
                || !in_array($message['role'] ?? null, ['user', 'assistant'], true)
                || !is_string($message['content'] ?? null)
            ) {
                throw new InvalidArgumentException('Assistant response contains an invalid message');
            }

            $messages[] = ['type' => 'message', 'role' => $message['role'], 'content' => $message['content']];
        }

        $selectedFacets = [];
        foreach ($data['selectedFacets'] as $facet) {
            if (!is_array($facet) || !is_string($facet['attribute'] ?? null) || !array_key_exists('value', $facet)) {
                continue;
            }

            $selectedFacets[] = ['attribute' => $facet['attribute'], 'value' => $facet['value']];
        }

        $searchTerm = $data['searchTerm'] ?? null;

        return new self(
            $messages,
            $data['navigation'],
            $data['history'],
            $selectedFacets,
            is_string($searchTerm) ? $searchTerm : null
        );
    }

    /**
     * @return array<int, array{type: string, role: string, content: string}>
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * @return array
     */
    public function getNavigation(): array
    {
        return $this->navigation;
    }

    /**
     * @return array
     */
    public function getHistory(): array
    {
        return $this->history;
    }

    /**
     * @return array<int, array{attribute: string, value: mixed}>
     */
    public function getSelectedFacets(): array
    {
        return $this->selectedFacets;
    }

    /**
     * @return string|null
     */
    public function getSearchTerm(): ?string
    {
        return $this->searchTerm;
    }
}
