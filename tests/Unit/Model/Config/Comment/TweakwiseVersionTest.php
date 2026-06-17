<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model\Config\Comment;

use Emico\CodeCept\Test\Unit;
use Magento\Framework\Composer\ComposerInformation;
use PHPUnit\Framework\MockObject\MockObject;
use Tweakwise\Magento2Tweakwise\Model\Config\Comment\Version;

class TweakwiseVersionTest extends Unit
{
    private ComposerInformation|MockObject $composerInformation;
    private Version $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->composerInformation = $this->createMock(ComposerInformation::class);
        $this->subject = new Version($this->composerInformation);
    }

    public function testGetCommentTextReturnsVersionForInstalledPackage(): void
    {
        $this->composerInformation
            ->method('getInstalledMagentoPackages')
            ->willReturn([
                'tweakwise/magento2-tweakwise' => [
                    'version' => 'v7.8.3',
                ],
            ]);

        $result = $this->subject->getCommentText(null);

        $this->assertSame('Tweakwise version v7.8.3', $result);
    }

    public function testGetCommentTextReturnsEmptyStringWhenVersionIsMissing(): void
    {
        $this->composerInformation
            ->method('getInstalledMagentoPackages')
            ->willReturn([
                'tweakwise/magento2-export' => [],
            ]);

        $result = $this->subject->getCommentText(null);

        $this->assertSame('', $result);
    }

    public function testGetCommentTextReturnsEmptyStringWhenPackageIsMissing(): void
    {
        $this->composerInformation
            ->method('getInstalledMagentoPackages')
            ->willReturn([
                'magento/product-community-edition' => [
                    'version' => '2.4.8',
                ],
            ]);

        $result = $this->subject->getCommentText(null);

        $this->assertSame('', $result);
    }
}
