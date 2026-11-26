<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model\Assistant;

use Emico\CodeCept\Test\Unit;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Mockery;
use Tweakwise\Magento2Tweakwise\Model\Assistant\PageContext;
use Tweakwise\Magento2Tweakwise\Model\Assistant\ResultMapper;
use Tweakwise\Magento2Tweakwise\Model\Client\Response\AssistantResponse;
use Tweakwise\Magento2TweakwiseExport\Model\Helper;

class ResultMapperTest extends Unit
{
    private const CATEGORY_URL = 'https://shop.test/women/tops.html';
    private const SEARCH_URL = 'https://shop.test/catalogsearch/result/';

    /** @var array<int, Category> */
    private array $categories = [];

    public function testSelectedFacetsAreMappedToUrlKeys(): void
    {
        $result = $this->createMapper()->map(
            $this->createResponse([
                ['attribute' => 'color', 'value' => 'Rood|Zwart'],
                ['attribute' => 'Maat', 'value' => 'M'],
                ['attribute' => 'unknown', 'value' => 'x'],
            ]),
            $this->createCategoryContext(),
            ['product_list_order' => 'price', 'product_list_dir' => 'asc', 'p' => '3', 'evil' => 'x']
        );

        $this->assertSame(['kleur' => ['Rood', 'Zwart'], 'maat' => ['M']], $result['filters']);
        $this->assertSame(
            self::CATEGORY_URL . '?kleur[]=Rood&kleur[]=Zwart&maat[]=M&product_list_order=price&product_list_dir=asc',
            $this->decodeBrackets($result['url'])
        );
        $this->assertTrue($result['ajax']);
    }

    /**
     * @dataProvider priceRanges
     */
    public function testPriceRangeIsConvertedToSliderFormat($value, ?string $expected): void
    {
        $result = $this->createMapper()->map(
            $this->createResponse([['attribute' => 'price', 'value' => $value]]),
            $this->createCategoryContext()
        );

        $this->assertSame($expected, $result['filters']['prijs'] ?? null);
    }

    public static function priceRanges(): array
    {
        return [
            'integers' => ['5-50', '5-50'],
            'decimals' => ['5.50 - 49,99', '5.5-49.99'],
            'reversed' => ['50-5', '5-50'],
            'array' => [[10, 20], '10-20'],
            'invalid' => ['cheap', null],
        ];
    }

    public function testCategoryFacetNavigatesToTheMagentoCategory(): void
    {
        $this->addCategory(15, '1/2/15', true, 'https://shop.test/men.html');

        $result = $this->createMapper()->map(
            $this->createResponse([
                ['attribute' => 'categorie', 'value' => 'Men'],
                ['attribute' => 'kleur', 'value' => 'Rood'],
            ]),
            $this->createCategoryContext()
        );

        $this->assertSame('https://shop.test/men.html?kleur[]=Rood', $this->decodeBrackets($result['url']));
        $this->assertArrayNotHasKey('categorie', $result['filters']);
        $this->assertFalse($result['ajax']);
    }

    public function testCategoryFacetAcceptsTheTweakwiseId(): void
    {
        $this->addCategory(15, '1/2/15', true, 'https://shop.test/men.html');

        $result = $this->createMapper()->map(
            $this->createResponse([['attribute' => 'categorie', 'value' => '1000115']]),
            $this->createCategoryContext()
        );

        $this->assertSame('https://shop.test/men.html', $result['url']);
    }

    /**
     * @dataProvider invisibleCategories
     */
    public function testUnknownOrInvisibleCategoryIsIgnored(string $value, ?array $category): void
    {
        if ($category !== null) {
            $this->addCategory(...$category);
        }

        $result = $this->createMapper()->map(
            $this->createResponse([['attribute' => 'categorie', 'value' => $value]]),
            $this->createCategoryContext()
        );

        $this->assertSame(self::CATEGORY_URL, $result['url']);
        $this->assertSame([], $result['filters']);
        $this->assertTrue($result['ajax']);
    }

    public static function invisibleCategories(): array
    {
        return [
            'unknown' => ['1000199', null],
            'inactive' => ['1000115', [15, '1/2/15', false, 'https://shop.test/men.html']],
            'other store root' => ['1000115', [15, '1/3/15', true, 'https://shop.test/men.html']],
            'other store id' => ['1000215', [15, '1/2/15', true, 'https://shop.test/men.html']],
            'unknown title' => ['Kids', null],
        ];
    }

    public function testSearchPageUsesTheReturnedSearchTerm(): void
    {
        $result = $this->createMapper()->map(
            $this->createResponse([['attribute' => 'kleur', 'value' => 'Rood']], 'red shoes'),
            new PageContext(PageContext::TYPE_SEARCH, 2, self::SEARCH_URL, 'shoes')
        );

        $this->assertSame(self::SEARCH_URL . '?q=red%20shoes&kleur[]=Rood', $this->decodeBrackets($result['url']));
        $this->assertSame(['q' => 'red shoes', 'kleur' => ['Rood']], $result['filters']);
        $this->assertTrue($result['ajax']);
    }

    public function testSearchPageKeepsTheCurrentSearchTermWithoutReturnedTerm(): void
    {
        $result = $this->createMapper()->map(
            $this->createResponse([]),
            new PageContext(PageContext::TYPE_SEARCH, 2, self::SEARCH_URL, 'shoes')
        );

        $this->assertSame(self::SEARCH_URL . '?q=shoes', $result['url']);
    }

    /**
     * Like the demoshop the assistant searches within the current category instead of the whole catalog.
     */
    public function testSearchTermOnCategoryPageSearchesWithinTheCategory(): void
    {
        $result = $this->createMapper()->map(
            $this->createResponse([['attribute' => 'kleur', 'value' => 'Rood']], 'red shoes'),
            $this->createCategoryContext()
        );

        $this->assertSame(self::CATEGORY_URL . '?q=red%20shoes&kleur[]=Rood', $this->decodeBrackets($result['url']));
        $this->assertSame(['q' => 'red shoes', 'kleur' => ['Rood']], $result['filters']);
        $this->assertTrue($result['ajax']);
    }

    public function testCategoryPageWithoutSearchTermDropsThePreviousSearchTerm(): void
    {
        $result = $this->createMapper()->map(
            $this->createResponse([['attribute' => 'kleur', 'value' => 'Rood']], '  '),
            new PageContext(PageContext::TYPE_CATEGORY, 12, self::CATEGORY_URL, 'red shoes')
        );

        $this->assertSame(self::CATEGORY_URL . '?kleur[]=Rood', $this->decodeBrackets($result['url']));
        $this->assertSame(['kleur' => ['Rood']], $result['filters']);
        $this->assertTrue($result['ajax']);
    }

    public function testSearchTermIsKeptWhenTheCategoryFacetSelectsAnotherCategory(): void
    {
        $this->addCategory(15, '1/2/15', true, 'https://shop.test/men.html');

        $result = $this->createMapper()->map(
            $this->createResponse([['attribute' => 'categorie', 'value' => 'Men']], 'red shoes'),
            $this->createCategoryContext()
        );

        $this->assertSame('https://shop.test/men.html?q=red%20shoes', $result['url']);
        $this->assertFalse($result['ajax']);
    }

    public function testNavigationSelectionAndSearchTermAreUsedLikeTheDemoshop(): void
    {
        $this->addCategory(12, '1/2/12', true, self::CATEGORY_URL);

        $result = $this->createMapper()->map(
            $this->createNavigationResponse(
                [
                    $this->facet('kleur', 'color', 'checkbox', [['title' => 'Purple', 'isselected' => true]]),
                    $this->facet('prijs', 'price', 'slider', [
                        ['title' => '30', 'isselected' => true],
                        ['title' => '60', 'isselected' => true],
                        ['title' => '0', 'isselected' => false],
                        ['title' => '99', 'isselected' => false],
                    ]),
                    $this->facet('maat', 'size', 'checkbox', [
                        ['title' => 'M', 'isselected' => 'true'],
                        ['title' => 'L', 'isselected' => false],
                    ]),
                    $this->facet('categorie', 'categorie', 'link', [[
                        'title' => 'Women',
                        'isselected' => true,
                        'attributeid' => 1000112,
                        'children' => [['title' => 'Tops', 'isselected' => true, 'attributeid' => 1000112]],
                    ]], 'CATEGORY'),
                ],
                'purple'
            ),
            $this->createCategoryContext()
        );

        $this->assertSame(
            ['q' => 'purple', 'kleur' => ['Purple'], 'prijs' => '30-60', 'maat' => ['M']],
            $result['filters']
        );
        $this->assertSame(
            self::CATEGORY_URL . '?q=purple&kleur[]=Purple&prijs=30-60&maat[]=M',
            $this->decodeBrackets($result['url'])
        );
        $this->assertTrue($result['ajax']);
    }

    public function testGroupedNavigationIsUnwrapped(): void
    {
        $response = new AssistantResponse([
            'messages' => [],
            'navigation' => ['grouped' => [
                'facets' => [$this->facet('kleur', 'color', 'checkbox', [['title' => 'Purple', 'isselected' => true]])],
                'groups' => [],
                'properties' => ['searchterm' => 'purple'],
            ]],
            'history' => [],
            'selectedFacets' => [],
            'searchTerm' => null,
        ]);

        $result = $this->createMapper()->map($response, $this->createCategoryContext());

        $this->assertSame(['q' => 'purple', 'kleur' => ['Purple']], $result['filters']);
    }

    public function testExplicitSelectedFacetsOverrideTheNavigationSelection(): void
    {
        $response = new AssistantResponse([
            'messages' => [],
            'navigation' => ['facets' => [
                $this->facet('kleur', 'color', 'checkbox', [['title' => 'Purple', 'isselected' => true]]),
            ], 'properties' => ['searchterm' => 'purple']],
            'history' => [],
            'selectedFacets' => [['attribute' => 'color', 'value' => 'Paars']],
            'searchTerm' => 'paars',
        ]);

        $result = $this->createMapper()->map($response, $this->createCategoryContext());

        $this->assertSame(['q' => 'paars', 'kleur' => ['Paars']], $result['filters']);
    }

    private function createCategoryContext(): PageContext
    {
        return new PageContext(PageContext::TYPE_CATEGORY, 12, self::CATEGORY_URL);
    }

    private function createNavigationResponse(array $facets, ?string $searchTerm): AssistantResponse
    {
        return new AssistantResponse([
            'messages' => [],
            'navigation' => ['facets' => $facets, 'properties' => ['searchterm' => $searchTerm]],
            'history' => [],
            'selectedFacets' => [],
            'searchTerm' => null,
        ]);
    }

    private function facet(
        string $urlKey,
        string $attributeName,
        string $selectionType,
        array $attributes,
        string $source = 'FEED'
    ): array {
        return [
            'facetsettings' => [
                'urlkey' => $urlKey,
                'attributename' => $attributeName,
                'source' => $source,
                'selectiontype' => $selectionType,
            ],
            'attributes' => $attributes,
        ];
    }

    private function createResponse(array $selectedFacets, ?string $searchTerm = null): AssistantResponse
    {
        $fixture = json_decode((string)file_get_contents(codecept_data_dir('assistant/success.json')), true);
        $navigation = $fixture['navigation'];
        // Only the explicit selected facets are under test here
        $navigation['facets'][0]['attributes'][0]['isselected'] = false;
        $navigation['facets'][] = [
            'facetsettings' => [
                'urlkey' => 'maat',
                'attributename' => 'size',
                'source' => 'FEED',
                'selectiontype' => 'checkbox',
            ],
            'attributes' => [],
        ];
        $navigation['facets'][2]['attributes'] = [
            [
                'title' => 'Women',
                'attributeid' => '1000112',
                'children' => [['title' => 'Men', 'attributeid' => '1000115']],
            ],
        ];

        return new AssistantResponse(
            [
                'messages' => [],
                'navigation' => $navigation,
                'history' => [],
                'selectedFacets' => $selectedFacets,
                'searchTerm' => $searchTerm,
            ]
        );
    }

    private function addCategory(int $id, string $path, bool $active, string $url): void
    {
        $category = Mockery::mock(Category::class);
        $category->shouldReceive('getId')->andReturn($id);
        $category->shouldReceive('getPath')->andReturn($path);
        $category->shouldReceive('getIsActive')->andReturn($active);
        $category->shouldReceive('getUrl')->andReturn($url);
        $this->categories[$id] = $category;
    }

    private function createMapper(): ResultMapper
    {
        $store = Mockery::mock(Store::class);
        $store->shouldReceive('getId')->andReturn(1);
        $store->shouldReceive('getRootCategoryId')->andReturn(2);
        $storeManager = Mockery::mock(StoreManagerInterface::class);
        $storeManager->shouldReceive('getStore')->andReturn($store);

        $repository = Mockery::mock(CategoryRepositoryInterface::class);
        $repository->shouldReceive('get')->andReturnUsing(function (int $id) {
            if (!isset($this->categories[$id])) {
                throw new NoSuchEntityException();
            }

            return $this->categories[$id];
        });

        $url = Mockery::mock(UrlInterface::class);
        $url->shouldReceive('getUrl')->with('catalogsearch/result')->andReturn(self::SEARCH_URL);

        $helper = Mockery::mock(Helper::class);
        $helper->shouldReceive('getStoreId')->andReturnUsing(static fn (int $id): int => (int)substr((string)$id, 5));
        $helper->shouldReceive('getTweakwiseId')->andReturnUsing(
            static fn (int $storeId, int $id): string => '1' . str_pad((string)$storeId, 4, '0', STR_PAD_LEFT) . $id
        );

        return new ResultMapper($repository, $storeManager, $url, $helper);
    }

    private function decodeBrackets(string $url): string
    {
        return str_replace(['%5B', '%5D'], ['[', ']'], $url);
    }
}
