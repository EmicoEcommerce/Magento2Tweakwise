<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Controller\Product;

use Magento\Catalog\Model\Product;
use Magento\PageCache\Model\Cache\Type as PageType;
use Tweakwise\Magento2Tweakwise\Helper\Cache;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\HttpFactory;
use Magento\Framework\App\Response\HttpInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Tweakwise\Magento2Tweakwise\Model\Config;

class Card implements HttpGetActionInterface
{
    /**
     * @param Cache $cacheHelper
     * @param HttpFactory $httpFactory
     * @param RequestInterface $request
     * @param Config $config
     */
    public function __construct(
        private readonly Cache $cacheHelper,
        private readonly HttpFactory $httpFactory,
        private readonly RequestInterface $request,
        private readonly Config $config
    ) {
    }

    /**
     * @return HttpInterface
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function execute(): HttpInterface
    {
        $itemId = (string)$this->request->getParam('item_id');
        $cacheKeyInfo = (string)$this->request->getParam('cache_key_info');
        $itemHtml = $this->cacheHelper->load($cacheKeyInfo);

        $response = $this->httpFactory->create();
        $response->appendBody($itemHtml);

        $response->setHeader(
            'X-Magento-Tags',
            implode(
                ',',
                [
                    Product::CACHE_TAG,
                    sprintf('%s_%s', Product::CACHE_TAG, $itemId),
                    PageType::CACHE_TAG
                ]
            )
        );
        $response->setPublicHeaders($this->config->getProductCardLifetime());
        return $response;
    }
}
