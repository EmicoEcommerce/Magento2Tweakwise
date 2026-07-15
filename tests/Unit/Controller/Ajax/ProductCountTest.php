<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Controller\Ajax;

use Emico\CodeCept\Test\Unit;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Mockery;
use Mockery\MockInterface;
use Tweakwise\Magento2Tweakwise\Controller\Ajax\ProductCount;
use Tweakwise\Magento2Tweakwise\Model\AjaxProductCountResult;
use Tweakwise\Magento2Tweakwise\Model\AjaxResultInitializer\CountInitializerInterface;
use Tweakwise\Magento2Tweakwise\Model\FilterFormInputProvider\HashInputProvider;
use Tweakwise\Test\Support\UnitTester;

class ProductCountTest extends Unit
{
    protected UnitTester $tester;

    private Context&MockInterface $context;

    private RequestInterface&MockInterface $request;

    private HashInputProvider&MockInterface $hashInputProvider;

    private JsonFactory&MockInterface $resultJsonFactory;

    private Json&MockInterface $jsonResult;

    private AjaxProductCountResult&MockInterface $result;

    private CountInitializerInterface&MockInterface $initializer;

    public function _before(): void
    {
        $this->context = Mockery::mock(Context::class);
        $this->request = Mockery::mock(RequestInterface::class);
        $this->hashInputProvider = Mockery::mock(HashInputProvider::class);
        $this->resultJsonFactory = Mockery::mock(JsonFactory::class);
        $this->jsonResult = Mockery::mock(Json::class);
        $this->result = Mockery::mock(AjaxProductCountResult::class);
        $this->initializer = Mockery::mock(CountInitializerInterface::class);

        $this->context->shouldReceive('getRequest')->andReturn($this->request);
        $this->context->shouldReceive('getResponse')->andReturn(Mockery::mock(ResponseInterface::class));
    }

    public function _after(): void
    {
        Mockery::close();
    }

    public function testExecuteReturnsJsonResult(): void
    {
        $this->request->shouldReceive('getParam')
            ->once()
            ->with('__tw_ajax_type')
            ->andReturn('category');
        $this->hashInputProvider->shouldReceive('validateHash')
            ->once()
            ->with($this->request)
            ->andReturn(true);
        $this->initializer->shouldReceive('initializeForCount')
            ->once()
            ->with($this->request)
            ->andReturn(27);
        $this->result->shouldReceive('setCount')
            ->once()
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
        $this->hashInputProvider->shouldReceive('validateHash')
            ->once()
            ->with($this->request)
            ->andReturn(false);

        $this->resultJsonFactory->shouldReceive('create')
            ->once()
            ->andReturn($this->jsonResult);
        $this->jsonResult->shouldReceive('setHttpResponseCode')
            ->once()
            ->with(400)
            ->andReturnSelf();
        $this->jsonResult->shouldReceive('setData')
            ->once()
            ->with(['error' => 'Incorrect/modified form parameters'])
            ->andReturnSelf();

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
        $this->request->shouldReceive('getParam')
            ->once()
            ->with('__tw_ajax_type')
            ->andReturn('missing');
        $this->hashInputProvider->shouldReceive('validateHash')
            ->once()
            ->with($this->request)
            ->andReturn(true);
        $this->resultJsonFactory->shouldReceive('create')
            ->once()
            ->andReturn($this->jsonResult);
        $this->jsonResult->shouldReceive('setHttpResponseCode')
            ->once()
            ->with(400)
            ->andReturnSelf();
        $this->jsonResult->shouldReceive('setData')
            ->once()
            ->with(['error' => 'No product count initializer found for type missing'])
            ->andReturnSelf();

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
