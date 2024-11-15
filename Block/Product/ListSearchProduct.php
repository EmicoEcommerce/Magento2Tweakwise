<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Block\Product;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Block\Product\Context;
use Tweakwise\Magento2Tweakwise\Block\Product\ListProduct;
use Magento\Catalog\Helper\Output as OutputHelper;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\Catalog\Model\Layer\Search as SearchLayer;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\Helper\PostHelper;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Url\Helper\Data;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2Tweakwise\Helper\Cache;

class ListSearchProduct extends ListProduct
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
        private readonly SearchLayer $searchLayer,
        array $data = [],
        ?OutputHelper $outputHelper = null
    ) {
        parent::__construct(
            $context,
            $postDataHelper,
            $layerResolver,
            $categoryRepository,
            $urlHelper,
            $tweakwiseConfig,
            $cookieManager,
            $cacheHelper,
            $registry,
            $request,
            $data,
            $outputHelper
        );
    }

    public function getLayer()
    {
        return $this->searchLayer;
    }
}
