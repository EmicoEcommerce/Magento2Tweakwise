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

/**
 * @package Tweakwise\Magento2Tweakwise\Model\Client\Response
 * @method FacetType[] getFacets();
 */
class FacetResponse extends Response
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
}
