<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model\Catalog\Product;

use Emico\CodeCept\Test\Unit;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Product\VisualBuilder;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\ItemType as ClientItemType;
use Tweakwise\Magento2Tweakwise\Model\Visual;
use Tweakwise\Magento2Tweakwise\Model\VisualFactory;

class VisualBuilderTest extends Unit
{
    use MockeryPHPUnitIntegration;

    public function testBuildMapsVisualItemToVisual(): void
    {
        $item = new ClientItemType([
            ClientItemType::TYPE => 'visual',
            ClientItemType::ID => '979a0492-5beb-42be-925a-dca7c24de993',
            ClientItemType::IMAGE => 'https://example.com/winter.jpg',
            ClientItemType::URL => 'https://example.com/winter',
            ClientItemType::VISUAL_PROPERTIES => [
                ClientItemType::COLSPAN => 2,
                ClientItemType::ROWSPAN => 3,
            ],
        ]);

        $visual = Mockery::mock(Visual::class);
        $visual->shouldReceive('setId')->once()->with('979a0492-5beb-42be-925a-dca7c24de993');
        $visual->shouldReceive('setData')
            ->once()
            ->with(ClientItemType::TWEAKWISE_ID, '979a0492-5beb-42be-925a-dca7c24de993');
        $visual->shouldReceive('setImageUrl')->once()->with('https://example.com/winter.jpg');
        $visual->shouldReceive('setUrl')->once()->with('https://example.com/winter');
        $visual->shouldReceive('setColspan')->once()->with(2);
        $visual->shouldReceive('setRowspan')->once()->with(3);
        $visual->shouldReceive('setVisualAttributes')->once()->with([]);

        $this->assertSame($visual, $this->createSubject($visual)->build($item));
    }

    public function testBuildSkipsColspanAndRowspanWhenResponseHasNoVisualProperties(): void
    {
        $item = new ClientItemType([
            ClientItemType::TYPE => 'visual',
            ClientItemType::ID => 'banner',
            ClientItemType::IMAGE => 'https://example.com/banner.jpg',
            ClientItemType::URL => '',
        ]);

        $visual = Mockery::mock(Visual::class);
        $visual->shouldReceive('setId')->once()->with('banner');
        $visual->shouldReceive('setData')->once();
        $visual->shouldReceive('setImageUrl')->once();
        $visual->shouldReceive('setUrl')->once()->with('');
        $visual->shouldReceive('setColspan')->never();
        $visual->shouldReceive('setRowspan')->never();
        $visual->shouldReceive('setVisualAttributes')->once();

        $this->createSubject($visual)->build($item);
    }

    private function createSubject(Visual $visual): VisualBuilder
    {
        $visualFactory = Mockery::mock(VisualFactory::class);
        $visualFactory->shouldReceive('create')->once()->andReturn($visual);

        return new VisualBuilder($visualFactory);
    }
}
