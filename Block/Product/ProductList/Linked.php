<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Block\Product\ProductList;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Block\Product\AbstractProduct;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Tweakwise\Magento2Tweakwise\Helper\Cache;

class Linked
{
    /**
     * @param Cache $cacheHelper
     * @param RequestInterface $request
     * @param ProductRepositoryInterface $productRepository
     * @param Registry $registry
     */
    public function __construct(
        private readonly Cache $cacheHelper,
        private readonly RequestInterface $request,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly Registry $registry,
    ) {
    }

    /**
     * @param AbstractProduct $productBlock
     * @return int|null
     */
    public function getCacheLifetime(AbstractProduct $productBlock): ?int
    {
        if (!$this->cacheHelper->personalMerchandisingCanBeApplied()) {
            return null;
        }

        $productBlock->setData('ttl', Cache::PRODUCT_LIST_TTL);
        $productBlock->setData('cache_lifetime', Cache::PRODUCT_LIST_TTL);
        return $productBlock->getData('cache_lifetime');
    }

    /**
     * @return string|null
     */
    public function getTemplate(): ?string
    {
        if (!$this->cacheHelper->personalMerchandisingCanBeApplied()) {
            return null;
        }

        return 'Tweakwise_Magento2Tweakwise::product/list/items.phtml';
    }

    /**
     * @param AbstractProduct $productBlock
     * @throws NoSuchEntityException
     */
    public function prepareData(AbstractProduct $productBlock): void
    {
        if ($this->cacheHelper->isEsiRequest($this->request) && !$productBlock->getProduct()) {
            $productId = $this->request->getParam('product_id');
            $product = $this->productRepository->getById($productId);
            $this->registry->register('product', $product);
            $productBlock->setData('product', $product);
        }
    }

    /**
     * @param AbstractProduct $productBlock
     * @param string $route
     * @param array $params
     * @return array|null
     */
    public function getUrl(AbstractProduct $productBlock, string $route = '', array $params = []): ?array
    {
        if (
            !$this->cacheHelper->personalMerchandisingCanBeApplied() ||
            $route !== 'page_cache/block/esi'
        ) {
            return null;
        }

        $params['_query'] = [
            'product_id' => $productBlock->getProduct()->getId()
        ];

        return $params;
    }
}
