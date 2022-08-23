<?php

namespace Tweakwise\Magento2Tweakwise\Block\LayeredNavigation\RenderLayered;

use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Filter\Item;
use Tweakwise\Magento2Tweakwise\Model\Seo\FilterHelper;

trait AnchorRendererTrait
{
    /**
     * @var FilterHelper
     */
    protected $filterHelper;

    /**
     * @param Item $item
     * @return string
     */
    public function renderAnchorHtmlTagAttributes(Item $item)
    {
        $anchorAttributes = $this->getAnchorTagAttributes($item);
        $attributeHtml = [];
        foreach ($anchorAttributes as $anchorAttribute => $anchorAttributeValue) {
            $attributeHtml[] = sprintf('%s="%s"', $anchorAttribute, $anchorAttributeValue);
        }
        return implode(' ', $attributeHtml);
    }

    /**
     * @param Item $item
     * @return string[]
     */
    protected function getAnchorTagAttributes(Item $item): array
    {
        $itemUrl = preg_replace('/&amp;p=\d+/', '', $this->getItemUrl($item));
        if ($this->filterHelper->shouldFilterBeIndexable($item)) {
            return ['href' => $itemUrl];
        }

        return ['href' => '#', 'data-seo-href' => $itemUrl];
    }

    /**
     * @param Item $item
     * @return string
     */
    protected function getItemUrl(Item $item)
    {
        return $this->escapeHtml($item->getUrl());
    }
}
