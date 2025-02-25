<?php

namespace Tweakwise\Magento2Tweakwise\Controller;

use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\RouteMatchingInterface;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\Strategy\PathSlugStrategy;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\Strategy\UrlStrategyFactory;
use Magento\Framework\App\Action\Forward;
use Magento\Framework\App\ActionFactory;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\RouterInterface;
use Magento\Framework\App\Request\Http as MagentoHttpRequest;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\UrlModel;
use Magento\Framework\App\ResponseFactory;

class Router implements RouterInterface
{
    /**
     * @var ActionFactory
     */
    protected $actionFactory;

    /**
     * @var RouteMatchingInterface
     */
    protected $routeMatchingStrategy;

    /**
     * @var UrlStrategyFactory
     */
    protected $urlStrategyFactory;

    /**
     * Router constructor.
     * @param ActionFactory $actionFactory
     * @param UrlStrategyFactory $urlStrategyFactory
     */
    public function __construct(ActionFactory $actionFactory, UrlStrategyFactory $urlStrategyFactory, private readonly UrlModel $magentoUrl, private responseFactory $responseFactory)
    {
        $this->actionFactory = $actionFactory;
        $this->urlStrategyFactory = $urlStrategyFactory;
    }

    /**
     * @return RouteMatchingInterface
     */
    protected function getRouteMatchingStrategy(): RouteMatchingInterface
    {
        if (!$this->routeMatchingStrategy) {
            $this->routeMatchingStrategy = $this->urlStrategyFactory->create(RouteMatchingInterface::class);
        }

        return $this->routeMatchingStrategy;
    }

    /**
     * Match application action by request
     *
     * @param RequestInterface $request
     * @return bool|ActionInterface
     */
    public function match(RequestInterface $request)
    {
        if (!$request instanceof MagentoHttpRequest) {
            return false;
        }

        $originalRequest = clone($request);
        $result = $this->getRouteMatchingStrategy()->match($request);

        if ($result === false) {
            return false;
        }

        if ($result instanceof ActionInterface) {
            return $result;
        }

        if ($this->getRouteMatchingStrategy() instanceof PathSlugStrategy) {
            $rewrite = $this->getRouteMatchingStrategy()->getRewrite($originalRequest);

            if ($rewrite->getRedirectType() === 301) {
                $url = $this->magentoUrl->getDirectUrl($rewrite->getTargetPath() . $request->getParam('filter_path'));
                $this->responseFactory->create()->setRedirect($url)->sendResponse();
                exit;
            }
        }

        return $this->actionFactory->create(Forward::class);
    }
}
