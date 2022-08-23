<?php
/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url;

use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Filter\Item;
use Magento\Framework\App\Request\Http as MagentoHttpRequest;

/**
 * Interface UrlInterface implementation should handle both category url's.
 */
interface CategoryUrlInterface
{
    /**
     * @param MagentoHttpRequest $request
     * @param Item $item
     * @return mixed
     */
    public function getCategoryFilterSelectUrl(MagentoHttpRequest $request, Item $item): string;

    /**
     * @param MagentoHttpRequest $request
     * @param Item $item
     * @return mixed
     */
    public function getCategoryFilterRemoveUrl(MagentoHttpRequest $request, Item $item): string;
}
