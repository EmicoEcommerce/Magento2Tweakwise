<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Observer;

use Codeception\Attribute\DataProvider;
use Emico\CodeCept\Test\Unit;
use Magento\Framework\Event\Observer;
use Magento\Framework\View\LayoutInterface;
use Mockery;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2Tweakwise\Observer\RemoveEsiNavigation;

/**
 * @see RemoveEsiNavigation
 */
class RemoveEsiNavigationTest extends Unit
{
    #[DataProvider('enabledFeatureProvider')]
    public function testEsiExcludesNavigationWhenTweakwiseIsEnabled(bool $layeredEnabled, bool $searchEnabled): void
    {
        $config = Mockery::mock(Config::class, [
            'isLayeredEnabled' => $layeredEnabled,
            'isSearchEnabled' => $searchEnabled,
        ]);
        $layout = Mockery::mock(LayoutInterface::class);
        $layout->shouldReceive('getUpdate->addHandle')->once()->with('tweakwise_esi');

        (new RemoveEsiNavigation($config))->execute(new Observer([
            'full_action_name' => 'magento_pagecache_block_esi',
            'layout' => $layout,
        ]));
    }

    public function testDisabledTweakwiseDoesNotChangeEsiLayout(): void
    {
        $config = Mockery::mock(Config::class, ['isLayeredEnabled' => false, 'isSearchEnabled' => false]);
        $layout = Mockery::mock(LayoutInterface::class);
        $layout->shouldNotReceive('getUpdate');

        (new RemoveEsiNavigation($config))->execute(new Observer([
            'full_action_name' => 'magento_pagecache_block_esi',
            'layout' => $layout,
        ]));
    }

    #[DataProvider('normalActionProvider')]
    public function testNormalPagesKeepNavigation(string $actionName): void
    {
        $config = Mockery::mock(Config::class);
        $layout = Mockery::mock(LayoutInterface::class);
        $layout->shouldNotReceive('getUpdate');

        (new RemoveEsiNavigation($config))->execute(new Observer([
            'full_action_name' => $actionName,
            'layout' => $layout,
        ]));
    }

    public static function enabledFeatureProvider(): array
    {
        return [
            'layered navigation' => [true, false],
            'search' => [false, true],
            'both features' => [true, true],
        ];
    }

    public static function normalActionProvider(): array
    {
        return [
            'category page' => ['catalog_category_view'],
            'search page' => ['catalogsearch_result_index'],
            'AJAX navigation' => ['tweakwise_ajax_navigation'],
            'AJAX search' => ['tweakwise_ajax_search'],
        ];
    }
}
