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
     * @var array<int, array{type: string, role: string, content: string}>
     */
    private readonly array $messages;

    /**
     * @var array
     */
    private readonly array $navigation;

    /**
     * @var array
     */
    private readonly array $history;

    /**
     * @var array<int, array{attribute: string, value: mixed}>
     */
    private readonly array $selectedFacets;

    /**
     * @var string|null
     */
    private readonly ?string $searchTerm;

    /**
     * @param mixed $data Decoded JSON body
     * @throws InvalidArgumentException When the body does not match the documented schema
     */
    public function __construct($data)
    {
        if (!is_array($data)) {
            throw new InvalidArgumentException('Assistant response is not an object');
        }

        foreach (['messages', 'navigation', 'history', 'selectedFacets'] as $key) {
            if (!isset($data[$key]) || !is_array($data[$key])) {
                throw new InvalidArgumentException(sprintf('Assistant response has no valid "%s"', $key));
            }
        }

        $this->messages = $this->parseMessages($data['messages']);
        $this->navigation = is_array($data['navigation']['grouped'] ?? null)
            // The grouped endpoint wraps the navigation in "grouped", like the demoshop tolerate both
            ? $data['navigation']['grouped']
            : $data['navigation'];
        $this->history = $data['history'];
        $this->selectedFacets = $this->parseSelectedFacets($data['selectedFacets']);
        $this->searchTerm = is_string($data['searchTerm'] ?? null) ? $data['searchTerm'] : null;
    }

    /**
     * @param array $messages
     * @return array<int, array{type: string, role: string, content: string}>
     * @throws InvalidArgumentException
     */
    private function parseMessages(array $messages): array
    {
        $result = [];
        foreach ($messages as $message) {
            if (
                !is_array($message)
                || !in_array($message['role'] ?? null, ['user', 'assistant'], true)
                || !is_string($message['content'] ?? null)
            ) {
                throw new InvalidArgumentException('Assistant response contains an invalid message');
            }

            $result[] = ['type' => 'message', 'role' => $message['role'], 'content' => $message['content']];
        }

        return $result;
    }

    /**
     * Selected facets without an attribute or value are dropped.
     *
     * @param array $selectedFacets
     * @return array<int, array{attribute: string, value: mixed}>
     */
    private function parseSelectedFacets(array $selectedFacets): array
    {
        $result = [];
        foreach ($selectedFacets as $facet) {
            if (!is_array($facet) || !is_string($facet['attribute'] ?? null) || !array_key_exists('value', $facet)) {
                continue;
            }

            $result[] = ['attribute' => $facet['attribute'], 'value' => $facet['value']];
        }

        return $result;
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
