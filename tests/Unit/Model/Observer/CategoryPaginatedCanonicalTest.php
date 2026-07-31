<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model\Observer;

use Emico\CodeCept\Test\Unit;
use Magento\Catalog\Block\Category\View as CategoryView;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\View\Asset\GroupedCollection;
use Magento\Framework\View\Page\Config as PageConfig;
use Mockery;
use Mockery\MockInterface;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2Tweakwise\Model\Observer\CategoryPaginatedCanonical;
use Tweakwise\Test\Support\UnitTester;

class CategoryPaginatedCanonicalTest extends Unit
{
    protected UnitTester $tester;

    private Config&MockInterface $config;
    private PageConfig&MockInterface $pageConfig;
    private RequestInterface&MockInterface $request;
    private CategoryPaginatedCanonical $subject;

    public function _before(): void
    {
        $this->config = Mockery::mock(Config::class);
        $this->pageConfig = Mockery::mock(PageConfig::class);
        $this->request = Mockery::mock(RequestInterface::class);

        $this->subject = new CategoryPaginatedCanonical(
            $this->config,
            $this->pageConfig,
            $this->request
        );
    }

    public function _after(): void
    {
        Mockery::close();
    }

    public function testExecuteReturnsEarlyWhenFeatureDisabled(): void
    {
        $this->config->shouldReceive('isPaginatedCanonicalEnabled')->once()->andReturn(false);
        $this->request->shouldNotReceive('getParam');
        $this->pageConfig->shouldNotReceive('getAssetCollection');

        $observer = new Observer(['block' => Mockery::mock(CategoryView::class)]);
        $this->subject->execute($observer);

        $this->assertTrue(true);
    }

    public function testExecuteReturnsEarlyWhenPageLowerThanTwo(): void
    {
        $this->config->shouldReceive('isPaginatedCanonicalEnabled')->once()->andReturn(true);
        $this->request->shouldReceive('getParam')->with('p')->once()->andReturn('1');
        $this->pageConfig->shouldNotReceive('getAssetCollection');

        $observer = new Observer(['block' => Mockery::mock(CategoryView::class)]);
        $this->subject->execute($observer);

        $this->assertTrue(true);
    }

    public function testExecuteUpdatesCanonicalWhenEnabledOnPaginatedCategoryPage(): void
    {
        $this->config->shouldReceive('isPaginatedCanonicalEnabled')->once()->andReturn(true);
        $this->request->shouldReceive('getParam')->with('p')->once()->andReturn('4');

        $existingCanonical = 'https://example.com/category?color=blue';

        $canonicalGroup = Mockery::mock();
        $canonicalGroup->shouldReceive('getAll')->once()->andReturn([$existingCanonical => Mockery::mock()]);

        $assetCollection = Mockery::mock(GroupedCollection::class);
        $assetCollection->shouldReceive('getGroupByContentType')->once()->with('canonical')->andReturn($canonicalGroup);
        $assetCollection->shouldReceive('remove')->once()->with($existingCanonical);

        $this->pageConfig->shouldReceive('getAssetCollection')->once()->andReturn($assetCollection);
        $this->pageConfig->shouldReceive('addRemotePageAsset')->once()->with(
            'https://example.com/category?color=blue&p=4',
            'canonical',
            ['attributes' => ['rel' => 'canonical']]
        );

        $observer = new Observer(['block' => Mockery::mock(CategoryView::class)]);
        $this->subject->execute($observer);

        $this->assertTrue(true);
    }
}
