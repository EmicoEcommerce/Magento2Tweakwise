<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Block\LayeredNavigation\RenderLayered;

use Emico\CodeCept\Test\Unit;
use Mockery;
use Mockery\MockInterface;
use Tweakwise\Magento2Tweakwise\Block\LayeredNavigation\RenderLayered\DefaultRenderer;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Filter\Item;
use Tweakwise\Test\Support\UnitTester;

class DefaultRendererTest extends Unit
{
    protected UnitTester $tester;

    /**
     * @var DefaultRenderer|MockInterface
     */
    private DefaultRenderer|MockInterface $renderer;

    /**
     * @return void
     * phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
     */
    public function _before(): void
    {
        $this->mockRenderer();
    }

    /**
     * @return void
     */
    public function testGetCategoryUrlDoesNotPrependDomainWhenOnlySchemeDiffers(): void
    {
        $url = 'http://magento2.test/default/women/tops-women2/';
        $item = $this->mockItem($url);

        $this->assertEquals(
            $url,
            $this->renderer->getCategoryUrl($item)
        );
    }

    /**
     * @return void
     */
    public function testGetCategoryUrlPrependsBaseUrlForRelativeFacetLink(): void
    {
        $item = $this->mockItem('/default/women/tops-women2/');

        $this->assertEquals(
            'https://magento2.test/default/women/tops-women2/',
            $this->renderer->getCategoryUrl($item)
        );
    }

    /**
     * @return void
     */
    private function mockRenderer(): void
    {
        $renderer = Mockery::mock(DefaultRenderer::class)->makePartial();
        $renderer->shouldReceive('getBaseUrl')->andReturn('https://magento2.test/');

        $this->renderer = $renderer;
    }

    /**
     * @param string $url
     * @return Item|MockInterface
     */
    private function mockItem(string $url): Item|MockInterface
    {
        $item = Mockery::mock(Item::class);
        $item->shouldReceive('getUrl')->andReturn($url);

        return $item;
    }
}
