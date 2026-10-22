<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Block\Product\ProductList;

use Emico\CodeCept\Test\Unit;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Registry;
use Mockery;
use Tweakwise\Magento2Tweakwise\Block\Product\ProductList\Linked;
use Tweakwise\Magento2Tweakwise\Helper\Cache;

class LinkedTest extends Unit
{
    /**
     * @return void
     */
    public function testGetTemplateReturnsModuleTemplateWhenMerchandisingListingShouldBeUsed(): void
    {
        $this->assertSame(
            'Tweakwise_Magento2Tweakwise::product/list/items.phtml',
            $this->createLinked(true, false)->getTemplate()
        );
    }

    /**
     * @return void
     */
    public function testGetTemplateReturnsNullWhenMerchandisingListingShouldNotBeUsed(): void
    {
        $this->assertNull($this->createLinked(false, false)->getTemplate());
    }

    /**
     * @return void
     */
    public function testGetTemplateReturnsNullOnHyvaTheme(): void
    {
        $this->assertNull($this->createLinked(true, true)->getTemplate());
    }

    /**
     * @param bool $shouldUseMerchandisingListing
     * @param bool $isHyvaTheme
     * @return Linked
     */
    private function createLinked(bool $shouldUseMerchandisingListing, bool $isHyvaTheme): Linked
    {
        $cacheHelper = Mockery::mock(Cache::class);
        $cacheHelper->shouldReceive('shouldUseMerchandisingListing')->andReturn($shouldUseMerchandisingListing);
        $cacheHelper->shouldReceive('isHyvaTheme')->andReturn($isHyvaTheme);

        return new Linked(
            $cacheHelper,
            Mockery::mock(RequestInterface::class),
            Mockery::mock(ProductRepositoryInterface::class),
            Mockery::mock(Registry::class)
        );
    }
}
