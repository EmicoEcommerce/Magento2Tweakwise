<?php
/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2Tweakwise\Model\Client\Type;

class AttributeType extends Type
{
    /**
     * @param AttributeType[]|array[] $children
     * @return $this
     */
    public function setChildren(array $children)
    {
        $children = $this->normalizeArray($children, 'attribute');

        $values = [];
        foreach ($children as $value) {
            if (!$value instanceof AttributeType) {
                $value = new AttributeType($value);
            }

            $values[] = $value;
        }

        $this->data['children'] = $values;
        return $this;
    }

    /**
     * @return string
     */
    public function getChildren()
    {
        return $this->getDataValue('children');
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return (string) $this->getDataValue('title');
    }

    /**
     * @return bool
     */
    public function getIsSelected()
    {
        return $this->getDataValue('isselected') == 'true';
    }

    /**
     * @return int
     */
    public function getNumberOfResults()
    {
        return (int) $this->getDataValue('nrofresults');
    }

    /**
     * @return int
     */
    public function getAttributeId()
    {
        return (string) $this->getDataValue('attributeid');
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return (string) $this->getDataValue('url');
    }

    /**
     * @return int|null
     */
    public function getAlternateSortOrder()
    {
        return $this->hasValue('@alternatesortorder')
            ? (int) $this->getDataValue('@alternatesortorder')
            : null;
    }
}
