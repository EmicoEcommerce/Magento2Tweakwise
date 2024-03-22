<?php

/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\FilterList;

use Magento\Catalog\Model\Layer\FilterableAttributeListInterface;

class EmptyAttributeList implements FilterableAttributeListInterface
{
    /**
     * {@inheritdoc}
     */
    public function getList()
    {
        return [];
    }
}
