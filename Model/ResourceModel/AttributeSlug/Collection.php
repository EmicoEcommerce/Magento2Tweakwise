<?php
/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2Tweakwise\Model\ResourceModel\AttributeSlug;

use Tweakwise\Magento2Tweakwise\Model\AttributeSlug;
use Tweakwise\Magento2Tweakwise\Model\ResourceModel\AttributeSlug as ResourceModel;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    /**
     * @var string
     */
    protected $_idFieldName = 'attribute';

    /**
     *
     */
    protected function _construct()
    {
        $this->_init(AttributeSlug::class, ResourceModel::class);
    }

}
