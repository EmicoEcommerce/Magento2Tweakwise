<?php

/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2Tweakwise\Block\LayeredNavigation\RenderLayered;

use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Filter;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Filter\Item;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\FacetType\SettingsType;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2Tweakwise\Model\NavigationConfig;
use Tweakwise\Magento2Tweakwise\Model\Seo\FilterHelper;
use Magento\Framework\View\Element\Template;
use Magento\Framework\Serialize\Serializer\Json;
use Tweakwise\Magento2TweakwiseExport\Model\Helper;

class DefaultRenderer extends Template
{
    use AnchorRendererTrait;

    /**
     * @var string
     */
    protected $_template = 'Tweakwise_Magento2Tweakwise::product/layered/default.phtml';

    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var Json
     */
    protected $jsonSerializer;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var NavigationConfig
     */
    protected $navigationConfig;

    protected $helper;

    /**
     * Constructor
     *
     * @param Template\Context $context
     * @param Config $config
     * @param NavigationConfig $navigationConfig
     * @param FilterHelper $filterHelper
     * @param Json $jsonSerializer
     * @param Helper $helper
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        Config $config,
        NavigationConfig $navigationConfig,
        FilterHelper $filterHelper,
        Json $jsonSerializer,
        Helper $helper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->config = $config;
        $this->filterHelper = $filterHelper;
        $this->jsonSerializer = $jsonSerializer;
        $this->navigationConfig = $navigationConfig;
        $this->helper = $helper;
    }

    /**
     * @param Filter $filter
     */
    public function setFilter(Filter $filter)
    {
        $this->filter = $filter;
    }

    /**
     * @return SettingsType
     */
    protected function getFacetSettings()
    {
        return $this->filter->getFacet()->getFacetSettings();
    }

    /**
     * @param Item $item
     * @return string
     */
    public function getCategoryUrl(Item $item): string
    {
        $catUrl = $item->getUrl();

        if (strpos($catUrl, $this->getBaseUrl()) === false) {
            $catUrl = $this->getBaseUrl() . $item->getUrl();
        }

        return $catUrl;
    }

    /**
     * @return Item[]
     */
    public function getItems()
    {
        $items = $this->filter->getItems();
        $type = $this->filter->getFacet()->getFacetSettings()->getSelectionType();
        $maxItems = $this->getMaxItemsShown();

        if ($this->config->isCategoryViewDefault() && $type == 'link') {
            $result = $this->findCurrentCategory($items);
            if (!empty($result)) {
                $items = $result;
            }
        }

        /** @var Item $item */
        foreach ($items as $index => $item) {
            $defaultShow = $index >= $maxItems;
            $item->setData('_default_hidden', $defaultShow);
        }

        return $items;
    }

    /**
     * @param array $items
     * @return array
     */
    private function findCurrentCategory($items)
    {
        $tweakwiseCategoryId = $this
                ->navigationConfig
                ->getNavigationContext()
                ->getContext()
                ->getResponse()
                ->getProperties()
                ->getSelectedCategoryId();

        if (empty($tweakwiseCategoryId)) {
            $storeId = $this->filter->getStoreId();
            $currentCategory = $this->filter->getLayer()->getCurrentCategory();
            $tweakwiseCategoryId = $this->helper->getTweakwiseId($storeId, $currentCategory->getId());
        }

        foreach ($items as $index => $item) {
            if ($item->getAttribute()->getValue('attributeid') == $tweakwiseCategoryId) {
                if (!empty($item->getChildren())) {
                    return $item->getChildren();
                } else {
                    //current category is the lowest level. Return all items on the same level
                    return $items;
                }
            } elseif (!empty($item->getChildren())) {
                //check if children are the current category
                $result = $this->findCurrentCategory($item->getChildren());
                if (!empty($result)) {
                    return $result;
                }
            }
        }

        return [];
    }

    /**
     * @return string
     */
    public function getJsSortConfig()
    {
        return $this->navigationConfig->getJsSortConfig($this->hasAlternateSortOrder());
    }

    /**
     * @return boolean
     */
    public function hasAlternateSortOrder()
    {
        $filter = function (Item $item) {
            return $item->getAlternateSortOrder() !== null;
        };

        $items = $this->getItems();
        $itemsWithAlternateSortOrder = array_filter($items, $filter);

        return \count($items) === \count($itemsWithAlternateSortOrder);
    }

    /**
     * @param Item $item
     * @return bool
     */
    public function itemDefaultHidden(Item $item)
    {
        return (bool) $item->getData('_default_hidden');
    }

    /**
     * @return int
     */
    public function getMaxItemsShown()
    {
        return $this->getFacetSettings()->getNumberOfShownAttributes();
    }

    /**
     * @return bool
     */
    public function hasHiddenItems()
    {
        return count($this->getItems()) > $this->getMaxItemsShown();
    }

    /**
     * @return string
     */
    public function getMoreItemText()
    {
        $text = $this->getFacetSettings()->getExpandText();
        if ($text) {
            return $text;
        }

        return 'Meer filters tonen';
    }

    /**
     * @return string
     */
    public function getLessItemText()
    {
        $text = $this->getFacetSettings()->getCollapseText();
        if ($text) {
            return $text;
        }

        return 'Minder filters tonen';
    }

    /**
     * @return bool
     */
    public function isSearchable()
    {
        return ($this->getFacetSettings()->isSearchable() && $this->hasHiddenItems());
    }

    /**
     * @return mixed|string|null
     */
    public function getSearchPlaceholder()
    {
        return $this->getFacetSettings()->getSearchPlaceholder();
    }

    /**
     * @return mixed|string|null
     */
    public function getSearchNoResultsText()
    {
        return $this->getFacetSettings()->getSearchNoResultsText();
    }

    /**
     * @return bool
     */
    public function shouldDisplayProductCountOnLayer()
    {
        return $this->getFacetSettings()->getIsNumberOfResultVisible();
    }

    /**
     * @return bool
     */
    public function showCheckbox()
    {
        return $this->getFacetSettings()->getSelectionType() === SettingsType::SELECTION_TYPE_CHECKBOX;
    }

    /**
     * @return string
     */
    public function getItemPrefix()
    {
        return $this->getFacetSettings()->getPrefix();
    }

    /**
     * @return string
     */
    public function getItemPostfix()
    {
        return $this->getFacetSettings()->getPostfix();
    }

    /**
     * @return string
     */
    public function getUrlKey()
    {
        return $this->getFacetSettings()->getUrlKey();
    }

    /**
     * @return bool
     */
    public function hasDefaultCategoryView()
    {
        return $this->config->isCategoryViewDefault();
    }
}
