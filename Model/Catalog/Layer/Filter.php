<?php

/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2Tweakwise\Model\Catalog\Layer;

use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Filter\Item;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\AttributeType;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\FacetType;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Filter\ItemFactory;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\FacetType\SettingsType;
use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\Layer\Filter\AbstractFilter;
use Magento\Catalog\Model\Layer\Filter\FilterInterface;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Eav\Model\Entity\Attribute\Option;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\StoreManager;

/**
 * Class Filter Extends
 *
 * @see \Magento\Catalog\Model\Layer\Filter\AbstractFilter
 * only for the type hint in
 * @see \Magento\Swatches\Block\LayeredNavigation\RenderLayered
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class Filter extends AbstractFilter implements FilterInterface
{
    /**
     * @var string
     */
    protected $requestVar;

    /**
     * @var array
     */
    protected $items;

    /**
     * @var Layer
     */
    protected $layer;

    /**
     * @var Attribute
     */
    protected $attributeModel;

    /**
     * @var int
     */
    protected $storeId;

    /**
     * @var int
     */
    protected $websiteId;

    /**
     * @var FacetType
     */
    protected $facet;

    /**
     * @var ItemFactory
     */
    protected $itemFactory;

    /**
     * @var StoreManager
     */
    protected $storeManager;

    /**
     * @var int[]
     */
    protected $optionLabelValueMap;

    /**
     * @var Item[]
     */
    protected $optionLabelItemMap;

    /**
     * Filter constructor.
     *
     * @param Layer $layer
     * @param FacetType $facet
     * @param ItemFactory $itemFactory
     * @param StoreManager $storeManager
     * @param Attribute|null $attribute
     * @noinspection MagicMethodsValidityInspection
     * @noinspection PhpMissingParentConstructorInspection
     */
    public function __construct(
        Layer $layer,
        FacetType $facet,
        ItemFactory $itemFactory,
        StoreManager $storeManager,
        Attribute $attribute = null
    ) {
        $this->layer = $layer;
        $this->facet = $facet;
        $this->itemFactory = $itemFactory;
        $this->storeManager = $storeManager;
        $this->attributeModel = $attribute;
    }

    /**
     * {@inheritdoc}
     */
    public function setRequestVar($varName)
    {
        $this->requestVar = $varName;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getRequestVar()
    {
        return $this->requestVar;
    }

    /**
     * {@inheritdoc}
     */
    public function getResetValue()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getCleanValue()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(RequestInterface $request)
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getItemsCount()
    {
        return count($this->getItems());
    }

    /**
     * @return Item[]
     * {@inheritdoc}
     */
    public function getItems()
    {
        if (!$this->items) {
            $this->initItems();
        }

        return $this->items;
    }

    /**
     * @param AttributeType $item
     * @return $this
     */
    public function addItem(AttributeType $item)
    {
        $this->items[] = $this->createItem($item);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setItems(array $items)
    {
        $this->items = [];
        foreach ($items as $item) {
            $this->addItem($item);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getLayer()
    {
        return $this->layer;
    }

    /**
     * @return Item[]
     */
    public function getActiveItems()
    {
        $result = [];
        foreach ($this->getItems() as $item) {
            if (!$item->isActive()) {
                continue;
            }

            $hasChildren = $item->getChildren();
            if (!$hasChildren) {
                $result[] = $item;
            } else {
                $deepestActiveChild = $this->findDeepestActiveChildItem($item) ?: $item;
                $result[] = $deepestActiveChild;
            }
        }

        $settings = $this->facet->getFacetSettings();
        if ($settings->getSelectionType() === SettingsType::SELECTION_TYPE_SLIDER) {
            return $this->combineActiveSliderItems($result);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function setAttributeModel($attribute)
    {
        $this->attributeModel = $attribute;
        $this->optionLabelValueMap = null;
        $this->optionLabelItemMap = null;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributeModel()
    {
        return $this->attributeModel;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        $title = (string) $this->facet->getFacetSettings()->getTitle();
        return htmlentities($title);
    }

    /**
     * {@inheritdoc}
     */
    public function getStoreId()
    {
        if ($this->storeId === null) {
            $this->storeId = $this->storeManager->getStore()->getId();
        }

        return $this->storeId;
    }

    /**
     * {@inheritdoc}
     */
    public function setStoreId($storeId)
    {
        $this->storeId = (int) $storeId;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getWebsiteId()
    {
        if ($this->websiteId === null) {
            $this->websiteId = $this->storeManager->getStore()->getWebsiteId();
        }

        return $this->websiteId;
    }

    /**
     * {@inheritdoc}
     */
    public function setWebsiteId($websiteId)
    {
        $this->websiteId = (int) $websiteId;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getClearLinkText()
    {
        return false;
    }

    /**
     * @return bool
     */
    public function hasAttributeModel()
    {
        return $this->attributeModel !== null;
    }

    /**
     * @return FacetType
     */
    public function getFacet()
    {
        return $this->facet;
    }

    /**
     * @param AttributeType $attributeType
     * @return Item
     */
    protected function createItem(AttributeType $attributeType)
    {
        $item = $this->itemFactory->create(['filter' => $this, 'attributeType' => $attributeType]);

        $children = [];
        foreach ($attributeType->getChildren() as $childAttributeType) {
            $children[] = $this->createItem($childAttributeType);
        }

        $item->setChildren($children);

        return $item;
    }

    /**
     * @param Item $item
     */
    protected function findDeepestActiveChildItem(Item $item)
    {
        if (!$item->hasChildren()) {
            return $item->isActive() ? $item : null;
        }

        foreach ($item->getChildren() as $child) {
            if ($child->isActive()) {
                return $this->findDeepestActiveChildItem($child);
            }
        }

        return $item->isActive() ? $item : null;
    }

    /**
     * @return $this
     */
    protected function initItems()
    {
        foreach ($this->facet->getAttributes() as $attribute) {
            $this->addItem($attribute);
        }

        return $this;
    }

    /**
     * @return int[]
     */
    protected function getOptionLabelValueMap()
    {
        if (!$this->hasAttributeModel()) {
            return [];
        }

        if ($this->optionLabelValueMap === null) {
            $map = [];
            /** @var Option $option */
            foreach ($this->getAttributeModel()->getOptions() as $option) {
                $map[(string)$option->getLabel()] = $option->getValue();
            }

            $this->optionLabelValueMap = $map;
        }

        return $this->optionLabelValueMap;
    }

    /**
     * @return Item[]
     */
    protected function getOptionLabelItemMap()
    {
        if (!$this->hasAttributeModel()) {
            return [];
        }

        if ($this->optionLabelItemMap === null) {
            $map = [];
            /** @var Item $item */
            foreach ($this->getItems() as $item) {
                $map[$item->getLabel()] = $item;
            }

            $this->optionLabelItemMap = $map;
        }

        return $this->optionLabelItemMap;
    }

    /**
     * @param string $label
     * @return int|null
     */
    public function getOptionIdByLabel($label)
    {
        $map = $this->getOptionLabelValueMap();
        return $map[$label] ?? null;
    }

    /**
     * @param int $id
     * @return string|null
     */
    public function getLabelByOptionId($id)
    {
        $map = $this->getOptionLabelValueMap();
        $map = array_flip($map);
        return $map[$id] ?? null;
    }

    /**
     * @param int $optionId
     * @return Item|null
     */
    public function getItemByOptionId($optionId)
    {
        $label = $this->getLabelByOptionId($optionId);
        if (!$label) {
            return null;
        }

        $map = $this->getOptionLabelItemMap();
        return $map[$label] ?? null;
    }

    /**
     * @return bool
     */
    public function isCollapsible()
    {
        return $this->facet->getFacetSettings()->getIsCollapsible();
    }

    /**
     * @return bool
     */
    public function isDefaultCollapsed()
    {
        return $this->facet->getFacetSettings()->getIsCollapsed();
    }

    /**
     * @return string
     */
    public function getCssClass()
    {
        return $this->facet->getFacetSettings()->getCssClass();
    }

    /**
     * @return string
     */
    public function getTooltip()
    {
        $facetSettings = $this->facet->getFacetSettings();
        return $facetSettings->getIsInfoVisible() ? $facetSettings->getInfoText() : '';
    }

    /**
     * @return string
     */
    public function getUrlKey()
    {
        return $this->facet->getFacetSettings()->getUrlKey();
    }

    /**
     * @param Item[] $activeItems
     * @return Item[]
     */
    protected function combineActiveSliderItems(array $activeItems)
    {
        if (count($activeItems) !== 2) {
            return $activeItems;
        }

        $selectedMin = $activeItems[0]->getAttribute()->getTitle();
        $selectedMax = $activeItems[1]->getAttribute()->getTitle();
        $mockedActiveItem = clone($activeItems[0]);
        $mockedActiveItem->getAttribute()->setValue('title', "$selectedMin-$selectedMax");

        return [$mockedActiveItem];
    }
}
