<?php

/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Tweakwise\Magento2Tweakwise\Model\Observer;

use Tweakwise\Magento2Tweakwise\Exception\ApiException;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\NavigationContext;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\HTTP\PhpEnvironment\Response;
use Magento\Search\Model\QueryFactory;

class CatalogSearchRedirect implements ObserverInterface
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var NavigationContext
     */
    protected $context;

    /**
     * @var Context
     */
    protected $actionContext;

    /**
     * CatalogSearchRedirect constructor.
     * @param Config $config
     * @param NavigationContext $context
     * @param Context $actionContext
     */
    public function __construct(Config $config, NavigationContext $context, Context $actionContext)
    {
        $this->config = $config;
        $this->context = $context;
        $this->actionContext = $actionContext;
    }

    /**
     * @param Observer $observer
     * @return void
     * @throws \Exception
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute(Observer $observer)
    {
        if (!$this->config->isSearchEnabled()) {
            return;
        }

        if (!$this->actionContext->getRequest()->getParam(QueryFactory::QUERY_VAR_NAME)) {
            return;
        }

        if ($this->config->getTweakwiseExceptionTrown()) {
            //no api response
            return;
        }

        try {
            $redirects = $this->context->getResponse()->getRedirects();
        } catch (ApiException $e) {
            //no api response
            return;
        }

        if (!$redirects) {
            return;
        }

        $redirect = current($redirects);
        $url = $redirect->getUrl();

        $isDirectUrl = strpos($url, 'http') === 0;
        $isProtocolRelative = strpos($url, '//') === 0;
        $isDomainRelative = !$isProtocolRelative && strpos($url, '/') === 0;

        if (!$isDirectUrl && !$isProtocolRelative && !$isDomainRelative) {
            // In this case we just assume it should be a relative url
            $url = '/' . $url;
        }

        $response = $this->getHttpResponse();
        if (!$response) {
            return;
        }

        $response->setRedirect($url);
        /** @var Action $controller */
        $controller = $observer->getData('controller_action');
        $controller->getActionFlag()->set('', Action::FLAG_NO_DISPATCH, 1);
    }

    /**
     * @return Response|null
     */
    protected function getHttpResponse()
    {
        $response = $this->actionContext->getResponse();
        if (!$response instanceof Response) {
            return null;
        }

        return $response;
    }
}
