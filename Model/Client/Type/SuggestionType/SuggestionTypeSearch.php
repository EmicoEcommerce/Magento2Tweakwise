<?php

namespace Tweakwise\Magento2Tweakwise\Model\Client\Type\SuggestionType;

use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\Strategy\QueryParameterStrategy;
use Tweakwise\Magento2TweakwiseExport\Model\Helper;
use Magento\Framework\UrlInterface;

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
