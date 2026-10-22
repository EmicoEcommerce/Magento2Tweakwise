<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model\Client\Response;

use Emico\CodeCept\Test\Unit;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tweakwise\Magento2Tweakwise\Model\Client\Request;
use Tweakwise\Magento2Tweakwise\Model\Client\Response\RecommendationsResponse;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2TweakwiseExport\Model\Helper;

class RecommendationsResponseTest extends Unit
{
    use MockeryPHPUnitIntegration;

    /**
     * @return void
     */
    public function testMergedRecommendationsKeepEveryVisualWithANonNumericId(): void
    {
        $response = $this->createResponse();

        $response->setRecommendation([
            ['items' => ['item' => [$this->visual('banner-pillows'), $this->product('100012')]]],
            ['items' => ['item' => [$this->visual('banner-freeshipping')]]],
        ]);

        $types = array_map(fn ($item) => $item->getType(), array_values($response->getItems()));
        $this->assertSame(['visual', 'product', 'visual'], $types);
    }

    /**
     * @return void
     */
    public function testMergedRecommendationsStillDropTheSameProductTwice(): void
    {
        $response = $this->createResponse();

        $response->setRecommendation([
            ['items' => ['item' => [$this->product('100012')]]],
            ['items' => ['item' => [$this->product('100012'), $this->product('100013')]]],
        ]);

        $this->assertCount(2, $response->getItems());
    }

    /**
     * @return RecommendationsResponse
     */
    private function createResponse(): RecommendationsResponse
    {
        $config = Mockery::mock(Config::class);
        $config->shouldReceive('isGroupedProductsEnabled')->andReturn(false);

        return new RecommendationsResponse(Mockery::mock(Helper::class), Mockery::mock(Request::class), $config, []);
    }

    /**
     * @param string $id
     * @return array
     */
    private function visual(string $id): array
    {
        return ['type' => 'visual', 'itemno' => $id, 'image' => 'https://example.com/banner.png'];
    }

    /**
     * @param string $id
     * @return array
     */
    private function product(string $id): array
    {
        return ['type' => 'product', 'itemno' => $id, 'title' => 'Product ' . $id];
    }
}
