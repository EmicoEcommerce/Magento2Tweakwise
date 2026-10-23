<?php

declare(strict_types=1);

namespace Tweakwise\Test\Functional;

use Closure;
use Emico\CodeCept\Models\Fixtures\ProductFixture;
use Emico\CodeCept\Test\Unit;
use Magento\Catalog\Block\Product\ProductList\Upsell;
use Magento\Checkout\Block\Cart\Crosssell;
use Mockery;
use Tweakwise\Magento2Tweakwise\Block\Cart\Crosssell\Plugin as CartPlugin;
use Tweakwise\Magento2Tweakwise\Block\Catalog\Product\ProductList\Featured;
use Tweakwise\Magento2Tweakwise\Block\Catalog\Product\ProductList\Upsell\Plugin as UpsellPlugin;
use Tweakwise\Magento2Tweakwise\Block\Catalog\Product\Widget\FeaturedProducts;
use Tweakwise\Magento2Tweakwise\Block\Product\ProductList\Linked;
use Tweakwise\Test\Support\FunctionalTester;
use Tweakwise\Test\Support\RecommendationFixtures;

/**
 * Follows the recommendations from the Tweakwise response to the items a block hands over to its template.
 */
class RecommendationChainTest extends Unit
{
    use RecommendationFixtures;

    private const VISUAL_IMAGE_A = 'https://example.com/media/wysiwyg/a.jpg';
    private const MODULE_ITEMS_TEMPLATE = 'Tweakwise_Magento2Tweakwise::product/list/items.phtml';
    private const MAGENTO_ITEMS_TEMPLATE = 'Magento_Catalog::product/list/items.phtml';

    protected FunctionalTester $tester;

    private ProductFixture $current;

    private ProductFixture $first;

    private ProductFixture $second;

    private ProductFixture $third;

    // phpcs:disable PSR2.Methods.MethodDeclaration.Underscore
    /**
     * @return void
     */
    public function _before(): void
    {
        $this->current = $this->createProduct('tweakwise-chain-current');
        $this->first = $this->createProduct('tweakwise-chain-first');
        $this->second = $this->createProduct('tweakwise-chain-second');
        $this->third = $this->createProduct('tweakwise-chain-third');

        $this->enableRecommendations();
    }

    /**
     * @return void
     */
    public function testUpsellKeepsTheVisualAfterTheProductsThatPrecedeItWithoutGroupedProducts(): void
    {
        $this->tester->mockConfig('tweakwise/general/grouped_products', '0');
        $this->mockRecommendations([
            $this->productItem($this->first),
            $this->productItem($this->second),
            $this->visualItem('visual-a'),
            $this->productItem($this->third),
        ]);

        $this->assertSame(
            [$this->first->getSku(), $this->second->getSku(), 'V', $this->third->getSku()],
            $this->describe($this->getUpsellItems())
        );
    }

    /**
     * With grouped products every product has two response items (the group and the variant), the visual
     * still belongs after the two products in front of it.
     *
     * @return void
     */
    public function testUpsellKeepsTheVisualAfterTheProductsThatPrecedeItWithGroupedProducts(): void
    {
        $this->tester->mockConfig('tweakwise/general/grouped_products', '1');
        $this->mockRecommendations([
            $this->productItem($this->first, true),
            $this->productItem($this->second, true),
            $this->visualItem('visual-a'),
            $this->productItem($this->third, true),
        ]);

        $this->assertSame(
            [$this->first->getSku(), $this->second->getSku(), 'V', $this->third->getSku()],
            $this->describe($this->getUpsellItems())
        );
    }

    /**
     * @return void
     */
    public function testMergedRecommendationsHandOverEveryVisual(): void
    {
        $this->tester->mockConfig('tweakwise/general/grouped_products', '0');
        $response = $this->mockRecommendations([]);
        $response->setRecommendation([
            ['items' => ['item' => [$this->visualItem('banner-pillows'), $this->productItem($this->first)]]],
            ['items' => ['item' => [$this->visualItem('banner-freeshipping'), $this->productItem($this->second)]]],
        ]);

        $this->assertSame(
            ['V', $this->first->getSku(), 'V', $this->second->getSku()],
            $this->describe($this->getUpsellItems())
        );
    }

    /**
     * @return void
     */
    public function testCartCrosssellHandsOverTheVisualAndLeavesOutProductsThatAreInTheCart(): void
    {
        $this->tester->mockConfig('tweakwise/general/grouped_products', '0');
        $this->mockRecommendations([
            $this->visualItem('visual-a'),
            $this->productItem($this->first),
            $this->productItem($this->second),
        ]);

        $crosssell = Mockery::mock(Crosssell::class);
        $crosssell->shouldReceive('getData')->with('_cart_product_ids')->andReturn([(int)$this->first->getId()]);
        $crosssell->shouldReceive('setData')->with('items', Mockery::type('array'));

        /** @var CartPlugin $plugin */
        $plugin = $this->tester->getObjectManager()->get(CartPlugin::class);
        $plugin->aroundGetItems($crosssell, fn () => []);

        $this->assertSame(['V', $this->second->getSku()], $this->describe($plugin->afterGetItems($crosssell, [])));
    }

    /**
     * @return void
     */
    public function testFeaturedBlockHandsOverTheVisualOnItsPosition(): void
    {
        $this->tester->mockConfig('tweakwise/general/grouped_products', '0');
        $this->mockRecommendations([
            $this->productItem($this->first),
            $this->visualItem('visual-a'),
            $this->productItem($this->second),
        ]);

        /** @var Featured $featured */
        $featured = $this->tester->getObjectManager()->create(Featured::class);

        $this->assertSame(
            [$this->first->getSku(), 'V', $this->second->getSku()],
            $this->describe($featured->getProductCollection())
        );
    }

    /**
     * @return void
     */
    public function testFeaturedWidgetHandsOverTheVisualOnItsPosition(): void
    {
        $this->tester->mockConfig('tweakwise/general/grouped_products', '0');
        $this->mockRecommendations([
            $this->visualItem('visual-a'),
            $this->productItem($this->first),
            $this->productItem($this->second),
        ]);

        /** @var FeaturedProducts $widget */
        $widget = $this->tester->getObjectManager()->create(FeaturedProducts::class, ['data' => ['rule_id' => '10']]);

        $this->assertSame(
            ['V', $this->first->getSku(), $this->second->getSku()],
            $this->describe($widget->getItems())
        );
    }

    /**
     * @return void
     */
    public function testUpsellFromXmlWithGroupedProductsKeepsTheVisualOnItsPosition(): void
    {
        $this->tester->mockConfig('tweakwise/general/grouped_products', '1');
        $this->mockRecommendationsResponse($this->parseXmlFixture(
            'upsell-grouped-with-visual.xml',
            [$this->first, $this->second, $this->third]
        ));

        $this->assertSame(
            [$this->first->getSku(), $this->second->getSku(), 'V', $this->third->getSku()],
            $this->describe($this->getUpsellItems())
        );
    }

    /**
     * @return void
     */
    public function testUpsellFromXmlWithoutGroupedProductsKeepsTheVisualOnItsPosition(): void
    {
        $this->tester->mockConfig('tweakwise/general/grouped_products', '0');
        $this->mockRecommendationsResponse($this->parseXmlFixture(
            'upsell-ungrouped-visual-between-products.xml',
            [$this->first, $this->second, $this->third]
        ));

        $this->assertSame(
            [$this->first->getSku(), 'V', $this->second->getSku(), $this->third->getSku()],
            $this->describe($this->getUpsellItems())
        );
    }

    /**
     * An empty url element is read as an empty array, that must not break the visual.
     *
     * @return void
     */
    public function testUpsellFromXmlHandsOverAVisualWithoutUrl(): void
    {
        $this->tester->mockConfig('tweakwise/general/grouped_products', '0');
        $this->mockRecommendationsResponse($this->parseXmlFixture(
            'upsell-visual-without-url.xml',
            [$this->first, $this->second]
        ));

        $items = array_values($this->getUpsellItems());

        $this->assertSame(['V', $this->first->getSku(), $this->second->getSku()], $this->describe($items));
        $this->assertSame('', $items[0]->getUrl());
        $this->assertSame(self::VISUAL_IMAGE_A, $items[0]->getImageUrl());
    }

    /**
     * @return void
     */
    public function testUpsellFromXmlKeepsEveryVisual(): void
    {
        $this->tester->mockConfig('tweakwise/general/grouped_products', '0');
        $this->mockRecommendationsResponse($this->parseXmlFixture(
            'upsell-two-visuals.xml',
            [$this->first, $this->second]
        ));

        $this->assertSame(
            ['V', $this->first->getSku(), 'V', $this->second->getSku()],
            $this->describe($this->getUpsellItems())
        );
    }

    /**
     * @return void
     */
    public function testCartCrosssellFromXmlKeepsTheVisualAndLeavesOutProductsInTheCart(): void
    {
        $this->tester->mockConfig('tweakwise/general/grouped_products', '0');
        $this->mockRecommendationsResponse($this->parseXmlFixture(
            'upsell-ungrouped-visual-between-products.xml',
            [$this->first, $this->second, $this->third]
        ));

        $crosssell = Mockery::mock(Crosssell::class);
        $crosssell->shouldReceive('getData')->with('_cart_product_ids')->andReturn([(int)$this->second->getId()]);
        $crosssell->shouldReceive('setData')->with('items', Mockery::type('array'));

        /** @var CartPlugin $plugin */
        $plugin = $this->tester->getObjectManager()->get(CartPlugin::class);
        $plugin->aroundGetItems($crosssell, fn () => []);

        $this->assertSame(
            [$this->first->getSku(), 'V', $this->third->getSku()],
            $this->describe($plugin->afterGetItems($crosssell, []))
        );
    }

    /**
     * @return void
     */
    public function testFeaturedFromXmlKeepsTheVisualOnItsPosition(): void
    {
        $this->tester->mockConfig('tweakwise/general/grouped_products', '0');
        $this->mockRecommendationsResponse($this->parseXmlFixture(
            'upsell-ungrouped-visual-between-products.xml',
            [$this->first, $this->second, $this->third]
        ));

        $this->assertSame(
            [$this->first->getSku(), 'V', $this->second->getSku(), $this->third->getSku()],
            $this->describe($this->createFeatured()->getProductCollection())
        );
    }

    /**
     * @return void
     */
    public function testTheModuleTemplateIsUsedForEveryBlockWhenVisualsAreEnabled(): void
    {
        $this->assertSame(self::MODULE_ITEMS_TEMPLATE, $this->getLinkedTemplate());
        $this->assertSame(self::MODULE_ITEMS_TEMPLATE, $this->getCartTemplate());
        $this->assertSame(self::MODULE_ITEMS_TEMPLATE, $this->createFeatured()->getTemplate());
    }

    /**
     * @return void
     */
    public function testMagentoTemplatesAreKeptWhenVisualsAndPersonalMerchandisingAreDisabled(): void
    {
        $this->tester->mockConfig('tweakwise/merchandising_builder/visuals/enabled', '0');

        $this->assertNull($this->getLinkedTemplate());
        $this->assertSame(self::MAGENTO_ITEMS_TEMPLATE, $this->getCartTemplate());

        $featured = $this->createFeatured();
        $featured->setTemplate(self::MAGENTO_ITEMS_TEMPLATE);
        $this->assertSame(self::MAGENTO_ITEMS_TEMPLATE, $featured->getTemplate());
    }

    /**
     * @return array
     */
    private function getUpsellItems(): array
    {
        $subject = Mockery::mock(Upsell::class);
        $subject->shouldReceive('getProduct')->andReturn($this->registerCurrentProduct($this->loadProduct($this->current)));

        /** @var UpsellPlugin $plugin */
        $plugin = $this->tester->getObjectManager()->get(UpsellPlugin::class);

        return $plugin->aroundGetItemCollection($subject, $this->proceedShouldNotBeCalled())->getItems();
    }

    /**
     * @return Closure
     */
    private function proceedShouldNotBeCalled(): Closure
    {
        return function () {
            $this->fail('Magento native items were used instead of the Tweakwise recommendations');
        };
    }

    /**
     * @return string|null
     */
    private function getLinkedTemplate(): ?string
    {
        return $this->tester->getObjectManager()->get(Linked::class)->getTemplate();
    }

    /**
     * @return string|null
     */
    private function getCartTemplate(): ?string
    {
        /** @var CartPlugin $plugin */
        $plugin = $this->tester->getObjectManager()->get(CartPlugin::class);

        return $plugin->afterGetTemplate(Mockery::mock(Crosssell::class), self::MAGENTO_ITEMS_TEMPLATE);
    }

    /**
     * @return Featured
     */
    private function createFeatured(): Featured
    {
        return $this->tester->getObjectManager()->create(Featured::class);
    }
}
