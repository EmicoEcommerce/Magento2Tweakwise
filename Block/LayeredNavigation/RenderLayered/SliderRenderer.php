<?php

/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2Tweakwise\Block\LayeredNavigation\RenderLayered;

use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2Tweakwise\Model\NavigationConfig;
use Tweakwise\Magento2Tweakwise\Model\Seo\FilterHelper;
use Magento\Tax\Helper\Data as TaxHelper;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Framework\View\Element\Template;
use Magento\Framework\Serialize\Serializer\Json;
use Tweakwise\Magento2TweakwiseExport\Model\Helper;

class SliderRenderer extends DefaultRenderer
{
    /**
     * @var string
     */
    protected $_template = 'Tweakwise_Magento2Tweakwise::product/layered/slider.phtml';

    /**
     * @var PriceHelper
     */
    protected $priceHelper;

    /**
     * @var TaxHelper
     */
    protected $taxHelper;

    /**
     * SliderRenderer constructor.
     * @param PriceHelper $priceHelper
     * @param TaxHelper $taxHelper
     * @param Config $config
     * @param NavigationConfig $navigationConfig
     * @param FilterHelper $filterHelper
     * @param Template\Context $context
     * @param Json $jsonSerializer
     * @param Helper $helper
     * @param array $data
     */
    public function __construct(
        PriceHelper $priceHelper,
        TaxHelper $taxHelper,
        Config $config,
        NavigationConfig $navigationConfig,
        FilterHelper $filterHelper,
        Template\Context $context,
        Json $jsonSerializer,
        Helper $helper,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $config,
            $navigationConfig,
            $filterHelper,
            $jsonSerializer,
            $helper,
            $data
        );
        $this->priceHelper = $priceHelper;
        $this->taxHelper = $taxHelper;
    }

    /**
     * @param int $index
     * @param int|float $default
     * @return int|float|string
     */
    protected function getItemValue($index, $default = 0)
    {
        $items = $this->getItems();
        if (!isset($items[$index])) {
            return $default;
        }

        return (float) $items[$index]->getLabel();
    }

    /**
     * @return int
     */
    public function getMinValue()
    {
        return floor($this->getItemValue(2, $this->getCurrentMinValue()));
    }

    /**
     * @return int
     */
    public function getMaxValue()
    {
        return ceil($this->getItemValue(3, $this->getCurrentMaxValue()));
    }

    /**
     * @return int
     */
    public function getCurrentMinValue()
    {
        return floor($this->getItemValue(0));
    }

    /**
     * @return int
     */
    public function getCurrentMaxValue()
    {
        return ceil($this->getItemValue(1, 99999));
    }

    /**
     * @param string $value
     * @return float|string
     */
    public function renderValue($value)
    {
        if (!$this->filter->getFacet()->getFacetSettings()->isPrice()) {
            return $value;
        }

        return $this->priceHelper->currency($value, true, false);
    }

    /**
     * @return string
     */
    public function getPriceFormatJson()
    {
        return $this->taxHelper->getPriceFormat();
    }

    /**
     * @return string
     */
    public function getFilterUrl()
    {
        $items = $this->getItems();
        if (!isset($items[0])) {
            return '#';
        }

        return $items[0]->getUrl();
    }

    /**
     * @return string
     */
    public function getJsSliderConfig(): string
    {
        return $this->navigationConfig->getJsSliderConfig($this);
    }

    /**
     * @return string
     */
    public function getCssId()
    {
        $anyItem = $this->getItems()[0];
        $urlKey = $anyItem->getFilter()
            ->getFacet()
            ->getFacetSettings()
            ->getUrlKey();

        return 'slider-' . $urlKey;
    }

    /**
     * @return bool
     */
    public function containsBuckets(): bool
    {
        return $this->filter->getFacet()->getFacetSettings()->containsBuckets();
    }

    /**
     * @return bool
     */
    public function containsClickpoints(): bool
    {
        return $this->filter->getFacet()->getFacetSettings()->containsClickpoints();
    }

    /**
     * @return array
     */
    public function getBuckets(): array
    {
        if ($this->containsBuckets()) {
            return $this->filter->getFacet()->getBuckets();
        }

        return [];
    }

    public function getClickPoints(): array
    {
        if ($this->containsClickpoints()) {
            return $this->filter->getFacet()->getClickpoints();
        }

        return [];
    }

    public function getBucketHightFactor(): float
    {
        $maxRelativeRange = 1;
        if ($this->containsBuckets()) {
            $buckets = $this->getBuckets();
            foreach ($buckets as $bucket) {
                //get max range from bucket array
                if ($bucket['relativeamount'] > $maxRelativeRange) {
                    $relativeAmount = ceil($bucket['relativeamount']);
                    if ($relativeAmount > $maxRelativeRange) {
                        $maxRelativeRange = $relativeAmount;
                    }
                }
            }
        }

        $bucketHightFactor = 60 / $maxRelativeRange;

        return $bucketHightFactor;
    }

    public function getTotalrange(): float
    {
        return ($this->getMaxValue() - $this->getMinValue());
    }
}
