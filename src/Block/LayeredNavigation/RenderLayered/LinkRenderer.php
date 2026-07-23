<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Block\LayeredNavigation\RenderLayered;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Escaper;
use Magento\Framework\Exception\NoSuchEntityException;
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

    private ?StrategyHelper $strategyHelper = null;

    /**
     * @param Context $context
     * @param Config $config
     * @param NavigationConfig $navigationConfig
     * @param FilterHelper $filterHelper
     * @param Json $jsonSerializer
     * @param Helper $helper
     * @param Escaper $escaper
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

        try {
            $this->getStrategyHelper()->warmUp($items, (int) $this->filter->getStoreId());
        } catch (NoSuchEntityException) {
            // Ignore and let per-item fallback resolve categories without store context.
        }

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

    private function getStrategyHelper(): StrategyHelper
    {
        if ($this->strategyHelper !== null) {
            return $this->strategyHelper;
        }

        $this->strategyHelper = ObjectManager::getInstance()->get(StrategyHelper::class);

        return $this->strategyHelper;
    }
}
