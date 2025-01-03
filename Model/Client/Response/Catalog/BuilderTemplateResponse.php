<?php

namespace Tweakwise\Magento2Tweakwise\Model\Client\Response\Catalog;

use Tweakwise\Magento2Tweakwise\Model\Client\Response;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\TemplateType;

class BuilderTemplateResponse extends Response
{
    /**
     * @param TemplateType[]|array[] $templates
     * @return $this
     */
    public function setBuilder(array $templates)
    {
        $templates = $this->normalizeArray($templates, 'builder');

        $values = [];
        foreach ($templates as $value) {
            if (!$value instanceof TemplateType) {
                $value = new TemplateType($value, 'id');
            }

            $values[] = $value;
        }

        $this->data['templates'] = $values;
        return $this;
    }
}
