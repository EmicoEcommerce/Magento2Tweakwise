<?php

namespace Tweakwise\Magento2Tweakwise\Model\AjaxResultInitializer;

use Tweakwise\Magento2Tweakwise\Model\AjaxNavigationResult;
use Magento\Framework\App\RequestInterface;

interface InitializerInterface
{
    public const LAYOUT_HANDLE_SEARCH = 'tweakwise_ajax_search';
    public const LAYOUT_HANDLE_CATEGORY = 'tweakwise_ajax_category';

    /**
     * Initialize the ajax navigation result object (i.e. add layouts, create layers, populate registry etc)
     *
     * @param AjaxNavigationResult $ajaxNavigationResult
     * @param RequestInterface $request
     * @return mixed
     */
    public function initializeAjaxResult(
        AjaxNavigationResult $ajaxNavigationResult,
        RequestInterface $request
    );
}
