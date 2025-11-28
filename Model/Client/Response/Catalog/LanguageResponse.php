<?php

namespace Tweakwise\Magento2Tweakwise\Model\Client\Response\Catalog;

use Tweakwise\Magento2Tweakwise\Model\Client\Response;

class LanguageResponse extends Response
{
    /**
     * Format response to a list of languages
     *
     * @return array
     */
    public function getLanguages(): array
    {
        return $this->normalizeArray($this->data, 'language');
    }
}
