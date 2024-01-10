<?php

namespace Tweakwise\Magento2Tweakwise\Model\Client\Type\SuggestionType;


/**
 * Class SuggestionTypeSearch
 * @package Tweakwise\Magento2Tweakwise\Model\Client\Type\SuggestionType
 */
class SuggestionTypeSearch extends SuggestionTypeAbstract
{
    public const TYPE = 'SearchPhrase';

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->getSearchUrl();
    }
}
