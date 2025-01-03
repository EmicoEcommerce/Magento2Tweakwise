<?php

namespace Tweakwise\Magento2Tweakwise\Model\Client\Request\Catalog;

use Tweakwise\Magento2Tweakwise\Model\Client\Request;
use Tweakwise\Magento2Tweakwise\Model\Client\Response\Catalog\BuilderTemplateResponse;

class BuilderTemplateRequest extends Request
{
    /**
     * @var string
     */
    protected $path = 'catalog/builders';

    /**
     * @return string
     */
    public function getResponseType(): string
    {
        return BuilderTemplateResponse::class;
    }
}
