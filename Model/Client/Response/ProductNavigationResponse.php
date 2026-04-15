<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

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
        // @phpstan-ignore-next-line
        foreach ($this->getItems() as $item) {
            $ids[] = $this->helper->getStoreId($item->getId());
        }

        return $ids;
    }

    /**
     * @return array
     */
    public function getProductData(): array
    {
        $productData = [];
        // @phpstan-ignore-next-line
        foreach ($this->getItems() as $item) {
            $data = [];
            if ($item->getImage()) {
                // Remove domain and media path when full url is used
                $imageUrl = preg_replace('#^.*?/catalog/product/#', '', $item->getImage());
                $data[ItemType::IMAGE] = $imageUrl;
            }

            $tweakwiseId = $item->getTweakwiseId();
            if (!empty($tweakwiseId)) {
                $data[ItemType::TWEAKWISE_ID] = $tweakwiseId;
            }

            $groupCode = $item->getGroupCodeFromAttributes();
            if (!empty($groupCode)) {
                $data[ItemType::GROUPCODE] = $groupCode;
            }

            $data[ItemType::COLSPAN] = $item->getColspan();
            $data[ItemType::ROWSPAN] = $item->getRowspan();

            $productData[$this->helper->getStoreId($item->getId())] = $data;
        }

        return $productData;
    }
}
