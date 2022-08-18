<?php
/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2Tweakwise\Block\LayeredNavigation\RenderLayered;

use Tweakwise\Magento2Tweakwise\Block\LayeredNavigation\RenderLayered\TreeRenderer\ItemRenderer;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Filter\Item;

class TreeRenderer extends DefaultRenderer
{
    /**
     * {@inheritDoc}
     */
    protected $_template = 'Tweakwise_Magento2Tweakwise::product/layered/tree.phtml';

    /**
     * @param Item $item
     * @return string
     */
    public function renderTreeItem(Item $item)
    {
        /** @var ItemRenderer $block */
        $block = $this->getLayout()->createBlock(ItemRenderer::class);
        $block->setFilter($this->filter);
        $block->setItem($item);
        return $block->toHtml();
    }
}
