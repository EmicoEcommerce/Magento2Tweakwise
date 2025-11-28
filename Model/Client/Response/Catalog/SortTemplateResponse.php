<?php

namespace Tweakwise\Magento2Tweakwise\Model\Client\Response\Catalog;

use Tweakwise\Magento2Tweakwise\Model\Client\Response;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\TemplateType;

class SortTemplateResponse extends Response
{
    /**
     * @param TemplateType[]|array[] $templates
     * @return $this
     */
    public function setSorttemplate(array $templates)
    {
        $templates = $this->normalizeArray($templates, 'sorttemplate');

        $values = [];
        foreach ($templates as $value) {
            if (!$value instanceof TemplateType) {
                $value = new TemplateType($value, 'sorttemplateid');
            }

            $values[] = $value;
        }

        $this->data['templates'] = $values;
        return $this;
    }
}
