<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Block\Product;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Block\Product\ListProduct as MagentoListProduct;
use Magento\Catalog\Helper\Output as OutputHelper;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\Framework\Data\Helper\PostHelper;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Url\Helper\Data;
use Magento\Framework\View\DesignInterface;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2Tweakwise\Helper\Cache;

class ListProduct extends MagentoListProduct
{
    /**
     * @var bool|null
     */
    private ?bool $isHyvaTheme = null;

    /**
     * @param Context $context
     * @param PostHelper $postDataHelper
     * @param Resolver $layerResolver
     * @param CategoryRepositoryInterface $categoryRepository
     * @param Data $urlHelper
     * @param Config $tweakwiseConfig
     * @param CookieManagerInterface $cookieManager
     * @param Cache $cacheHelper
     * @param Registry $registry
     * @param DesignInterface $viewDesign
     * @param array $data
     * @param OutputHelper|null $outputHelper
     */
    public function __construct(
        Context $context,
        PostHelper $postDataHelper,
        Resolver $layerResolver,
        CategoryRepositoryInterface $categoryRepository,
        Data $urlHelper,
        private readonly Config $tweakwiseConfig,
        private readonly CookieManagerInterface $cookieManager,
        private readonly Cache $cacheHelper,
        private readonly Registry $registry,
        private readonly DesignInterface $viewDesign,
        array $data = [],
        ?OutputHelper $outputHelper = null
    ) {
        parent::__construct(
            $context,
            $postDataHelper,
            $layerResolver,
            $categoryRepository,
            $urlHelper,
            $data,
            $outputHelper
        );
    }

    /**
     * @return int|bool|null
     */
    protected function getCacheLifetime()
    {
        if (
            !$this->cacheHelper->isVarnishEnabled() ||
            !$this->tweakwiseConfig->isPersonalMerchandisingActive() ||
            $this->cacheHelper->isTweakwiseAjaxRequest()
        ) {
            return parent::getCacheLifetime();
        }

        $this->setData('ttl', Cache::PRODUCT_LIST_TTL);
        $this->setData('cache_lifetime', Cache::PRODUCT_LIST_TTL);
        return $this->getData('cache_lifetime');
    }

    /**
     * @param string $route
     * @param array $params
     * @return string
     */
    public function getUrl($route = '', $params = [])
    {
        if (
            !$this->cacheHelper->isVarnishEnabled() ||
            !$this->tweakwiseConfig->isPersonalMerchandisingActive() ||
            $route !== 'page_cache/block/esi'
        ) {
            return parent::getUrl($route, $params);
        }

        $additionalParams = [];
        $profileKey = $this->getProfileKey();
        if ($profileKey) {
            $additionalParams['tn_pk'] = $profileKey;
        }

        $category = $this->registry->registry('current_category');
        if ($category) {
            $additionalParams['cc_id'] = $category->getId();
        }

        $params = array_merge($additionalParams, $params);

        return parent::getUrl($route, $params);
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        if (
            !$this->cacheHelper->isVarnishEnabled() ||
            !$this->tweakwiseConfig->isPersonalMerchandisingActive() ||
            $this->isHyvaTheme()
        ) {
            return parent::getTemplate();
        }

        return 'Tweakwise_Magento2Tweakwise::product/list.phtml';
    }

    /**
     * @return string|null
     */
    private function getProfileKey(): ?string
    {
        return $this->cookieManager->getCookie(
            $this->tweakwiseConfig->getPersonalMerchandisingCookieName(),
            null
        );
    }

    /**
     * @return bool
     */
    private function isHyvaTheme(): bool
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
}
