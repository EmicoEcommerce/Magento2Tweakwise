<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Block\Catalog\Product\ProductList;

use Emico\CodeCept\Test\Unit;
use Magento\Catalog\Model\Product;
use Magento\Framework\Registry;
use Mockery;
use Mockery\MockInterface;
use Tweakwise\Magento2Tweakwise\Block\Catalog\Product\ProductList\AbstractRecommendationPlugin;
use Tweakwise\Magento2Tweakwise\Exception\InvalidArgumentException;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Product\Recommendation\Collection;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Product\Recommendation\Context;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\Recommendations\FeaturedRequest;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\Recommendations\ProductRequest;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2Tweakwise\Model\Config\TemplateFinder;
use Tweakwise\Test\Support\UnitTester;

class AbstractRecommendationPluginTest extends Unit
{
    protected UnitTester $tester;

    private Registry&MockInterface $registry;
    private Context&MockInterface $context;
    private TemplateFinder&MockInterface $templateFinder;
    private object $subject;

    public function _before(): void
    {
        $this->registry = Mockery::mock(Registry::class);
        $this->context = Mockery::mock(Context::class);
        $this->templateFinder = Mockery::mock(TemplateFinder::class);

        $this->subject = new class (
            Mockery::mock(Config::class),
            $this->registry,
            $this->context,
            $this->templateFinder,
        ) extends AbstractRecommendationPlugin {
            protected function getType()
            {
                return Config::RECOMMENDATION_TYPE_UPSELL;
            }

            public function fetchCollection(): Collection
            {
                return $this->getCollection();
            }
        };
    }

    public function _after(): void
    {
        Mockery::close();
    }

    public function testGetCollectionConfiguresRequestEachTimeAndDoesNotCacheCollection(): void
    {
        $product = Mockery::mock(Product::class);
        $request = Mockery::mock(ProductRequest::class);
        $firstCollection = Mockery::mock(Collection::class);
        $secondCollection = Mockery::mock(Collection::class);

        $this->context->shouldReceive('getRequest')->twice()->andReturn($request);
        $this->registry->shouldReceive('registry')->with('product')->twice()->andReturn($product);
        $this->templateFinder->shouldReceive('forProduct')
            ->with($product, Config::RECOMMENDATION_TYPE_UPSELL)
            ->twice()
            ->andReturn(12);
        $request->shouldReceive('setProduct')->with($product)->twice()->andReturnSelf();
        $request->shouldReceive('setTemplate')->with(12)->twice()->andReturnSelf();
        $this->context->shouldReceive('getCollection')->twice()->andReturn($firstCollection, $secondCollection);

        $this->assertSame($firstCollection, $this->subject->fetchCollection());
        $this->assertSame($secondCollection, $this->subject->fetchCollection());
    }

    public function testGetCollectionThrowsWhenContextRequestIsNotProductRequest(): void
    {
        $this->context->shouldReceive('getRequest')->once()->andReturn(Mockery::mock(FeaturedRequest::class));
        $this->registry->shouldNotReceive('registry');
        $this->templateFinder->shouldNotReceive('forProduct');
        $this->context->shouldNotReceive('getCollection');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Set context should contain ProductRequest');

        $this->subject->fetchCollection();
    }

    public function testGetCollectionSkipsRequestConfigurationWhenRegistryHasNoProduct(): void
    {
        $request = Mockery::mock(ProductRequest::class);
        $collection = Mockery::mock(Collection::class);

        $this->context->shouldReceive('getRequest')->once()->andReturn($request);
        $this->registry->shouldReceive('registry')->with('product')->once()->andReturn(null);
        $request->shouldNotReceive('setProduct');
        $request->shouldNotReceive('setTemplate');
        $this->templateFinder->shouldNotReceive('forProduct');
        $this->context->shouldReceive('getCollection')->once()->andReturn($collection);

        $this->assertSame($collection, $this->subject->fetchCollection());
    }
}
