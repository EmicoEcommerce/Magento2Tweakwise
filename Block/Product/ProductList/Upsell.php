<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Block\Product\ProductList;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Block\Product\ProductList\Upsell as MagentoUpsell;
use Magento\Catalog\Model\Product\Visibility as ProductVisibility;
use Magento\Checkout\Model\ResourceModel\Cart as CartResourceModel;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Module\Manager;
use Magento\Framework\Registry;
use Tweakwise\Magento2Tweakwise\Helper\Cache;

class Upsell extends MagentoUpsell
{
    /**
     * @param Context $context
     * @param CartResourceModel $checkoutCart
     * @param ProductVisibility $catalogProductVisibility
     * @param CheckoutSession $checkoutSession
     * @param Manager $moduleManager
     * @param Cache $cacheHelper
     * @param RequestInterface $request
     * @param ProductRepositoryInterface $productRepository
     * @param Registry $registry
     * @param array $data
     */
    public function __construct(
        Context $context,
        CartResourceModel $checkoutCart,
        ProductVisibility $catalogProductVisibility,
        CheckoutSession $checkoutSession,
        Manager $moduleManager,
        private readonly Cache $cacheHelper,
        private readonly RequestInterface $request,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly Registry $registry,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $checkoutCart,
            $catalogProductVisibility,
            $checkoutSession,
            $moduleManager,
            $data
        );
    }

    /**
     * @return int|bool|null
     */
    protected function getCacheLifetime()
    {
        if (!$this->cacheHelper->personalMerchandisingCanBeApplied()) {
            return parent::getCacheLifetime();
        }

        $this->setData('ttl', Cache::PRODUCT_LIST_TTL);
        $this->setData('cache_lifetime', Cache::PRODUCT_LIST_TTL);
        return $this->getData('cache_lifetime');
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        if (!$this->cacheHelper->personalMerchandisingCanBeApplied()) {
            return parent::getTemplate();
        }

        return 'Tweakwise_Magento2Tweakwise::product/list/items.phtml';
    }

    /**
     * @return Upsell
     * @throws NoSuchEntityException
     */
    protected function _prepareData()
    {
        if ($this->cacheHelper->isEsiRequest($this->request) && !$this->getProduct()) {
            $productId = $this->request->getParam('product_id');
            $product = $this->productRepository->getById($productId);
            $this->registry->register('product', $product);
            $this->setData('product', $product);
        }

        return parent::_prepareData();
    }

    /**
     * @param string $route
     * @param array $params
     * @return string
     */
    public function getUrl($route = '', $params = [])
    {
        if (
            !$this->cacheHelper->personalMerchandisingCanBeApplied() ||
            $route !== 'page_cache/block/esi'
        ) {
            return parent::getUrl($route, $params);
        }

        $params['_query'] = [
            'product_id' => $this->getProduct()->getId()
        ];

        return parent::getUrl($route, $params);
    }
}
