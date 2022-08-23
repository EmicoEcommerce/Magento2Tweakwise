<?php
/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2Tweakwise\Block\Navigation\FilterRenderer;

use Closure;
use Tweakwise\Magento2Tweakwise\Block\LayeredNavigation\RenderLayered\DefaultRenderer;
use Tweakwise\Magento2Tweakwise\Block\LayeredNavigation\RenderLayered\LinkRenderer;
use Tweakwise\Magento2Tweakwise\Block\LayeredNavigation\RenderLayered\SwatchRenderer;
use Tweakwise\Magento2Tweakwise\Block\LayeredNavigation\RenderLayered\SliderRenderer;
use Tweakwise\Magento2Tweakwise\Block\LayeredNavigation\RenderLayered\TreeRenderer;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Filter;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\FacetType\SettingsType;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2TweakwiseExport\Model\Logger;
use Magento\Catalog\Model\Layer\Filter\FilterInterface;
use Magento\Framework\View\LayoutInterface;
use Magento\LayeredNavigation\Block\Navigation\FilterRenderer;
use Magento\Swatches\Block\LayeredNavigation\RenderLayered;

class Plugin
{
    /**
     * @var LayoutInterface
     */
    protected $layout;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Logger
     */
    protected $log;

    /**
     * Filter renderer block types
     *
     * @var string[]
     */
    protected $blockTypes = [
        SettingsType::SELECTION_TYPE_TREE => TreeRenderer::class,
        SettingsType::SELECTION_TYPE_LINK => LinkRenderer::class,
        SettingsType::SELECTION_TYPE_SLIDER => SliderRenderer::class,
        SettingsType::SELECTION_TYPE_COLOR => SwatchRenderer::class,
    ];

    /**
     * @var string[]
     */
    protected $defaultAllowedRenderTypes = [
        SettingsType::SELECTION_TYPE_LINK,
        SettingsType::SELECTION_TYPE_CHECKBOX,
        SettingsType::SELECTION_TYPE_COLOR,
    ];

    /**
     * @var SwatchHelper
     */
    protected $swatchHelper;

    /**
     * @param Logger $log
     * @param LayoutInterface $layout
     * @param Config $config
     */
    public function __construct(Logger $log, LayoutInterface $layout, Config $config)
    {
        $this->layout = $layout;
        $this->config = $config;
        $this->log = $log;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @param FilterRenderer $subject
     * @param Closure $proceed
     * @param FilterInterface $filter
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function aroundRender(FilterRenderer $subject, Closure $proceed, FilterInterface $filter)
    {
        if (!$filter instanceof Filter) {
            return $proceed($filter);
        }

        if (!$this->config->isLayeredEnabled()) {
            return $proceed($filter);
        }

        $facet = $filter->getFacet();
        $settings = $facet->getFacetSettings();
        $renderType = $settings->getSelectionType();

        if ($this->config->getUseDefaultLinkRenderer() && in_array($renderType, $this->defaultAllowedRenderTypes)) {
            return $proceed($filter);
        }

        $blockType = $this->getBlockType($settings);
        $block = $this->layout->createBlock($blockType);

        if (!$block instanceof DefaultRenderer && !$block instanceof RenderLayered) {
            $this->log->error(sprintf('Invalid renderer block type %s not instanceof %s', $blockType, DefaultRenderer::class));
            return $proceed($filter);
        }

        $block->setFilter($filter);
        return $block->toHtml();
    }

    /**
     * @param SettingsType $settings
     * @return string
     */
    protected function getBlockType(SettingsType $settings)
    {
        $renderType = $settings->getSelectionType();
        return $this->blockTypes[$renderType] ?? DefaultRenderer::class;
    }
}
