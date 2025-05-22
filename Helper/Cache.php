<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Helper;

use Magento\Catalog\Model\Product;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\DesignInterface;
use Magento\PageCache\Model\Config;
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
     * @param CacheInterface $cache
     * @param RequestInterface $request
     * @param ScopeConfigInterface $scopeConfig
     * @param TweakwiseConfig $config
     * @param DesignInterface $viewDesign
     * @param Json $jsonSerializer
     */
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly RequestInterface $request,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly TweakwiseConfig $config,
        private readonly DesignInterface $viewDesign,
        private readonly Json $jsonSerializer
    ) {
    }

    /**
     * @param string $hashedCacheKeyInfo
     * @return string
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function load(string $hashedCacheKeyInfo): string
    {
        $result = $this->cache->load($this->getCacheKey($hashedCacheKeyInfo));
        return $result ? $result : '';
    }

    /**
     * @param string $data
     * @param string $hashedCacheKeyInfo
     * @param array $tags
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function save(string $data, string $hashedCacheKeyInfo, array $tags = []): void
    {
        $this->cache->save(
            $data,
            $this->getCacheKey($hashedCacheKeyInfo),
            $tags,
            $this->config->getProductCardLifetime()
        );
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
     * @param Product $item
     * @return string
     * @throws LocalizedException
     */
    public function getImage(Product $item): string
    {
        if (!$this->config->isGroupedProductsEnabled() || $item->getTypeId() !== Configurable::TYPE_CODE) {
            return '';
        }

        return $item->getImage();
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
     * @param int $storeId
     * @param int $customerGroupId
     * @param string $image
     * @param string $cardType
     * @return string
     */
    public function hashCacheKeyInfo(
        string $itemId,
        int $storeId,
        int $customerGroupId,
        string $image = '',
        string $cardType = 'default',
    ): string {
        return sha1($this->jsonSerializer->serialize([$itemId, $storeId, $customerGroupId, $image, $cardType]));
    }

    /**
     * @param string $hashedCacheKeyInfo
     * @return string
     */
    private function getCacheKey(string $hashedCacheKeyInfo): string
    {
        return sprintf(
            '%s_%s',
            self::REDIS_CACHE_KEY,
            $hashedCacheKeyInfo
        );
    }
}
