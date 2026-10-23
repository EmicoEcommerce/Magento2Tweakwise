<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\ViewModel;

use Emico\CodeCept\Test\Unit;
use Magento\Customer\Model\Session;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\LayoutInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use Tweakwise\Magento2Tweakwise\Helper\Cache;
use Tweakwise\Magento2Tweakwise\Model\Visual;
use Tweakwise\Magento2Tweakwise\ViewModel\LinkedProductListItem;

class LinkedProductListItemTest extends Unit
{
    use MockeryPHPUnitIntegration;

    private LayoutInterface&MockInterface $layout;
    private Cache&MockInterface $cacheHelper;
    private AbstractBlock&MockInterface $parentBlock;
    private LinkedProductListItem $subject;

    protected function _before(): void
    {
        $this->layout = Mockery::mock(LayoutInterface::class);
        $this->cacheHelper = Mockery::mock(Cache::class);
        $this->parentBlock = Mockery::mock(AbstractBlock::class);

        $this->subject = new LinkedProductListItem(
            $this->layout,
            $this->cacheHelper,
            Mockery::mock(StoreManagerInterface::class),
            Mockery::mock(Session::class)
        );
    }

    public function testGetItemHtmlRendersVisualWithVisualBlockAndSkipsCache(): void
    {
        $visual = Mockery::mock(Visual::class);

        $visualBlock = Mockery::mock(AbstractBlock::class);
        $visualBlock->shouldReceive('setData')->once()->with('visual', $visual)->andReturnSelf();
        $visualBlock->shouldReceive('toHtml')->once()->andReturn('<li class="visual">winter</li>');
        $this->layout->shouldReceive('getBlock')
            ->once()
            ->with('tweakwise.catalog.product.list.visual')
            ->andReturn($visualBlock);

        $this->cacheHelper->shouldReceive('personalMerchandisingCanBeApplied')->never();
        $this->cacheHelper->shouldReceive('load')->never();
        $this->cacheHelper->shouldReceive('save')->never();

        $result = $this->subject->getItemHtml($visual, $this->parentBlock, ['card_type' => 'upsell']);

        $this->assertSame('<li class="visual">winter</li>', $result);
    }

    public function testGetItemHtmlReturnsEmptyStringWhenVisualBlockIsMissing(): void
    {
        $this->layout->shouldReceive('getBlock')
            ->once()
            ->with('tweakwise.catalog.product.list.visual')
            ->andReturn(false);

        $result = $this->subject->getItemHtml(
            Mockery::mock(Visual::class),
            $this->parentBlock,
            ['card_type' => 'related']
        );

        $this->assertSame('', $result);
    }
}
