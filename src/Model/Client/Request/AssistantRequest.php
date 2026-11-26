<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Model\Client\Request;

use Tweakwise\Magento2Tweakwise\Model\Client\Request;

/**
 * JSON body for POST /ai/assistant/{instanceKey} (or /ai/assistant/grouped/{instanceKey}).
 */
class AssistantRequest extends Request
{
    /**
     * @var string
     */
    protected $path = 'ai/assistant';

    /**
     * @var string
     */
    protected $groupedPath = 'ai/assistant/grouped';

    /**
     * @var array<int, array{type: string, role: string, content: string}>
     */
    private array $messages = [];

    private ?string $categoryId = null;

    private ?string $searchTerm = null;

    /**
     * @var array<int, array{attribute: string, value: mixed}>
     */
    private array $selectedFacets = [];

    private array $history = [];

    private ?string $profileKey = null;

    /**
     * @param array<int, array{role: string, content: string}> $messages
     * @return $this
     */
    public function setMessages(array $messages): self
    {
        $this->messages = [];
        foreach ($messages as $message) {
            $this->messages[] = [
                'type' => 'message',
                'role' => (string)$message['role'],
                'content' => (string)$message['content'],
            ];
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * Sets the Magento category, mapped to the Tweakwise category id of the current store.
     *
     * @param int $categoryId
     * @return $this
     */
    public function setMagentoCategoryId(int $categoryId): self
    {
        $this->categoryId = (string)$this->helper->getTweakwiseId((int)$this->getStoreId(), $categoryId);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCategoryId(): ?string
    {
        return $this->categoryId;
    }

    /**
     * @param string|null $searchTerm
     * @return $this
     */
    public function setSearchTerm(?string $searchTerm): self
    {
        $searchTerm = $searchTerm !== null ? trim($searchTerm) : null;
        $this->searchTerm = $searchTerm !== '' ? $searchTerm : null;

        return $this;
    }

    /**
     * @param array<int, array{attribute: string, value: mixed}> $selectedFacets
     * @return $this
     */
    public function setSelectedFacets(array $selectedFacets): self
    {
        $this->selectedFacets = array_values($selectedFacets);

        return $this;
    }

    /**
     * History is opaque server state and is passed through unchanged.
     *
     * @param array $history
     * @return $this
     */
    public function setHistory(array $history): self
    {
        $this->history = $history;

        return $this;
    }

    /**
     * @param string $profileKey
     * @return $this
     */
    public function setProfileKey(string $profileKey)
    {
        $profileKey = trim($profileKey);
        $this->profileKey = $profileKey !== '' ? $profileKey : null;

        return $this;
    }

    /**
     * @return array
     */
    public function getBody(): array
    {
        $body = [
            'categoryId' => $this->categoryId,
            'messages' => $this->messages,
            'selectedFacets' => $this->selectedFacets,
            'searchTerm' => $this->searchTerm,
            'history' => $this->history,
        ];

        if ($this->profileKey !== null) {
            $body['profileKey'] = $this->profileKey;
        }

        return $body;
    }
}
