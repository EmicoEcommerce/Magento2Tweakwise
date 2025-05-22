<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\ViewModel;

use Magento\Catalog\Model\Product;
use Magento\Customer\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\View\LayoutInterface;
use Tweakwise\Magento2Tweakwise\Helper\Cache;
use Tweakwise\Magento2Tweakwise\Model\Visual;
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
        private readonly StoreManagerInterface $storeManager,
        private readonly Session $customerSession
    ) {
    }

    /**
     * @param Product|Visual $item
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
        Product|Visual $item,
        AbstractBlock $parentBlock,
        string $viewMode,
        string $templateType,
        string $imageDisplayArea,
        bool $showDescription
    ): string {
        $isVisual = $item instanceof Visual;
        if (
            !$this->cacheHelper->personalMerchandisingCanBeApplied() ||
            $this->cacheHelper->isTweakwiseAjaxRequest()
        ) {
            if ($isVisual) {
                return $this->getVisualHtml($item);
            }

            return $this->getItemHtmlWithRenderer(
                $item,
                $parentBlock,
                $viewMode,
                $templateType,
                $imageDisplayArea,
                $showDescription
            );
        }

        $itemId = (string)$item->getId();
        $storeId = (int)$this->storeManager->getStore()->getId();
        $customerGroupId = (int)$this->customerSession->getCustomerGroupId();
        $hashedCacheKeyInfo = $this->cacheHelper->hashCacheKeyInfo(
            $itemId,
            $storeId,
            $customerGroupId,
            $this->cacheHelper->getImage($item)
        );

        if (!$this->cacheHelper->load($hashedCacheKeyInfo)) {
            if ($isVisual) {
                $itemHtml = $this->getVisualHtml($item);
                $this->cacheHelper->save($itemHtml, $hashedCacheKeyInfo);
            } else {
                $itemHtml = $this->getItemHtmlWithRenderer(
                    $item,
                    $parentBlock,
                    $viewMode,
                    $templateType,
                    $imageDisplayArea,
                    $showDescription
                );
                $this->cacheHelper->save(
                    $itemHtml,
                    $hashedCacheKeyInfo,
                    [Product::CACHE_TAG, sprintf('%s_%s', Product::CACHE_TAG, $itemId)]
                );
            }
        }

        return sprintf(
            '<esi:include src="/%s?item_id=%s&cache_key_info=%s" />',
            Cache::PRODUCT_CARD_PATH,
            $itemId,
            $hashedCacheKeyInfo
        );
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

    /**
     * @param Visual $visual
     * @return string
     */
    private function getVisualHtml(Visual $visual): string
    {
        /** @var AbstractBlock $visualRendererBlock */
        $visualRendererBlock = $this->layout->getBlock('tweakwise.catalog.product.list.visual');

        if (! $visualRendererBlock) {
            return '';
        }

        $visualRendererBlock->setData('visual', $visual);

        return $visualRendererBlock->toHtml();
    }
}
