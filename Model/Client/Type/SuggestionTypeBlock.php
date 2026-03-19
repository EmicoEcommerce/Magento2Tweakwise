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
     * @var SuggestionTypeFactory
     */
    protected $suggestionTypeFactory;

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
        SuggestionTypeFactory $suggestionTypeFactory,
        array $data = []
    ) {
        $this->suggestionTypeFactory = $suggestionTypeFactory;
        parent::__construct($data);
    }

    /**
     * @param string $type
     * @return void
     */
    public function setType(string $type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param array $block
     * @return $this
     */
    public function setBlock(array $block)
    {
        $this->data['blocks'] = $block;
        return $this;
    }

    /**
     * @return array
     */
    public function getBlock()
    {
        return $this->data['blocks'] ?? [];
    }
}
