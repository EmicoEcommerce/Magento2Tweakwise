<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model\Seo;

use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\Catalog\Model\Layer\State;
use Magento\Framework\Api\AttributeValue;
use Magento\Framework\View\Page\Config as PageConfig;
use PHPUnit\Framework\TestCase;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Filter;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Filter\Item;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\FilterList\Tweakwise;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\AttributeType;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\FacetType;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\FacetType\SettingsType;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2Tweakwise\Model\ConfigAttributeProcessService;
use Tweakwise\Magento2Tweakwise\Model\Seo\FilterHelper;
use Tweakwise\Magento2Tweakwise\Model\Seo\Robots\Plugin;

class FilterHelperTest extends TestCase
{
    /**
     * @dataProvider whitelistProvider
     */
    public function testIndexabilityIgnoresAttributeAndValueCase(
        string $attributeCode,
        ?string $value,
        string $whitelist,
        string $valuesWhitelist,
        bool $categoryOverride,
        bool $expected,
        int $maxFacets = 1,
        bool $seoEnabled = true
    ): void {
        $settings = new SettingsType(['urlkey' => $attributeCode, 'source' => SettingsType::SOURCE_FEED]);
        $facet = new FacetType();
        $facet->setFacetSettings($settings);
        $filter = $this->createMock(Filter::class);
        $filter->method('getFacet')->willReturn($facet);
        $item = $this->createMock(Item::class);
        $item->method('getFilter')->willReturn($filter);
        $item->method('getAttribute')->willReturn(new AttributeType(['title' => $value]));

        $category = $this->createMock(Category::class);
        $category->method('getCustomAttribute')->willReturnMap([
            [
                Config::ATTRIBUTE_FILTER_WHITELIST,
                $categoryOverride ? new AttributeValue(['value' => $whitelist]) : null,
            ],
            [
                Config::ATTRIBUTE_FILTER_VALUES_WHITELIST,
                $categoryOverride ? new AttributeValue(['value' => $valuesWhitelist]) : null,
            ],
        ]);
        $state = $this->createMock(State::class);
        $state->method('getFilters')->willReturn([$item]);
        $layer = $this->createMock(Layer::class);
        $layer->method('getState')->willReturn($state);
        $layer->method('getCurrentCategory')->willReturn($category);
        $resolver = $this->createMock(Resolver::class);
        $resolver->method('get')->willReturn($layer);

        $config = $this->createMock(Config::class);
        $config->method('isSeoEnabled')->willReturn($seoEnabled);
        $config->method('getMaxAllowedFacets')->willReturn($maxFacets);
        $config->method('getFilterWhitelist')->willReturn(
            ConfigAttributeProcessService::extractFilterWhitelist($categoryOverride ? 'other' : $whitelist)
        );
        $config->method('getFilterValuesWhitelist')->willReturn(
            ConfigAttributeProcessService::extractFilterValuesWhitelist(
                $categoryOverride ? 'other=value' : $valuesWhitelist
            )
        );
        $helper = new FilterHelper($resolver, $this->createMock(Tweakwise::class), $config);

        $this->assertSame($expected, $helper->shouldFilterBeIndexable($item));
        $this->assertSame($expected, $helper->shouldPageBeIndexable());
        $plugin = new Plugin($helper, $config);
        $pageConfig = $this->createMock(PageConfig::class);
        $this->assertSame(
            $expected ? 'INDEX,FOLLOW' : 'NOINDEX,FOLLOW',
            $plugin->afterGetRobots($pageConfig, 'INDEX,FOLLOW')
        );
        $this->assertSame('NOINDEX,NOFOLLOW', $plugin->afterGetRobots($pageConfig, 'NOINDEX,NOFOLLOW'));
    }

    public static function whitelistProvider(): iterable
    {
        foreach ([false, true] as $categoryOverride) {
            $scope = $categoryOverride ? 'category' : 'store';
            yield "$scope mixed-case configured code" =>
                ['gepeld_ongepeld', 'Gepeld', 'Gepeld_ongepeld', '', $categoryOverride, true];
            yield "$scope mixed-case facet code" =>
                ['Gepeld_ongepeld', 'Gepeld', 'gepeld_ongepeld', '', $categoryOverride, true];
            yield "$scope mixed-case code and value" =>
                ['GEPELD_ONGEPELD', 'gEpElD', 'Gepeld_ongepeld', 'Gepeld_ongepeld=GEPELD', $categoryOverride, true];
            yield "$scope non-whitelisted attribute" =>
                ['other', 'Gepeld', 'Gepeld_ongepeld', '', $categoryOverride, false];
            yield "$scope non-whitelisted value" =>
                ['gepeld_ongepeld', 'Ongepeld', 'Gepeld_ongepeld', 'GEPELD_ONGEPELD=Gepeld', $categoryOverride, false];
            yield "$scope merged value restrictions" =>
                [
                    'gepeld_ongepeld',
                    'Gepeld',
                    'Gepeld_ongepeld',
                    'Gepeld_ongepeld=Gepeld,GEPELD_ONGEPELD=Ongepeld',
                    $categoryOverride,
                    true,
                ];
            yield "$scope missing value" =>
                ['gepeld_ongepeld', null, 'Gepeld_ongepeld', 'Gepeld_ongepeld=Gepeld', $categoryOverride, false];
            yield "$scope no value restriction for attribute" =>
                ['gepeld_ongepeld', 'Gepeld', 'Gepeld_ongepeld', 'other=value', $categoryOverride, true];
            yield "$scope empty whitelist" =>
                ['gepeld_ongepeld', 'Gepeld', '', '', $categoryOverride, false];
            yield "$scope facet limit exceeded" =>
                ['gepeld_ongepeld', 'Gepeld', 'Gepeld_ongepeld', '', $categoryOverride, false, 0];
            yield "$scope SEO disabled" =>
                ['gepeld_ongepeld', 'Gepeld', '', '', $categoryOverride, true, 0, false];
        }
    }
}
