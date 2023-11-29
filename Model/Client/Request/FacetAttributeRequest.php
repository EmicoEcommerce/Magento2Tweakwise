<?php
/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2Tweakwise\Model\Client\Request;

use Magento\Store\Model\StoreManager;
use Tweakwise\Magento2Tweakwise\Exception\ApiException;
use Tweakwise\Magento2Tweakwise\Model\Client\Request;
use Tweakwise\Magento2Tweakwise\Model\Client\Response\FacetAttributesResponse;
use Tweakwise\Magento2TweakwiseExport\Model\Helper;

class FacetAttributeRequest extends Request
{
    /**
     * {@inheritDoc}
     */
    protected $path = 'facets';

    /**
     * @var array
     */
    protected $hiddenParameters = [];

    /**
     * {@inheritdoc}
     */
    public function getResponseType()
    {
        return FacetAttributesResponse::class;
    }

    public function addFacetKey($facetKey) {
        $this->setPath($this->path . '/' . $facetKey . '/attributes');
    }
}
