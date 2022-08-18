<?php

namespace Tweakwise\Magento2Tweakwise\Model\Client\Type\SuggestionType;

/**
 * Interface SuggestionTypeInterface
 * @package Tweakwise\Magento2Tweakwise\Model\Client\Type\SuggestionType
 */
interface SuggestionTypeInterface
{
    /**
     * @return string
     */
    public function getUrl();

    /**
     * @return string
     */
    public function getName();
}
