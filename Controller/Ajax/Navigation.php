<?php

namespace Tweakwise\Magento2Tweakwise\Controller\Ajax;

use InvalidArgumentException;
use Tweakwise\Magento2Tweakwise\Model\AjaxNavigationResult;
use Tweakwise\Magento2Tweakwise\Model\AjaxResultInitializer\InitializerInterface;
use Tweakwise\Magento2Tweakwise\Model\Client\Timer;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NotFoundException;
use Tweakwise\Magento2Tweakwise\Model\FilterFormInputProvider\HashInputProvider;

/**
 * Class Navigation
 * Handles ajax filtering requests for category pages
 */
class Navigation extends Action
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var AjaxNavigationResult
     */
    protected $ajaxNavigationResult;

    /**
     * @var InitializerInterface[]
     */
    protected $initializerMap;

    /**
     * @var HashInputProvider
     */
    protected $hashInputProvider;

    /**
     * @var Timer
     */
    protected $timer;

    /**
     * Navigation constructor.
     * @param Context $context
     * @param Config $config
     * @param AjaxNavigationResult $ajaxNavigationResult
     * @param HashInputProvider $hashInputProvider
     * @param Timer $timer
     * @param array $initializerMap
     */
    public function __construct(
        Context $context,
        Config $config,
        AjaxNavigationResult $ajaxNavigationResult,
        HashInputProvider $hashInputProvider,
        Timer $timer,
        array $initializerMap
    ) {
        parent::__construct($context);
        $this->config = $config;
        $this->ajaxNavigationResult = $ajaxNavigationResult;
        $this->initializerMap = $initializerMap;
        $this->hashInputProvider = $hashInputProvider;
        $this->timer = $timer;
    }

    /**
     * Execute action based on request and return result
     *
     * Note: Request will be added as operation argument in future
     *
     * @return ResultInterface|ResponseInterface
     * @throws NotFoundException
     */
    public function execute()
    {
        if (!$this->config->isAjaxFilters()) {
            throw new NotFoundException(__('Page not found.'));
        }

        $request = $this->getRequest();

        $hashIsValid = $this->hashInputProvider->validateHash($request);

        //form is modified, don't accept the request. Should only happen in an xss attack
        if (!$hashIsValid) {
            throw new InvalidArgumentException('Incorrect/modified form parameters');
        }

        $type = $request->getParam('__tw_ajax_type');

        if (!isset($this->initializerMap[$type])) {
            throw new InvalidArgumentException('No ajax navigation result handler found for ' . $type);
        }

        $this->initializerMap[$type]->initializeAjaxResult(
            $this->ajaxNavigationResult,
            $request
        );

        return $this->ajaxNavigationResult;
    }
}
