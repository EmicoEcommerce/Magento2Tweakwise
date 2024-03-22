<?php

namespace Tweakwise\Magento2Tweakwise\Model\Client\Request\Catalog;

use Tweakwise\Magento2Tweakwise\Model\Client\Request;
use Tweakwise\Magento2Tweakwise\Model\Client\Response\Catalog\SortTemplateResponse;

class SortTemplateRequest extends Request
{
    /**
     * @var string
     */
    protected $path = 'catalog/sorttemplates';

    /**
     * @return string
     */
    public function getResponseType()
    {
        return SortTemplateResponse::class;
    }
}
