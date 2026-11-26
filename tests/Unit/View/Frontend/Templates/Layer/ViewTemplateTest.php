<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\View\Frontend\Templates\Layer;

use DOMDocument;
use DOMXPath;
use Emico\CodeCept\Test\Unit;
use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\Layer\State;
use Magento\Framework\Escaper;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\TemplateEngine\Php;
use Magento\LayeredNavigation\Block\Navigation;
use Mockery;
use Mockery\MockInterface;
use ReflectionClass;
use Tweakwise\Magento2Tweakwise\Model\NavigationConfig;

class ViewTemplateTest extends Unit
{
    // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    public function _after(): void
    {
        Mockery::close();
    }

    /**
     * An assistant answer without results hides the filters, the assistant must stay available
     */
    public function testAssistantIsRenderedWhenTheFiltersAreHidden(): void
    {
        $block = $this->createBlock('<section class="tw-assistant-facet"></section>');
        $block->shouldNotReceive('getFilters');

        $xpath = $this->parse($this->render($block));

        $this->assertSame(1, $xpath->query('//*[@id="layered-filter-block"]//section[@class="tw-assistant-facet"]')->length);
        $this->assertSame(1, $xpath->query('//form[@id="facet-filter-form"]')->length);
        $this->assertSame(0, $xpath->query('//*[@id="narrow-by-list"]')->length);
    }

    public function testNothingIsRenderedWhenTheFiltersAndTheAssistantAreHidden(): void
    {
        $this->assertSame('', trim($this->render($this->createBlock(''))));
    }

    private function createBlock(string $assistantHtml): Navigation&MockInterface
    {
        $state = Mockery::mock(State::class);
        $state->shouldReceive('getFilters')->andReturn([]);
        $layer = Mockery::mock(Layer::class);
        $layer->shouldReceive('getState')->andReturn($state);

        $navigationConfig = Mockery::mock(NavigationConfig::class);
        $navigationConfig->shouldReceive('getJsFormConfig')->andReturn('{}');
        $navigationConfig->shouldReceive('isFormFilters')->andReturn(false);
        $navigationConfig->shouldReceive('getFilterFormInput')->andReturn([]);

        $block = Mockery::mock(Navigation::class);
        $block->shouldReceive('canShowBlock')->andReturn(false);
        $block->shouldReceive('getTemplateFile')->andReturn('assistant.phtml');
        $block->shouldReceive('fetchView')->with('assistant.phtml')->andReturn($assistantHtml);
        $block->shouldReceive('getData')->with('tweakwise_navigation_config')->andReturn($navigationConfig);
        $block->shouldReceive('getLayer')->andReturn($layer);
        $block->shouldReceive('getChildHtml')->andReturn('');

        return $block;
    }

    private function parse(string $html): DOMXPath
    {
        $document = new DOMDocument();
        $document->loadHTML($html, LIBXML_NOERROR);

        return new DOMXPath($document);
    }

    private function render(Navigation $block): string
    {
        $templateFile = dirname((string)(new ReflectionClass(NavigationConfig::class))->getFileName(), 2)
            . '/view/frontend/templates/layer/view.phtml';
        $engine = new Php(Mockery::mock(ObjectManagerInterface::class));

        return $engine->render($block, $templateFile, ['block' => $block, 'escaper' => new Escaper()]);
    }
}
