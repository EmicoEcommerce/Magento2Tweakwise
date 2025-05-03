<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Helper;

use Magento\Customer\Model\Session;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\DesignInterface;
use Magento\PageCache\Model\Config;
use Magento\Store\Model\StoreManagerInterface;
use Tweakwise\Magento2Tweakwise\Model\Config as TweakwiseConfig;

class Cache
{
    public const PRODUCT_LIST_TTL = 0;
    public const PRODUCT_CARD_PATH = 'tweakwise/product/card';
    private const REDIS_CACHE_KEY = 'product_card';

    /**
     * @var bool|null
     */
    private ?bool $isHyvaTheme = null;

    /**
     * @param StoreManagerInterface $storeManager
     * @param CacheInterface $cache
     * @param Session $customerSession
     * @param RequestInterface $request
     * @param ScopeConfigInterface $scopeConfig
     * @param TweakwiseConfig $config
     * @param DesignInterface $viewDesign
     */
    public function __construct(
        private readonly StoreManagerInterface $storeManager,
        private readonly CacheInterface        $cache,
        private readonly Session               $customerSession,
        private readonly RequestInterface      $request,
        private readonly ScopeConfigInterface  $scopeConfig,
        private readonly TweakwiseConfig       $config,
        private readonly DesignInterface       $viewDesign
    ) {
    }

    /**
     * @param string $itemId
     * @param string $cardType
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function load(string $itemId, string $cardType = 'default'): string
    {
        $result = $this->cache->load($this->getCacheKey($itemId, $cardType));
        return $result ? $result : '';
    }

    /**
     * @param string $data
     * @param string $itemId
     * @param string $cardType
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function save(string $data, string $itemId, string $cardType = 'default'): void
    {
        $this->cache->save($data, $this->getCacheKey($itemId, $cardType));
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
     * @return bool
     */
    public function personalMerchandisingCanBeApplied(): bool
    {
        return $this->isVarnishEnabled() && $this->config->isPersonalMerchandisingActive();
    }

    /**
     * @return bool
     */
    public function shouldUseMerchandisingListing(): bool
    {
        return $this->personalMerchandisingCanBeApplied() || $this->config->isVisualsEnabled();
    }

    /**
     * @param RequestInterface $request
     * @return bool
     */
    public function isEsiRequest(RequestInterface $request): bool
    {
        return str_contains($request->getRequestUri(), 'page_cache/block/esi');
    }

    /**
     * @return bool
     */
    public function isHyvaTheme(): bool
    {
        if ($this->isHyvaTheme === null) {
            $theme = $this->viewDesign->getDesignTheme();
            while ($theme) {
                if (strpos($theme->getCode(), 'Hyva/') === 0) {
                    $this->isHyvaTheme = true;
                    return $this->isHyvaTheme;
                }

                $theme = $theme->getParentTheme();
            }

            $this->isHyvaTheme = false;
        }

        return $this->isHyvaTheme;
    }

    /**
     * @param string $itemId
     * @param string $cardType
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getCacheKey(string $itemId, string $cardType): string
    {
        $storeId = $this->storeManager->getStore()->getId();
        $customerGroupId = $this->customerSession->getCustomerGroupId();

        return sprintf(
            '%s_%s_%s_%s_%s',
            $storeId,
            $customerGroupId,
            $itemId,
            $cardType,
            self::REDIS_CACHE_KEY
        );
    }
}
