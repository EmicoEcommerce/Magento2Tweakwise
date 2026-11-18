<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model\Catalog\Layer\NavigationContext;

use Emico\CodeCept\Test\Unit;
use Mockery;
use Tweakwise\Magento2Tweakwise\Exception\ApiException;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\NavigationContext;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\NavigationContext\CurrentContext;
use Tweakwise\Magento2Tweakwise\Model\Client\Response\ProductNavigationResponse;

class CurrentContextTest extends Unit
{
    public function testRequestIdDoesNotFetchAnUnavailableResponse(): void
    {
        $context = Mockery::mock(NavigationContext::class);
        $context->shouldReceive('hasResponse')->once()->andReturn(false);
        $context->shouldReceive('getResponse')->andThrow(new ApiException('Gateways unreachable'));
        $subject = new CurrentContext();
        $subject->setContext($context);

        $this->assertSame('', $subject->getTweakwiseRequestId());
    }

    public function testRequestIdUsesAnAlreadyResolvedResponse(): void
    {
        $response = Mockery::mock(ProductNavigationResponse::class);
        $response->shouldReceive('getRequestId')->once()->andReturn('resolved-request');
        $context = Mockery::mock(NavigationContext::class);
        $context->shouldReceive('hasResponse')->once()->andReturn(true);
        $context->shouldReceive('getResponse')->once()->andReturn($response);
        $subject = new CurrentContext();
        $subject->setContext($context);

        $this->assertSame('resolved-request', $subject->getTweakwiseRequestId());
    }
}
