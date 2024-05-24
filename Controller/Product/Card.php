<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Controller\Product;

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
        $productId = $this->request->getParam('product_id');
        $itemHtml = $this->cacheHelper->load((int)$productId);

        $response = $this->httpFactory->create();
        $response->appendBody($itemHtml);
        $response->setPublicHeaders($this->config->getProductCardLifetime());
        return $response;
    }
}
