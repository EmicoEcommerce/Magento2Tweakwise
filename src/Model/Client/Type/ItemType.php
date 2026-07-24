<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

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
    public const ID = 'itemno';
    public const TYPE = 'type';
    public const ORDER = 'order';
    public const TITLE = 'title';
    public const PRICE = 'price';
    public const BRAND = 'brand';
    public const IMAGE = 'image';
    public const URL = 'url';
    public const ATTRIBUTES = 'attributes';
    public const TWEAKWISE_ID = 'tw_id';
    public const VISUAL_PROPERTIES = 'visualproperties';
    public const COLSPAN = 'colspan';
    public const ROWSPAN = 'rowspan';

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
     * @return int
     */
    public function getId()
    {
        return (int)$this->getDataValue(self::ID);
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return (string)$this->getDataValue(self::TYPE);
    }

    /**
     * @return string
     */
    public function getOrder()
    {
        return (string)$this->getDataValue(self::ORDER);
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return (string)$this->getDataValue(self::TITLE);
    }

    /**
     * @return float
     */
    public function getPrice()
    {
        return (float)$this->getDataValue(self::PRICE);
    }

    /**
     * Only works if the final_price is added on the tweakwise side. Contact tweakwise support if you want to add the final_price to the TW response
     * @return float
     */
    public function getFinalPrice()
    {
        $attributes = $this->getDataValue(self::ATTRIBUTES);
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

        return 0.0;
    }

    /**
     * @return string
     */
    public function getBrand()
    {
        return (string) $this->getDataValue(self::BRAND);
    }

    /**
     * @return string
     */
    public function getImage()
    {
        return (string) $this->getDataValue(self::IMAGE);
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return (string) $this->getDataValue(self::URL);
    }

    /**
     * @return string
     */
    public function getGroupCodeFromAttributes(): string
    {
        $attributes = $this->normalizeArray($this->getDataValue(self::ATTRIBUTES), 'attribute');

        foreach ($attributes as $attribute) {
            if (
                isset($attribute['name']) &&
                $attribute['name'] === 'groupcode' &&
                isset($attribute['values']['value'])
            ) {
                return (string)$attribute['values']['value'];
            }
        }

        return '';
    }

    /**
     * @return string|null
     */
    public function getTweakwiseId(): ?string
    {
        return (string)$this->getDataValue(self::TWEAKWISE_ID);
    }

    /**
     * @return int|null
     */
    public function getColspan(): ?int
    {
        $visualProperties = $this->getVisualProperties();
        if (!$visualProperties) {
            return null;
        }

        return (int)$visualProperties[self::COLSPAN];
    }

    /**
     * @return int|null
     */
    public function getRowspan(): ?int
    {
        $visualProperties = $this->getVisualProperties();
        if (!$visualProperties) {
            return null;
        }

        return (int)$visualProperties[self::ROWSPAN];
    }

    /**
     * @return array|null
     */
    protected function getVisualProperties(): ?array
    {
        return $this->getDataValue(self::VISUAL_PROPERTIES);
    }
}
