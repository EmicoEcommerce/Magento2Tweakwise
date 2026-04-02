<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Model\Client\Type;

use Tweakwise\Magento2Tweakwise\Model\Client\Type\SuggestionType\SuggestionTypeFactory;

/**
 * Class SuggestionTypeBlock
 * Represents a block of suggestions.
 */
class SuggestionTypeBlock extends Type
{
    /**
     * @var string
     */
    protected $type;

    /**
     * SuggestionTypeBlock constructor.
     * @param SuggestionTypeFactory $suggestionTypeFactory
     * @param array $data
     */
    public function __construct(
        protected readonly SuggestionTypeFactory $suggestionTypeFactory,
        array $data = []
    ) {
        parent::__construct($data);
    }

    /**
     * @param string $type
     * @return void
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param array $block
     * @return $this
     */
    public function setBlock(array $block): self
    {
        $this->data['blocks'] = $block;
        return $this;
    }

    /**
     * @return array
     */
    public function getBlock(): array
    {
        return $this->data['blocks'] ?? [];
    }
}
