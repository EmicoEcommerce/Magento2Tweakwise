<?php

namespace Tweakwise\Magento2Tweakwise\Model\Autocomplete\DataProvider;

use Tweakwise\Magento2Tweakwise\Model\Client\Type\SuggestionTypeGroup;
use Magento\Search\Model\Autocomplete\ItemInterface;

class SuggestionGroupItem implements ItemInterface
{
    /**
     * @var SuggestionTypeGroup
     */
    protected $group;

    /**
     * SuggestionGroupItem constructor.
     * @param SuggestionTypeGroup $group
     */
    public function __construct(SuggestionTypeGroup $group)
    {
        $this->group = $group;
    }

    /**
     * @return string|null
     */
    public function getTitle()
    {
        return $this->group->getName() ?: '';
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $result = [
            'type' => 'suggestion_group',
            'title' => $this->group->getName(),
            'subtype' => $this->group->getType(),
        ];
        foreach ($this->group->getSuggestions() as $suggestion) {
            $suggestionUrl = $suggestion->getUrl();
            if (!$suggestionUrl) {
                continue;
            }

            $suggestionResult = [
                'title' => $suggestion->getName(),
                'url' => $suggestionUrl
            ];
            $result['suggestions'][] = $suggestionResult;
        }

        return $result;
    }
}
