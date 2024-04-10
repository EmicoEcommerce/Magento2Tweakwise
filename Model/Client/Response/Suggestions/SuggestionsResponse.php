<?php

namespace Tweakwise\Magento2Tweakwise\Model\Client\Response\Suggestions;

use Tweakwise\Magento2Tweakwise\Model\Client\Request;
use Tweakwise\Magento2Tweakwise\Model\Client\Response;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\SuggestionTypeGroup;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\SuggestionTypeGroupFactory;
use Tweakwise\Magento2TweakwiseExport\Model\Helper;

class SuggestionsResponse extends Response
{
    /**
     * @var SuggestionTypeGroupFactory
     */
    protected $suggestionTypeGroupFactory;

    /**
     * SuggestionsResponse constructor.
     * @param SuggestionTypeGroupFactory $suggestionTypeGroupFactory
     * @param Helper $helper
     * @param Request $request
     * @param array|null $data
     */
    public function __construct(
        SuggestionTypeGroupFactory $suggestionTypeGroupFactory,
        Helper $helper,
        Request $request,
        array $data = null
    ) {
        $this->suggestionTypeGroupFactory = $suggestionTypeGroupFactory;
        parent::__construct($helper, $request, $data);
    }

    /**
     * @param array $groups
     */
    public function setGroup(array $groups)
    {
        $groups = $this->normalizeArray($groups, 'group');

        $values = [];
        foreach ($groups as $group) {
            if (!$group instanceof SuggestionTypeGroup) {
                $suggestionGroup = $this->suggestionTypeGroupFactory->create();
                $suggestionGroup->setData($group);
                $values[] = $suggestionGroup;
            } else {
                $values[] = $group;
            }
        }

        $this->data['groups'] = $values;
        return $this;
    }
}
