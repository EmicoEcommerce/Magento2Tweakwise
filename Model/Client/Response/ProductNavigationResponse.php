<?php

/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2Tweakwise\Model\Client\Response;

use Tweakwise\Magento2Tweakwise\Model\Client\Response;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\FacetType;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\ItemType;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\PropertiesType;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\RedirectType;

class ProductNavigationResponse extends Response
{
    /**
     * @param FacetType[]|array[] $facets
     * @return $this
     */
    public function setFacets(array $facets)
    {
        $facets = $this->normalizeArray($facets, 'facet');

        $values = [];
        foreach ($facets as $value) {
            if (!$value instanceof FacetType) {
                $value = new FacetType($value);
            }

            $values[] = $value;
        }

        $this->data['facets'] = $values;
        return $this;
    }

    /**
     * @param ItemType[]|array[] $items
     * @return $this
     */
    public function setItems(array $items)
    {
        $items = $this->normalizeArray($items, 'item');

        $values = [];
        foreach ($items as $value) {
            if (!$value instanceof ItemType) {
                $value = new ItemType($value);
            }

            $values[] = $value;
        }

        $this->data['items'] = $values;
        return $this;
    }

    /**
     * Function to get items from groups and set the items
     * @param array $groups
     * @return $this
     */
    public function setGroups(array $groups): ProductNavigationResponse
    {
        if (!$groups) {
            $this->setItems([]);
            return $this;
        }

        $items = [];
        foreach ($groups['group'] as $group) {
            $simple = $this->getMostSuitableVariant($group);
            $configurable = $this->getConfigurable($group);

            if (!$configurable) {
                continue;
            }

            if (!empty($simple['image'])) {
                $configurable['image'] = $simple['image'];
            }

            if (!empty($simple['type'])) {
                $configurable['type'] = $simple['type'];
            }

            $items[] = $configurable;
        }

        $this->setItems($items);
        return $this;
    }

    /**
     * Function to get most suitable variant. This is always the first item in the array.
     * @param array $group
     * @return array
     */
    private function getMostSuitableVariant(array $group): array
    {
        if (isset($group['items']['item'][0])) {
            return reset($group['items']['item']);
        }

        return $group['items']['item'];
    }

    /**
     * @param array $group
     * @return array
     */
    private function getConfigurable(array $group): array
    {
        return ['itemno' => $group['code']];
    }

    /**
     * @param PropertiesType|array $properties
     * @return $this
     */
    public function setProperties($properties)
    {
        if (!$properties instanceof PropertiesType) {
            $properties = new PropertiesType($properties);
        }

        $this->data['properties'] = $properties;
        return $this;
    }

    /**
     * @param RedirectType[]|array[] $redirects
     * @return $this
     */
    public function setRedirects(array $redirects)
    {
        $redirects = $this->normalizeArray($redirects, 'redirect');

        $values = [];
        foreach ($redirects as $value) {
            if (!$value instanceof RedirectType) {
                $value = new RedirectType($value);
            }

            $values[] = $value;
        }

        $this->data['redirects'] = $values;
        return $this;
    }

    /**
     * @return int[]
     */
    public function getProductIds()
    {
        $ids = [];
        foreach ($this->getItems() as $item) {
            $ids[] = $this->helper->getStoreId($item->getId());
        }

        return $ids;
    }

    /**
     * @return array
     */
    public function getProductImages(): array
    {
        $productImages = [];
        foreach ($this->getItems() as $item) {
            if (!$item->getImage()) {
                continue;
            }

            // Remove domain and media path when full url is used
            $imageUrl = preg_replace("#^.*?/catalog/product/#", "", $item->getImage());
            $productImages[$this->helper->getStoreId($item->getId())] = $imageUrl;
        }

        return $productImages;
    }
}
