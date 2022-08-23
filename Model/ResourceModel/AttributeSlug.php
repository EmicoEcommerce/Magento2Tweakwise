<?php
/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2Tweakwise\Model\ResourceModel;

use Tweakwise\Magento2Tweakwise\Api\Data\AttributeSlugInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class AttributeSlug extends AbstractDb
{
    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('tweakwise_attribute_slug', AttributeSlugInterface::ATTRIBUTE);
        $this->_isPkAutoIncrement = false;
    }
}
