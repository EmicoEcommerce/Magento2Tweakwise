<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model\Catalog\Layer;

use Emico\CodeCept\Test\Unit;
use Exception;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\App\Request\Http as MagentoHttpRequest;
use Mockery;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Filter;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Filter\Item;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\NavigationContext\CurrentContext;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\CategoryUrlInterface;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\Strategy\UrlStrategyFactory;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\UrlModel;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\ProductNavigationRequest;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\ProductSearchRequest;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\AttributeType;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\FacetType;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\FacetType\SettingsType;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2TweakwiseExport\Model\Helper as ExportHelper;
use Tweakwise\Test\Support\UnitTester;

class UrlTest extends Unit
{
    protected UnitTester $tester;

    private UrlStrategyFactory $urlStrategyFactory;
    private MagentoHttpRequest $request;
    private Config $config;
    private CurrentContext $currentContext;
    private CategoryUrlInterface $categoryUrlStrategy;
    private Url $subject;

    /**
     * @return void
     * phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
     */
    public function _before(): void
    {
        $this->urlStrategyFactory = Mockery::mock(UrlStrategyFactory::class);
        $this->request = Mockery::mock(MagentoHttpRequest::class);
        $this->config = Mockery::mock(Config::class);
        $this->currentContext = Mockery::mock(CurrentContext::class);
        $this->categoryUrlStrategy = Mockery::mock(CategoryUrlInterface::class);

        $this->subject = new Url(
            $this->urlStrategyFactory,
            $this->request,
            Mockery::mock(CategoryRepositoryInterface::class),
            Mockery::mock(ExportHelper::class),
            Mockery::mock(UrlModel::class),
            $this->config,
            $this->currentContext,
        );
    }

    /**
     * @return void
     */
    public function testGetSelectFilterReturnsTweakwiseCategoryLinkOnCategoryPageWhenEnabled(): void
    {
        $item = $this->createCategoryItem('https://magento2.test/default/women/tops-women2/');
        $this->config->shouldReceive('isCategoryUrlFromTweakwiseEnabled')->andReturn(true);
        $this->currentContext->shouldReceive('getRequest')->andReturn(Mockery::mock(ProductNavigationRequest::class));
        $this->urlStrategyFactory->shouldNotReceive('create');

        $result = $this->subject->getSelectFilter($item);

        $this->assertEquals('https://magento2.test/default/women/tops-women2/', $result);
    }

    /**
     * @return void
     */
    public function testGetSelectFilterFallsBackToMagentoCategoryUrlWhenSettingIsDisabled(): void
    {
        $item = $this->createCategoryItem('https://magento2.test/default/women/tops-women2/');
        $this->config->shouldReceive('isCategoryUrlFromTweakwiseEnabled')->andReturn(false);
        $this->urlStrategyFactory
            ->shouldReceive('create')
            ->with(CategoryUrlInterface::class)
            ->once()
            ->andReturn($this->categoryUrlStrategy);
        $this->categoryUrlStrategy
            ->shouldReceive('getCategoryFilterSelectUrl')
            ->with($this->request, $item)
            ->once()
            ->andReturn('/default/women/tops-women2/facet/');

        $result = $this->subject->getSelectFilter($item);

        $this->assertEquals('/default/women/tops-women2/facet/', $result);
    }

    /**
     * @return void
     */
    public function testGetSelectFilterFallsBackToMagentoCategoryUrlWhenLinkIsEmpty(): void
    {
        $item = $this->createCategoryItem('');
        $this->config->shouldReceive('isCategoryUrlFromTweakwiseEnabled')->andReturn(true);
        $this->urlStrategyFactory
            ->shouldReceive('create')
            ->with(CategoryUrlInterface::class)
            ->once()
            ->andReturn($this->categoryUrlStrategy);
        $this->categoryUrlStrategy
            ->shouldReceive('getCategoryFilterSelectUrl')
            ->with($this->request, $item)
            ->once()
            ->andReturn('/default/women/tops-women2/facet/');

        $result = $this->subject->getSelectFilter($item);

        $this->assertEquals('/default/women/tops-women2/facet/', $result);
    }

    /**
     * @return void
     */
    public function testGetSelectFilterFallsBackToMagentoCategoryUrlOnSearchRequest(): void
    {
        $item = $this->createCategoryItem('https://magento2.test/default/women/tops-women2/');
        $this->config->shouldReceive('isCategoryUrlFromTweakwiseEnabled')->andReturn(true);
        $this->currentContext->shouldReceive('getRequest')->andReturn(Mockery::mock(ProductSearchRequest::class));
        $this->urlStrategyFactory
            ->shouldReceive('create')
            ->with(CategoryUrlInterface::class)
            ->once()
            ->andReturn($this->categoryUrlStrategy);
        $this->categoryUrlStrategy
            ->shouldReceive('getCategoryFilterSelectUrl')
            ->with($this->request, $item)
            ->once()
            ->andReturn('/catalogsearch/result/?cat=tops-women2');

        $result = $this->subject->getSelectFilter($item);

        $this->assertEquals('/catalogsearch/result/?cat=tops-women2', $result);
    }

    /**
     * @return void
     */
    public function testGetSelectFilterFallsBackToMagentoCategoryUrlWhenContextIsUnavailable(): void
    {
        $item = $this->createCategoryItem('https://magento2.test/default/women/tops-women2/');
        $this->config->shouldReceive('isCategoryUrlFromTweakwiseEnabled')->andReturn(true);
        $this->currentContext->shouldReceive('getRequest')->andThrow(new Exception('No active context'));
        $this->urlStrategyFactory
            ->shouldReceive('create')
            ->with(CategoryUrlInterface::class)
            ->once()
            ->andReturn($this->categoryUrlStrategy);
        $this->categoryUrlStrategy
            ->shouldReceive('getCategoryFilterSelectUrl')
            ->with($this->request, $item)
            ->once()
            ->andReturn('/default/women/tops-women2/facet/');

        $result = $this->subject->getSelectFilter($item);

        $this->assertEquals('/default/women/tops-women2/facet/', $result);
    }

    /**
     * @param string $link
     * @return Item
     */
    private function createCategoryItem(string $link): Item
    {
        $settings = Mockery::mock(SettingsType::class);
        $settings->shouldReceive('getSource')->andReturn(SettingsType::SOURCE_CATEGORY);

        $facetType = Mockery::mock(FacetType::class);
        $facetType->shouldReceive('getFacetSettings')->andReturn($settings);

        $filter = Mockery::mock(Filter::class);
        $filter->shouldReceive('getFacet')->andReturn($facetType);

        $attribute = Mockery::mock(AttributeType::class);
        $attribute->shouldReceive('getLink')->andReturn($link);

        $item = Mockery::mock(Item::class);
        $item->shouldReceive('getFilter')->andReturn($filter);
        $item->shouldReceive('getAttribute')->andReturn($attribute);

        return $item;
    }
}
