<?php
/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2Tweakwise\Model\Client\Response\Catalog;

use Tweakwise\Magento2Tweakwise\Model\Client\Response;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\TemplateType;

/**
 * @method TemplateType[] getTemplates();
 */
class TemplateResponse extends Response
{
    /**
     * @param TemplateType[]|array[] $templates
     * @return $this
     */
    public function setTemplate(array $templates)
    {
        $templates = $this->normalizeArray($templates, 'template');

        $values = [];
        foreach ($templates as $value) {
            if (!$value instanceof TemplateType) {
                $value = new TemplateType($value, 'templateid');
            }

            $values[] = $value;
        }

        $this->data['templates'] = $values;
        return $this;
    }
}
