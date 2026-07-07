<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Controller\Ajax;

use Emico\CodeCept\Test\Unit;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Tweakwise\Magento2Tweakwise\Controller\Ajax\ProductCount;
use Tweakwise\Magento2Tweakwise\Model\AjaxProductCountResult;
use Tweakwise\Magento2Tweakwise\Model\AjaxResultInitializer\CountInitializerInterface;
use Tweakwise\Magento2Tweakwise\Model\FilterFormInputProvider\HashInputProvider;
use Tweakwise\Test\Support\UnitTester;

class ProductCountTest extends Unit
{
    protected UnitTester $tester;

    private Context&MockObject $context;

    private RequestInterface&MockObject $request;

    private HashInputProvider&MockObject $hashInputProvider;

    private JsonFactory&MockObject $resultJsonFactory;

    private Json&MockObject $jsonResult;

    private AjaxProductCountResult&MockObject $result;

    private CountInitializerInterface&MockObject $initializer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->context = $this->createMock(Context::class);
        $this->request = $this->createMock(RequestInterface::class);
        $this->hashInputProvider = $this->createMock(HashInputProvider::class);
        $this->resultJsonFactory = $this->createMock(JsonFactory::class);
        $this->jsonResult = $this->createMock(Json::class);
        $this->result = $this->createMock(AjaxProductCountResult::class);
        $this->initializer = $this->createMock(CountInitializerInterface::class);

        $this->context->method('getRequest')->willReturn($this->request);
    }

    public function testExecuteReturnsJsonResult(): void
    {
        $this->request->expects($this->once())
            ->method('getParam')
            ->with('__tw_ajax_type')
            ->willReturn('category');
        $this->hashInputProvider->expects($this->once())
            ->method('validateHash')
            ->with($this->request)
            ->willReturn(true);
        $this->initializer->expects($this->once())
            ->method('initializeForCount')
            ->with($this->request)
            ->willReturn(27);
        $this->result->expects($this->once())
            ->method('setCount')
            ->with(27);

        $subject = new ProductCount(
            $this->context,
            $this->resultJsonFactory,
            $this->result,
            $this->hashInputProvider,
            ['category' => $this->initializer],
        );

        $this->assertSame($this->result, $subject->execute());
    }

    public function testExecuteReturnsBadRequestJsonForInvalidHash(): void
    {
        $this->hashInputProvider->expects($this->once())
            ->method('validateHash')
            ->with($this->request)
            ->willReturn(false);

        $this->resultJsonFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->jsonResult);
        $this->jsonResult->expects($this->once())
            ->method('setHttpResponseCode')
            ->with(400)
            ->willReturnSelf();
        $this->jsonResult->expects($this->once())
            ->method('setData')
            ->with(['error' => 'Incorrect/modified form parameters'])
            ->willReturnSelf();

        $subject = new ProductCount(
            $this->context,
            $this->resultJsonFactory,
            $this->result,
            $this->hashInputProvider,
            ['category' => $this->initializer],
        );

        $this->assertSame($this->jsonResult, $subject->execute());
    }

    public function testExecuteReturnsBadRequestJsonForUnknownType(): void
    {
        $this->request->expects($this->once())
            ->method('getParam')
            ->with('__tw_ajax_type')
            ->willReturn('missing');
        $this->hashInputProvider->expects($this->once())
            ->method('validateHash')
            ->with($this->request)
            ->willReturn(true);
        $this->resultJsonFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->jsonResult);
        $this->jsonResult->expects($this->once())
            ->method('setHttpResponseCode')
            ->with(400)
            ->willReturnSelf();
        $this->jsonResult->expects($this->once())
            ->method('setData')
            ->with(['error' => 'No product count initializer found for type missing'])
            ->willReturnSelf();

        $subject = new ProductCount(
            $this->context,
            $this->resultJsonFactory,
            $this->result,
            $this->hashInputProvider,
            ['category' => $this->initializer],
        );

        $this->assertSame($this->jsonResult, $subject->execute());
    }
}
