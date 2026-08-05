<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Model\Analytics;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;

class CurrentProductResolver
{
    private ?int $productId = null;
    private ProductInterface|false|null $product = null;

    public function __construct(
        private readonly RequestInterface $request,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly StoreManagerInterface $storeManager,
    ) {
    }

    public function getProductId(): int
    {
        if ($this->productId === null) {
            $this->productId = $this->resolveProductId();
        }

        return $this->productId;
    }

    public function getProduct(): ?ProductInterface
    {
        if ($this->product === null) {
            $this->product = $this->resolveProduct();
        }

        return $this->product ?: null;
    }

    private function resolveProductId(): int
    {
        $productId = (int)$this->request->getParam('id');

        if ($this->request->getActionName() === 'configure' || !$productId) {
            $productId = (int)$this->request->getParam('product_id');
        }

        return $productId;
    }

    private function resolveProduct(): ProductInterface|false
    {
        $productId = $this->getProductId();
        if (!$productId) {
            return false;
        }

        try {
            return $this->productRepository->getById(
                $productId,
                false,
                (int)$this->storeManager->getStore()->getId()
            );
        } catch (NoSuchEntityException) {
            return false;
        }
    }
}
