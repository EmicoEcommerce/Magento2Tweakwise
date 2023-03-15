<?php
/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2Tweakwise\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class CrosssellTemplateType implements OptionSourceInterface
{
    /**
     * Possible crosssell types
     */
    public const TYPE_CROSSSELL = 'crosssell';
    public const TYPE_FEATURED = 'featured';

    /**
     * @var array[]
     */
    protected $options;

    /**
     * @return array
     */
    protected function buildOptions()
    {
        return [
            ['value' => self::TYPE_CROSSSELL, 'label' => __('Crosssell template')],
            ['value' => self::TYPE_FEATURED, 'label' => __('Featured products template')],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        if (!$this->options) {
            $this->options = $this->buildOptions();
        }
        return $this->options;
    }
}
