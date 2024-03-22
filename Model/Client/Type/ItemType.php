<?php

/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2Tweakwise\Model\Client\Type;

/**
 * @method LabelType[] getLabels();
 */
class ItemType extends Type
{
    /**
     * @param LabelType[]|array[] $labels
     * @return $this
     */
    public function setLabels(array $labels)
    {
        $labels = $this->normalizeArray($labels, 'label');

        $values = [];
        foreach ($labels as $value) {
            if (!$value instanceof LabelType) {
                $value = new LabelType($value);
            }

            $values[] = $value;
        }

        $this->data['labels'] = $values;
        return $this;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return (int) $this->getDataValue('itemno');
    }

    /**
     * @return string
     */
    public function getOrder()
    {
        return (string) $this->getDataValue('order');
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return (string) $this->getDataValue('title');
    }

    /**
     * @return int
     */
    public function getPrice()
    {
        return (float) $this->getDataValue('price');
    }

    /**
     * Only works if the final_price is added on the tweakwise side. Contact tweakwise support if you want to add the final_price to the TW response
     * @return int
     */
    public function getFinalPrice()
    {
        $attributes = $this->getDataValue('attributes');
        if (!empty($attributes['attribute'])) {
            foreach ($attributes['attribute'] as $attribute) {
                //only one result
                if (!is_array($attribute)) {
                    $attribute = $attributes['attribute'];
                }

                if (
                    isset($attribute['name']) &&
                    $attribute['name'] === 'final_price' &&
                    isset($attribute['values']['value'])
                ) {
                    return (float)($attribute['values']['value']);
                }
            }
        }

        return (float) 0;
    }

    /**
     * @return string
     */
    public function getBrand()
    {
        return (string) $this->getDataValue('brand');
    }

    /**
     * @return string
     */
    public function getImage()
    {
        return (string) $this->getDataValue('image');
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return (string) $this->getDataValue('url');
    }
}
