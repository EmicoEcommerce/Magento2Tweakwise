<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Controller\Ajax;

use InvalidArgumentException;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Tweakwise\Magento2Tweakwise\Model\AjaxProductCountResult;
use Tweakwise\Magento2Tweakwise\Model\AjaxResultInitializer\CountInitializerInterface;
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
     * @param JsonFactory $resultJsonFactory
     * @param AjaxProductCountResult $ajaxProductCountResult
     * @param HashInputProvider $hashInputProvider
     * @param CountInitializerInterface[] $initializerMap
     */
    public function __construct(
        Context $context,
        private readonly JsonFactory $resultJsonFactory,
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
            return $this->getBadRequestJsonResult('Incorrect/modified form parameters');
        }

        $type = $request->getParam('__tw_ajax_type');

        if (!isset($this->initializerMap[$type])) {
            return $this->getBadRequestJsonResult('No product count initializer found for type ' . $type);
        }

        try {
            $count = $this->initializerMap[$type]->initializeForCount($request);
        } catch (NoSuchEntityException | InvalidArgumentException $exception) {
            return $this->getBadRequestJsonResult($exception->getMessage());
        }

        $this->ajaxProductCountResult->setCount($count);

        return $this->ajaxProductCountResult;
    }

    private function getBadRequestJsonResult(string $message): Json
    {
        $result = $this->resultJsonFactory->create();
        $result->setHttpResponseCode(400);
        $result->setData(['error' => $message]);

        return $result;
    }
}
