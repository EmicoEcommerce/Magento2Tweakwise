<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Controller\Ajax;

use InvalidArgumentException;
use Tweakwise\Magento2Tweakwise\Model\AjaxProductCountResult;
use Tweakwise\Magento2Tweakwise\Model\AjaxResultInitializer\CountInitializerInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Tweakwise\Magento2Tweakwise\Model\FilterFormInputProvider\HashInputProvider;

/**
 * Returns only the product count for the current filter selection.
 * Used by the formFilters feature to update the "Show X items" button count without
 * fetching and rendering the full product list HTML.
 */
class ProductCount extends Action
{
    /**
     * @param Context $context
     * @param AjaxProductCountResult $ajaxProductCountResult
     * @param HashInputProvider $hashInputProvider
     * @param CountInitializerInterface[] $initializerMap
     */
    public function __construct(
        Context $context,
        private readonly AjaxProductCountResult $ajaxProductCountResult,
        private readonly HashInputProvider $hashInputProvider,
        private readonly array $initializerMap,
    ) {
        parent::__construct($context);
    }

    /**
     * @return ResultInterface|ResponseInterface
     */
    public function execute(): ResultInterface|ResponseInterface
    {
        $request = $this->getRequest();

        $hashIsValid = $this->hashInputProvider->validateHash($request);

        if (!$hashIsValid) {
            throw new InvalidArgumentException('Incorrect/modified form parameters');
        }

        $type = $request->getParam('__tw_ajax_type');

        if (!isset($this->initializerMap[$type])) {
            throw new InvalidArgumentException('No product count initializer found for type ' . $type);
        }

        $this->initializerMap[$type]->initializeForCount(
            $request
        );

        return $this->ajaxProductCountResult;
    }
}
