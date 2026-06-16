<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model;

use ArrayObject;
use Emico\CodeCept\Test\Unit;
use Magento\Framework\App\Response\HttpInterface;
use Magento\Framework\Serialize\Serializer\Json;
use PHPUnit\Framework\MockObject\MockObject;
use Tweakwise\Magento2Tweakwise\Model\AjaxProductCountResult;
use Tweakwise\Test\Support\UnitTester;

class AjaxProductCountResultTest extends Unit
{
    protected UnitTester $tester;

    private Json&MockObject $serializer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->serializer = $this->createMock(Json::class);
    }

    public function testRenderWritesProductCountJson(): void
    {
        $response = $this->createMock(HttpInterface::class);
        $headers = new ArrayObject();
        $response->expects($this->exactly(2))
            ->method('setHeader')
            ->willReturnCallback(function (string $name, string $value, bool $replace) use ($headers): void {
                $headers->append([$name, $value, $replace]);
            });
        $response->expects($this->once())
            ->method('appendBody')
            ->with('{"product_count":42}');

        $this->serializer->expects($this->once())
            ->method('serialize')
            ->with(['product_count' => 42])
            ->willReturn('{"product_count":42}');

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
            $headers->getArrayCopy(),
        );
    }
}
