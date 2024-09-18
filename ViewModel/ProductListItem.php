<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\ViewModel;

use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\View\LayoutInterface;
use Tweakwise\Magento2Tweakwise\Helper\Cache;
use Magento\Store\Model\StoreManagerInterface;

class ProductListItem implements ArgumentInterface
{
    /**
     * @param LayoutInterface $layout
     * @param Cache $cacheHelper
     */
    public function __construct(
        private readonly LayoutInterface $layout,
        private readonly Cache $cacheHelper,
        private readonly StoreManagerInterface $storeManager
    ) {
    }

    /**
     * @param Product $product
     * @param AbstractBlock $parentBlock
     * @param string $viewMode
     * @param string $templateType
     * @param string $imageDisplayArea
     * @param bool $showDescription
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getItemHtml(
        Product $product,
        AbstractBlock $parentBlock,
        string $viewMode,
        string $templateType,
        string $imageDisplayArea,
        bool $showDescription
    ): string {
        if (
            !$this->cacheHelper->personalMerchandisingCanBeApplied() ||
            $this->cacheHelper->isTweakwiseAjaxRequest()
        ) {
            return $this->getItemHtmlWithRenderer(
                $product,
                $parentBlock,
                $viewMode,
                $templateType,
                $imageDisplayArea,
                $showDescription
            );
        }

        $productId = (int) $product->getId();
        $storeId = $this->storeManager->getStore()->getId();
        if (!$this->cacheHelper->load($productId)) {
            $itemHtml = $this->getItemHtmlWithRenderer(
                $product,
                $parentBlock,
                $viewMode,
                $templateType,
                $imageDisplayArea,
                $showDescription
            );
            $this->cacheHelper->save($itemHtml, $productId);
        }

        return sprintf('<esi:include src="/%s?product_id=%s&store_id=%s" />',
            Cache::PRODUCT_CARD_PATH, $productId, $storeId);
    }

    /**
     * @param Product $product
     * @param AbstractBlock $parentBlock
     * @param string $viewMode
     * @param string $templateType
     * @param string $imageDisplayArea
     * @param bool $showDescription
     * @return string
     */
    private function getItemHtmlWithRenderer(
        Product $product,
        AbstractBlock $parentBlock,
        string $viewMode,
        string $templateType,
        string $imageDisplayArea,
        bool $showDescription
    ): string {
        /** @var AbstractBlock $itemRendererBlock */
        $itemRendererBlock = $this->layout->getBlock('tweakwise.catalog.product.list.item');

        if (! $itemRendererBlock) {
            return '';
        }

        $detailsRenderers = $parentBlock->getChildBlock('details.renderers');
        if ($detailsRenderers) {
            $itemRendererBlock->setChild('details.renderers', $detailsRenderers);
        }

        $itemRendererBlock
            ->setData('product', $product)
            ->setData('parent_block', $parentBlock)
            ->setData('view_mode', $viewMode)
            ->setData('image_display_area', $imageDisplayArea)
            ->setData('show_description', $showDescription)
            ->setData('pos', $parentBlock->getPositioned())
            ->setData('output_helper', $parentBlock->getData('outputHelper'))
            ->setData('template_type', $templateType);

        return $itemRendererBlock->toHtml();
    }
}
