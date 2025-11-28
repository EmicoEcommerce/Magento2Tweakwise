<?php

namespace Tweakwise\Magento2Tweakwise\Model\Client\Type;

use Tweakwise\Magento2Tweakwise\Model\Client\Type\SuggestionType\SuggestionTypeAbstract;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\SuggestionType\SuggestionTypeFactory;

/**
 * Class SuggestionGroupType
 *
 * @method string|null getName()
 * @method setName(string $name);
 * @method SuggestionTypeAbstract[] getSuggestions();
 */
class SuggestionTypeGroup extends Type
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
     * SuggestionTypeGroup constructor.
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
     * @param SuggestionTypeAbstract[]|array[] $suggestions
     * @return $this
     */
    public function setSuggestions(array $suggestions)
    {
        $suggestions = $this->normalizeArray($suggestions, 'suggestion');

        $values = [];
        foreach ($suggestions as $suggestion) {
            if (!$suggestion instanceof SuggestionTypeAbstract) {
                $suggestion = $this->suggestionTypeFactory->createSuggestion($suggestion, $this->type);
            }

            $values[] = $suggestion;
        }

        $this->data['suggestions'] = $values;
        return $this;
    }
}
