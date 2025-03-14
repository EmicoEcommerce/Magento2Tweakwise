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
use Magento\Store\Model\StoreManagerInterface;

class LinkedProductListItem implements ArgumentInterface
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
     * @param Product $product
     * @param AbstractBlock $parentBlock
     * @param array $params
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getItemHtml(
        Product $product,
        AbstractBlock $parentBlock,
        array $params
    ): string {
        if (
            !$this->cacheHelper->personalMerchandisingCanBeApplied() ||
            $this->cacheHelper->isTweakwiseAjaxRequest()
        ) {
            return $this->getItemHtmlWithRenderer(
                $product,
                $parentBlock,
                $params
            );
        }

        $productId = (int)$product->getId();
        $storeId = (int)$this->storeManager->getStore()->getId();
        $customerGroupId = (int)$this->customerSession->getCustomerGroupId();
        $cardType = str_replace(' ', '_', $params['card_type']);
        $hashedCacheKeyInfo = $this->cacheHelper->hashCacheKeyInfo(
            $productId,
            $storeId,
            $customerGroupId,
            $this->cacheHelper->getImage($product),
            $cardType
        );

        if (!$this->cacheHelper->load($hashedCacheKeyInfo)) {
            $itemHtml = $this->getItemHtmlWithRenderer(
                $product,
                $parentBlock,
                $params
            );
            $this->cacheHelper->save($itemHtml, $hashedCacheKeyInfo);
        }

        return sprintf(
            '<esi:include src="/%s?cache_key_info=%s" />',
            Cache::PRODUCT_CARD_PATH,
            $hashedCacheKeyInfo
        );
    }

    /**
     * @param Product $product
     * @param AbstractBlock $parentBlock
     * @param array $params
     * @return string
     */
    private function getItemHtmlWithRenderer(
        Product $product,
        AbstractBlock $parentBlock,
        array $params
    ): string {
        /** @var AbstractBlock $itemRendererBlock */
        $itemRendererBlock = $this->layout->getBlock('tweakwise.catalog.linked.product.list.item');

        if (! $itemRendererBlock) {
            return '';
        }

        $itemRendererBlock
            ->setData('item', $product)
            ->setData('parent_block', $parentBlock)
            ->setData('type', $params['card_type'])
            ->setData('image', $params['image'])
            ->setData('template_type', $params['template_type'])
            ->setData('can_items_add_to_cart', $params['can_items_add_to_cart'])
            ->setData('show_add_to', $params['show_add_to'])
            ->setData('show_cart', $params['show_cart']);

        return $itemRendererBlock->toHtml();
    }
}
