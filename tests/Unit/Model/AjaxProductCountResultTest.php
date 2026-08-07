<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model;

use Emico\CodeCept\Test\Unit;
use Magento\Framework\App\Response\HttpInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Mockery;
use Mockery\MockInterface;
use Tweakwise\Magento2Tweakwise\Model\AjaxProductCountResult;
use Tweakwise\Test\Support\UnitTester;

class AjaxProductCountResultTest extends Unit
{
    protected UnitTester $tester;

    private Json&MockInterface $serializer;

    /** @var array<int, array{0: string, 1: string, 2: bool}> */
    private array $headers = [];

    public function _before(): void
    {
        $this->serializer = Mockery::mock(Json::class);
        $this->headers = [];
    }

    public function _after(): void
    {
        Mockery::close();
    }

    public function testRenderWritesProductCountJson(): void
    {
        $response = Mockery::mock(HttpInterface::class);
        $response->shouldReceive('setHeader')
            ->twice()
            ->andReturnUsing(function (string $name, string $value, bool $replace): void {
                $this->headers[] = [$name, $value, $replace];
            });
        $response->shouldReceive('appendBody')
            ->once()
            ->with('{"product_count":42}');

        $this->serializer->shouldReceive('serialize')
            ->once()
            ->with(['product_count' => 42])
            ->andReturn('{"product_count":42}');

        $subject = new class ($this->serializer) extends AjaxProductCountResult {
            public function renderPublic(HttpInterface $response): static
            {
                return $this->render($response);
            }
        };

        $subject->setCount(42);

        $this->assertSame($subject, $subject->renderPublic($response));
        $this->assertSame(
            [
                ['Content-Type', 'application/json', true],
                ['Cache-Control', 'no-cache, no-store, must-revalidate', true],
            ],
            $this->headers,
        );
    }
}
