<?php
/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2Tweakwise\Block\LayeredNavigation\RenderLayered\TreeRenderer;

use Tweakwise\Magento2Tweakwise\Block\LayeredNavigation\RenderLayered\TreeRenderer;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Filter\Item;

/**
 * Class ItemRenderer
 * @package Tweakwise\Magento2Tweakwise\Block\LayeredNavigation\RenderLayered\TreeRenderer
 */
class ItemRenderer extends TreeRenderer
{
    /**
     * {@inheritDoc}
     */
    protected $_template = 'Emico_Tweakwise::product/layered/tree/item.phtml';

    /**
     * @var Item
     */
    protected $item;

    /**
     * @return Item
     */
    public function getItem()
    {
        return $this->item;
    }

    /**
     * @param Item $item
     * @return $this
     */
    public function setItem(Item $item)
    {
        $this->item = $item;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasChildren()
    {
        return $this->item->hasChildren();
    }

    /**
     * @return Item[]
     */
    public function getChildren()
    {
        return $this->item->getChildren();
    }
}
