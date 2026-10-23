<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Tweakwise\Magento2Tweakwise\Model\Observer;

use Exception;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\Observer;
use Psr\Log\LoggerInterface;
use Tweakwise\Magento2Tweakwise\Exception\ApiException;
use Tweakwise\Magento2Tweakwise\Exception\TweakwiseExceptionInterface;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\NavigationContext;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\HTTP\PhpEnvironment\Response;

class CatalogLastPageRedirect implements ObserverInterface
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
     * @var LoggerInterface
     */
    private readonly LoggerInterface $logger;

    /**
     * CatalogSearchRedirect constructor.
     * @param Config $config
     * @param NavigationContext $context
     * @param Context $actionContext
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        Config $config,
        NavigationContext $context,
        Context $actionContext,
        ?LoggerInterface $logger = null
    ) {
        $this->config = $config;
        $this->context = $context;
        $this->actionContext = $actionContext;
        $this->logger = $logger ?? ObjectManager::getInstance()->get(LoggerInterface::class);
    }

    /**
     * @param Observer $observer
     * @return void
     * @throws Exception
     * phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter.FoundInImplementedInterface
     */
    public function execute(Observer $observer)
    {
        if ((int) $this->actionContext->getRequest()->getParam('p', 1) <= 1) {
            return;
        }

        $response = $this->getHttpResponse();
        if (!$response || $response->isRedirect()) {
            return;
        }

        if ($this->config->getTweakwiseExceptionThrown()) {
            //no api response
            return;
        }

        try {
            // @phpstan-ignore-next-line
            $properties = $this->context->getResponse()->getProperties();
        } catch (TweakwiseExceptionInterface $e) {
            $this->handleTweakwiseException($e);
            return;
        }

        $numberOfItems = $properties->getNumberOfItems();
        if ($numberOfItems === 0) {
            $lastPage = $this->getLastPageForEmptyResults();
            if ($lastPage === null) {
                return;
            }
        } else {
            $lastPage = $properties->getNumberOfPages();
        }

        $page = $properties->getCurrentPage();
        if ($page <= $lastPage) {
            return;
        }

        $url = $this->actionContext->getUrl()->getUrl(
            '*/*/*',
            [
            '_current' => true,
            '_use_rewrite' => true,
            '_query' => ['p' => $lastPage]
            ]
        );

        $response->setRedirect($url);
    }

    private function handleTweakwiseException(TweakwiseExceptionInterface $exception): void
    {
        if ($exception instanceof ApiException && $exception->getCode() === 404) {
            return;
        }

        $this->logger->critical($exception->getMessage());
        $this->config->setTweakwiseExceptionThrown();
    }

    /**
     * @return int|null
     */
    protected function getLastPageForEmptyResults(): ?int
    {
        return null;
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
