<?php
/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2Tweakwise\Model\Client\Response;

use Tweakwise\Magento2Tweakwise\Model\Client\Response;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\AttributeType;

/**
 * @package Tweakwise\Magento2Tweakwise\Model\Client\Response
 */
class FacetAttributesResponse extends Response
{
    /**
     * @param AttributeType[]|array[] $attributes
     * @return $this
     */
    public function setAttributes(array $attributes)
    {
        $attributes = $this->normalizeArray($attributes, 'attributes');

        $values = [];
        foreach ($attributes as $value) {
            if (!$value instanceof AttributeType) {
                $value = new AttributeType($value);
            }

            $attributes = $value->getValue('attribute');

            if (isset($attributes[0])) {
                $this->data['attributes'] = $value->getValue('attribute');
            }else {
                //only one result
                $this->data['attributes'][] = $value->getValue('attribute');
            }

            return $this->data['attributes'];

        }

        return $this;
    }

    public function getAttributes(){
        if (isset($this->data['attributes'])) {
            return $this->data['attributes'];
        }
        return [];
    }
}
