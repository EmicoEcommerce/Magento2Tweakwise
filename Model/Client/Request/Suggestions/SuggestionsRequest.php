<?php

namespace Tweakwise\Magento2Tweakwise\Model\Client\Request\Suggestions;

use Tweakwise\Magento2Tweakwise\Model\Client\Request;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\SearchRequestInterface;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\SearchRequestTrait;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2TweakwiseExport\Model\Helper;
use Magento\Store\Model\StoreManager;
use Tweakwise\Magento2Tweakwise\Model\Client\Response\Suggestions\SuggestionsResponse;

class SuggestionsRequest extends Request implements SearchRequestInterface
{
    use SearchRequestTrait;

    /**
     * {@inheritDoc}
     */
    protected $path = 'suggestions';

    /**
     * @return string
     */
    public function getResponseType()
    {
        return SuggestionsResponse::class;
    }
}
