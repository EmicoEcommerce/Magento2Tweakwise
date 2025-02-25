<?php

namespace Tweakwise\Magento2Tweakwise\Controller;

use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\RouteMatchingInterface;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\Strategy\UrlStrategyFactory;
use Magento\Framework\App\Action\Forward;
use Magento\Framework\App\ActionFactory;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\RouterInterface;
use Magento\Framework\App\Request\Http as MagentoHttpRequest;
use Magento\Framework\App\Action\Redirect;

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
    public function __construct(ActionFactory $actionFactory, UrlStrategyFactory $urlStrategyFactory)
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

        $result = $this->getRouteMatchingStrategy()->match($request);

        if ($result === false) {
            return false;
        }

        if ($result instanceof ActionInterface) {
            return $result;
        }

        $url = $request->getParam('redirect');

        if($url) {
            $url = $request->getParam('redirect');
            $redirect = $this->actionFactory->create(Redirect::class);
            $redirect->getResponse()->setRedirect($request->getParam('redirect'));
            return $redirect;
        }

        return $this->actionFactory->create(Forward::class);
    }
}
