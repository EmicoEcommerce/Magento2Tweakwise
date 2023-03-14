<?php
/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2Tweakwise\Model\Client\Type;

use Tweakwise\Magento2Tweakwise\Model\Client\Type\FacetType\SettingsType;

/**
 * @method SettingsType getFacetSettings();
 * @method AttributeType[] getAttributes();
 */
class FacetType extends Type
{
    /**
     * @param SettingsType|array $settings
     * @return $this
     */
    public function setFacetSettings($settings)
    {
        if (!$settings instanceof SettingsType) {
            $settings = new SettingsType($settings);
        }

        $this->data['facet_settings'] = $settings;
        return $this;
    }

    /**
     * @param AttributeType[]|array[] $attributes
     * @return $this
     */
    public function setAttributes(array $attributes)
    {
        $attributes = $this->normalizeArray($attributes, 'attribute');

        $values = [];
        foreach ($attributes as $value) {
            if (!$value instanceof AttributeType) {
                $value = new AttributeType($value);
            }

            $values[] = $value;
        }

        $this->data['attributes'] = $values;
        return $this;
    }
        
    public function getFacetSettings(): ?SettingsType
    {
        return $this->getValue('facet_settings');
    }

    /**
     * @return AttributeType[]
     */
    public function getAttributes(): ?array
    {
        return $this->getValue('attributes');
    }
}
