<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Block\LayeredNavigation\RenderLayered;

use Magento\Framework\Escaper;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Serialize\Serializer\Json;
use Tweakwise\Magento2Tweakwise\Block\LayeredNavigation\RenderLayered\LinkRenderer\ItemRenderer;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Filter\Item;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\StrategyHelper;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2Tweakwise\Model\NavigationConfig;
use Tweakwise\Magento2Tweakwise\Model\Seo\FilterHelper;
use Tweakwise\Magento2TweakwiseExport\Model\Helper;

class LinkRenderer extends DefaultRenderer
{
    /**
     * @var string
     */
    protected $_template = 'Tweakwise_Magento2Tweakwise::product/layered/link.phtml';

    /**
     * @param Context $context
     * @param Config $config
     * @param NavigationConfig $navigationConfig
     * @param FilterHelper $filterHelper
     * @param Json $jsonSerializer
     * @param Helper $helper
     * @param Escaper $escaper
     * @param StrategyHelper $strategyHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Config $config,
        NavigationConfig $navigationConfig,
        FilterHelper $filterHelper,
        Json $jsonSerializer,
        Helper $helper,
        Escaper $escaper,
        private readonly StrategyHelper $strategyHelper,
        array $data = []
    ) {
        parent::__construct($context, $config, $navigationConfig, $filterHelper, $jsonSerializer, $helper, $escaper, $data);
    }

    /**
     * Returns filter items and pre-warms the category + URL-rewrite caches so that
     * all subsequent per-item calls to getCategoryFromItem() and Category::getUrl()
     * are served from memory instead of issuing individual DB queries.
     *
     * @return Item[]
     */
    public function getItems()
    {
        $items = parent::getItems();

        $this->strategyHelper->warmUp($items, (int) $this->filter->getStoreId());

        return $items;
    }

    /**
     * @param Item $item
     * @return string
     */
    public function renderLinkItem(Item $item)
    {
        /** @var ItemRenderer $block */
        $block = $this->getLayout()->createBlock(ItemRenderer::class);
        $block->setFilter($this->filter);
        $block->setItem($item);
        return $block->toHtml();
    }
}
