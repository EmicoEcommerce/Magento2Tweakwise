<?php

/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url;

use Tweakwise\Magento2Tweakwise\Model\Client\Request\ProductNavigationRequest;
use Magento\Framework\App\Request\Http as MagentoHttpRequest;

/**
 * Interface UrlInterface implementation should handle both category url's and
 */
interface FilterApplierInterface
{
    /**
     * Apply all attribute filters, category filters, sort order, page limit request parameters to navigation request
     *
     * @param MagentoHttpRequest $request
     * @param ProductNavigationRequest $navigationRequest
     * @return $this
     */
    public function apply(
        MagentoHttpRequest $request,
        ProductNavigationRequest $navigationRequest
    ): FilterApplierInterface;
}
