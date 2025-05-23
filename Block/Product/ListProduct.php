<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Block\Product;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Block\Product\ListProduct as MagentoListProduct;
use Magento\Catalog\Helper\Output as OutputHelper;
use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Helper\PostHelper;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Url\Helper\Data;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2Tweakwise\Helper\Cache;

class ListProduct extends MagentoListProduct
{
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
     * @param RequestInterface $request
     * @param array $data
     * @param OutputHelper|null $outputHelper
     * @param Layer|null $catalogLayer
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
        private readonly RequestInterface $request,
        array $data = [],
        ?OutputHelper $outputHelper = null,
        ?Layer $catalogLayer = null
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
        if ($catalogLayer) {
            $this->_catalogLayer = $catalogLayer;
        }
    }

    /**
     * @return int|bool|null
     */
    protected function getCacheLifetime()
    {
        if (
            !$this->cacheHelper->personalMerchandisingCanBeApplied() ||
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
            !$this->cacheHelper->personalMerchandisingCanBeApplied() ||
            $route !== 'page_cache/block/esi'
        ) {
            return parent::getUrl($route, $params);
        }

        $queryParams = [];
        $profileKey = $this->getProfileKey();
        if ($profileKey) {
            $queryParams['tn_pk'] = $profileKey;
        }

        $category = $this->registry->registry('current_category');
        if ($category) {
            $queryParams['cc_id'] = $category->getId();
        }

        $queryParams = array_merge($this->request->getParams(), $queryParams);
        $params['_query'] = isset($params['_query']) ? array_merge($params['_query'], $queryParams) : $queryParams;

        return parent::getUrl($route, $params);
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        if (
            !$this->cacheHelper->shouldUseMerchandisingListing() ||
            $this->cacheHelper->isHyvaTheme()
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
}
