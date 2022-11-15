<?php
/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Tweakwise\Magento2Tweakwise\Block\Catalog\Product\ProductList;

class Plugin
{
    /**
    * Retrieve additional blocks html
    *
    * @return string
    */
    public function afterGetAdditionalHtml($subject, $result)
    {
        $searchBanner = $subject->getBlockHtml('navigation.search.banner.products.top');

        return $searchBanner . $result;
    }
}
