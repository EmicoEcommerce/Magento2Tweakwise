<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Helper;

use Magento\Customer\Model\Session;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\PageCache\Model\Config;
use Magento\Store\Model\StoreManagerInterface;

class Cache
{
    public const PRODUCT_LIST_TTL = 0;
    public const PRODUCT_CARD_PATH = 'tweakwise/product/card';
    private const REDIS_CACHE_KEY = 'product_card';

    /**
     * @param StoreManagerInterface $storeManager
     * @param CacheInterface $cache
     * @param Session $customerSession
     * @param RequestInterface $request
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly StoreManagerInterface $storeManager,
        private readonly CacheInterface $cache,
        private readonly Session $customerSession,
        private readonly RequestInterface $request,
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * @param int $productId
     * @return string
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function load(int $productId): string
    {
        $result = $this->cache->load($this->getCacheKey($productId));
        return $result ? $result : '';
    }

    /**
     * @param string $data
     * @param int $productId
     * @return void
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function save(string $data, int $productId): void
    {
        $this->cache->save($data, $this->getCacheKey($productId));
    }

    /**
     * @return bool
     */
    public function isTweakwiseAjaxRequest(): bool
    {
        return $this->request->getPathInfo() === '/tweakwise/ajax/navigation/';
    }

    /**
     * @return bool
     */
    public function isVarnishEnabled(): bool
    {
        return $this->scopeConfig->getValue(Config::XML_PAGECACHE_TYPE) === (string) Config::VARNISH;
    }

    /**
     * @param int $productId
     * @return string
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    private function getCacheKey(int $productId): string
    {
        $storeId = $this->storeManager->getStore()->getId();
        $customerGroupId = $this->customerSession->getCustomerGroupId();

        return sprintf('%s_%s_%s_%s', $storeId, $customerGroupId, $productId, self::REDIS_CACHE_KEY);
    }
}
