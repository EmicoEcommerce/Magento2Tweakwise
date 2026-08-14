<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model;

use Emico\CodeCept\Test\Unit;
use Magento\Framework\App\RequestInterface;
use Mockery;
use Mockery\MockInterface;
use ReflectionClass;
use Tweakwise\Magento2Tweakwise\Model\AjaxNavigationResult;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2Tweakwise\Model\UrlPaginationHelper;
use Tweakwise\Test\Support\UnitTester;

class AjaxNavigationResultTest extends Unit
{
    protected UnitTester $tester;

    private Config&MockInterface $config;
    private RequestInterface&MockInterface $request;
    private UrlPaginationHelper&MockInterface $urlPaginationHelper;
    private AjaxNavigationResult $subject;

    public function _before(): void
    {
        $this->config = Mockery::mock(Config::class);
        $this->request = Mockery::mock(RequestInterface::class);
        $this->urlPaginationHelper = Mockery::mock(UrlPaginationHelper::class);

        $this->subject = (new ReflectionClass(AjaxNavigationResult::class))->newInstanceWithoutConstructor();
        $this->setProtectedProperty($this->subject, 'config', $this->config);
        $this->setProtectedProperty($this->subject, 'request', $this->request);
        $this->setProtectedProperty($this->subject, 'urlPaginationHelper', $this->urlPaginationHelper);
    }

    public function _after(): void
    {
        Mockery::close();
    }

    public function testGetCanonicalUrlReturnsEmptyWhenFeatureDisabled(): void
    {
        $this->config->shouldReceive('isPaginatedCanonicalEnabled')->once()->andReturn(false);

        $result = $this->subject->getCanonicalUrl('https://example.com/category?color=blue');

        $this->assertSame('', $result);
    }

    public function testGetCanonicalUrlReturnsEmptyWhenPageLowerThanTwo(): void
    {
        $this->config->shouldReceive('isPaginatedCanonicalEnabled')->once()->andReturn(true);
        $this->request->shouldReceive('getParam')->with('p')->once()->andReturn('1');

        $responseUrl = 'https://example.com/category?color=blue';
        $result = $this->subject->getCanonicalUrl($responseUrl);

        $this->assertSame('', $result);
    }

    public function testGetCanonicalUrlAppendsPageToExistingQueryString(): void
    {
        $this->config->shouldReceive('isPaginatedCanonicalEnabled')->once()->andReturn(true);
        $this->request->shouldReceive('getParam')->with('p')->once()->andReturn('3');
        $this->urlPaginationHelper
            ->shouldReceive('appendPageParam')
            ->once()
            ->with('https://example.com/category?color=blue', 3)
            ->andReturn('https://example.com/category?color=blue&p=3');

        $result = $this->subject->getCanonicalUrl('https://example.com/category?color=blue');

        $this->assertSame('https://example.com/category?color=blue&p=3', $result);
    }

    private function setProtectedProperty(object $object, string $propertyName, mixed $value): void
    {
        $class = new ReflectionClass($object);

        while ($class !== false) {
            if ($class->hasProperty($propertyName)) {
                $property = $class->getProperty($propertyName);
                $property->setAccessible(true);
                $property->setValue($object, $value);
                return;
            }

            $class = $class->getParentClass();
        }
    }
}
