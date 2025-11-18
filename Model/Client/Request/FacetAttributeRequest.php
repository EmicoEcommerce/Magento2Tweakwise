<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2Tweakwise\Model\Client\Request;

use Tweakwise\Magento2Tweakwise\Model\Client\Request;
use Tweakwise\Magento2Tweakwise\Model\Client\Response\FacetAttributesResponse;

class FacetAttributeRequest extends Request
{
    /**
     * @var string
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

    /**
     * @param $facetKey
     * @return void
     */
    public function addFacetKey($facetKey) // @phpstan-ignore-line
    {
        $this->setPath($this->path . '/' . $facetKey . '/attributes');
    }
}
