<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\ViewModel;

use Emico\CodeCept\Test\Unit;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\LayoutInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use Tweakwise\Magento2Tweakwise\Api\Data\EventInterface;
use Tweakwise\Magento2Tweakwise\Api\Data\TagInterface;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2Tweakwise\ViewModel\PersonalMerchandisingAnalytics;

class PersonalMerchandisingAnalyticsTest extends Unit
{
    use MockeryPHPUnitIntegration;

    private LayoutInterface&MockInterface $layout;
    private Json&MockInterface $jsonSerializer;
    private PersonalMerchandisingAnalytics $subject;

    protected function _before(): void
    {
        $this->layout = Mockery::mock(LayoutInterface::class);
        $this->jsonSerializer = Mockery::mock(Json::class);

        $this->subject = new PersonalMerchandisingAnalytics(
            Mockery::mock(Config::class),
            Mockery::mock(StoreManagerInterface::class),
            Mockery::mock(RequestInterface::class),
            $this->jsonSerializer,
            $this->layout
        );
    }

    public function testGetEventsDataReturnsAnEmptyListWhenTheAnalyticsBlockIsNotFound(): void
    {
        $this->layout->shouldReceive('getBlock')->once()->with('tweakwise.analytics')->andReturn(false);
        $this->jsonSerializer->shouldReceive('serialize')->once()->with([])->andReturn('[]');

        $this->assertSame('[]', $this->subject->getEventsData('req-1'));
    }

    public function testGetEventsDataComposesTagsFromTheDataLayerArgument(): void
    {
        $productTag = Mockery::mock(TagInterface::class);
        $productTag->shouldReceive('get')->once()->andReturn('100016');

        $block = Mockery::mock(AbstractBlock::class);
        $block->shouldReceive('getData')->once()->with('data_layer')->andReturn(['product' => $productTag]);
        $block->shouldReceive('getData')->once()->with('data_layer_events')->andReturn([]);

        $this->layout->shouldReceive('getBlock')->once()->with('tweakwise.analytics')->andReturn($block);

        $this->jsonSerializer->shouldReceive('serialize')->once()->with([
            ['type' => 'product', 'value' => '100016', 'requestId' => 'req-1'],
        ])->andReturn('[{"type":"product"}]');

        $this->assertSame('[{"type":"product"}]', $this->subject->getEventsData('req-1'));
    }

    public function testGetEventsDataFlattensMultipleValuesFromADataLayerEventWithAnEmptyRequestId(): void
    {
        $purchaseEvent = Mockery::mock(EventInterface::class);
        $purchaseEvent->shouldReceive('get')->once()->andReturn([
            ['productKeys' => ['1'], 'revenue' => 10.0],
            ['productKeys' => ['2'], 'revenue' => 20.0],
        ]);

        $block = Mockery::mock(AbstractBlock::class);
        $block->shouldReceive('getData')->once()->with('data_layer')->andReturn([]);
        $block->shouldReceive('getData')->once()->with('data_layer_events')->andReturn(['purchase_event' => $purchaseEvent]);

        $this->layout->shouldReceive('getBlock')->once()->with('tweakwise.analytics')->andReturn($block);

        $this->jsonSerializer->shouldReceive('serialize')->once()->with([
            ['type' => 'purchase_event', 'value' => ['productKeys' => ['1'], 'revenue' => 10.0], 'requestId' => ''],
            ['type' => 'purchase_event', 'value' => ['productKeys' => ['2'], 'revenue' => 20.0], 'requestId' => ''],
        ])->andReturn('[...]');

        $this->assertSame('[...]', $this->subject->getEventsData('req-1'));
    }
}
