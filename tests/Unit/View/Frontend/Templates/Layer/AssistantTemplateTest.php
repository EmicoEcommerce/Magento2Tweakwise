<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\View\Frontend\Templates\Layer;

use DOMDocument;
use DOMXPath;
use Emico\CodeCept\Test\Unit;
use Magento\Framework\Escaper;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\View\TemplateEngine\Php;
use Magento\LayeredNavigation\Block\Navigation;
use Mockery;
use ReflectionClass;
use Tweakwise\Magento2Tweakwise\ViewModel\Assistant;

class AssistantTemplateTest extends Unit
{
    // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    public function _after(): void
    {
        Mockery::close();
    }

    public function testJsConfigCannotBreakOutOfTheAttribute(): void
    {
        $jsConfig = json_encode(['searchTerm' => "x' autofocus onfocus='alert(1)", 'label' => 'a & "b"']);
        $viewModel = Mockery::mock(Assistant::class);
        $viewModel->shouldReceive('isAvailable')->andReturn(true);
        $viewModel->shouldReceive('getUnavailableReason')->andReturn(null);
        $viewModel->shouldReceive('getJsConfig')->andReturn($jsConfig);

        $block = Mockery::mock(Navigation::class);
        $block->shouldReceive('getData')->with('tweakwise_assistant_view_model')->andReturn($viewModel);

        $document = new DOMDocument();
        $document->loadHTML($this->render($block), LIBXML_NOERROR);
        $section = (new DOMXPath($document))->query('//section[contains(@class, "tw-assistant-facet")]')->item(0);

        $this->assertNotNull($section);
        $this->assertFalse($section->hasAttribute('autofocus'));
        $this->assertFalse($section->hasAttribute('onfocus'));
        $this->assertSame($jsConfig, $section->getAttribute('data-mage-init'));
    }

    private function render(Navigation $block): string
    {
        $templateFile = dirname((string)(new ReflectionClass(Assistant::class))->getFileName(), 2)
            . '/view/frontend/templates/layer/assistant.phtml';
        $engine = new Php(Mockery::mock(ObjectManagerInterface::class));

        return $engine->render($block, $templateFile, ['block' => $block, 'escaper' => new Escaper()]);
    }
}
