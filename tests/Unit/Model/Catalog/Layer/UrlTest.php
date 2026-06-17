<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model\Catalog\Layer;

use Emico\CodeCept\Test\Unit;
use Exception;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\App\Request\Http as MagentoHttpRequest;
use PHPUnit\Framework\MockObject\MockObject;
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

    /**
     * @var UrlStrategyFactory|MockObject
     */
    private UrlStrategyFactory|MockObject $urlStrategyFactory;

    /**
     * @var MagentoHttpRequest|MockObject
     */
    private MagentoHttpRequest|MockObject $request;

    /**
     * @var CategoryRepositoryInterface|MockObject
     */
    private CategoryRepositoryInterface|MockObject $categoryRepository;

    /**
     * @var ExportHelper|MockObject
     */
    private ExportHelper|MockObject $exportHelper;

    /**
     * @var UrlModel|MockObject
     */
    private UrlModel|MockObject $magentoUrl;

    /**
     * @var Config|MockObject
     */
    private Config|MockObject $config;

    /**
     * @var CurrentContext|MockObject
     */
    private CurrentContext|MockObject $currentContext;

    /**
     * @var CategoryUrlInterface|MockObject
     */
    private CategoryUrlInterface|MockObject $categoryUrlStrategy;

    /**
     * @var Url
     */
    private Url $subject;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->urlStrategyFactory = $this->createMock(UrlStrategyFactory::class);
        $this->request = $this->createMock(MagentoHttpRequest::class);
        $this->categoryRepository = $this->createMock(CategoryRepositoryInterface::class);
        $this->exportHelper = $this->createMock(ExportHelper::class);
        $this->magentoUrl = $this->createMock(UrlModel::class);
        $this->config = $this->createMock(Config::class);
        $this->currentContext = $this->createMock(CurrentContext::class);
        $this->categoryUrlStrategy = $this->createMock(CategoryUrlInterface::class);

        $this->subject = new Url(
            $this->urlStrategyFactory,
            $this->request,
            $this->categoryRepository,
            $this->exportHelper,
            $this->magentoUrl,
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
        $this->config->method('isCategoryUrlFromTweakwiseEnabled')->willReturn(true);
        $this->currentContext->method('getRequest')->willReturn($this->createMock(ProductNavigationRequest::class));

        $this->urlStrategyFactory->expects($this->never())->method('create');

        $result = $this->subject->getSelectFilter($item);

        $this->assertSame('https://magento2.test/default/women/tops-women2/', $result);
    }

    /**
     * @return void
     */
    public function testGetSelectFilterFallsBackToMagentoCategoryUrlWhenSettingIsDisabled(): void
    {
        $item = $this->createCategoryItem('https://magento2.test/default/women/tops-women2/');
        $this->config->method('isCategoryUrlFromTweakwiseEnabled')->willReturn(false);

        $this->currentContext->expects($this->never())->method('getRequest');
        $this->urlStrategyFactory
            ->expects($this->once())
            ->method('create')
            ->with(CategoryUrlInterface::class)
            ->willReturn($this->categoryUrlStrategy);
        $this->categoryUrlStrategy
            ->expects($this->once())
            ->method('getCategoryFilterSelectUrl')
            ->with($this->request, $item)
            ->willReturn('/default/women/tops-women2/facet/');

        $result = $this->subject->getSelectFilter($item);

        $this->assertSame('/default/women/tops-women2/facet/', $result);
    }

    /**
     * @return void
     */
    public function testGetSelectFilterFallsBackToMagentoCategoryUrlWhenLinkIsEmpty(): void
    {
        $item = $this->createCategoryItem('');
        $this->config->method('isCategoryUrlFromTweakwiseEnabled')->willReturn(true);

        $this->currentContext->expects($this->never())->method('getRequest');
        $this->urlStrategyFactory
            ->expects($this->once())
            ->method('create')
            ->with(CategoryUrlInterface::class)
            ->willReturn($this->categoryUrlStrategy);
        $this->categoryUrlStrategy
            ->expects($this->once())
            ->method('getCategoryFilterSelectUrl')
            ->with($this->request, $item)
            ->willReturn('/default/women/tops-women2/facet/');

        $result = $this->subject->getSelectFilter($item);

        $this->assertSame('/default/women/tops-women2/facet/', $result);
    }

    /**
     * @return void
     */
    public function testGetSelectFilterFallsBackToMagentoCategoryUrlOnSearchRequest(): void
    {
        $item = $this->createCategoryItem('https://magento2.test/default/women/tops-women2/');
        $this->config->method('isCategoryUrlFromTweakwiseEnabled')->willReturn(true);
        $this->currentContext->method('getRequest')->willReturn($this->createMock(ProductSearchRequest::class));

        $this->urlStrategyFactory
            ->expects($this->once())
            ->method('create')
            ->with(CategoryUrlInterface::class)
            ->willReturn($this->categoryUrlStrategy);
        $this->categoryUrlStrategy
            ->expects($this->once())
            ->method('getCategoryFilterSelectUrl')
            ->with($this->request, $item)
            ->willReturn('/catalogsearch/result/?cat=tops-women2');

        $result = $this->subject->getSelectFilter($item);

        $this->assertSame('/catalogsearch/result/?cat=tops-women2', $result);
    }

    /**
     * @return void
     */
    public function testGetSelectFilterFallsBackToMagentoCategoryUrlWhenContextIsUnavailable(): void
    {
        $item = $this->createCategoryItem('https://magento2.test/default/women/tops-women2/');
        $this->config->method('isCategoryUrlFromTweakwiseEnabled')->willReturn(true);
        $this->currentContext->method('getRequest')->willThrowException(new Exception('No active context'));

        $this->urlStrategyFactory
            ->expects($this->once())
            ->method('create')
            ->with(CategoryUrlInterface::class)
            ->willReturn($this->categoryUrlStrategy);
        $this->categoryUrlStrategy
            ->expects($this->once())
            ->method('getCategoryFilterSelectUrl')
            ->with($this->request, $item)
            ->willReturn('/default/women/tops-women2/facet/');

        $result = $this->subject->getSelectFilter($item);

        $this->assertSame('/default/women/tops-women2/facet/', $result);
    }

    /**
     * @param string $link
     * @return Item
     */
    private function createCategoryItem(string $link): Item
    {
        $settings = new SettingsType(['source' => SettingsType::SOURCE_CATEGORY]);

        $facetType = new FacetType();
        $facetType->setFacetSettings($settings);

        $filter = $this->createMock(Filter::class);
        $filter->method('getFacet')->willReturn($facetType);

        $attribute = $this->createMock(AttributeType::class);
        $attribute->method('getLink')->willReturn($link);

        $item = $this->createMock(Item::class);
        $item->method('getFilter')->willReturn($filter);
        $item->method('getAttribute')->willReturn($attribute);

        return $item;
    }
}

