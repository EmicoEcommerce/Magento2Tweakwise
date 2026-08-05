<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Model\Config\Comment;

use Emico\CodeCept\Test\Unit;
use Magento\Framework\Composer\ComposerInformation;
use Mockery;
use Tweakwise\Magento2Tweakwise\Model\Config\Comment\Version;
use Tweakwise\Test\Support\UnitTester;

class TweakwiseVersionTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    /**
     * @param array<string, array<string, string>> $installedMagentoPackages
     * @param string $expected
     * @return void
     * @dataProvider commentTextDataProvider
     */
    public function testVersionText(array $installedMagentoPackages, string $expected): void
    {
        $composerInformation = Mockery::mock(ComposerInformation::class);
        $composerInformation
            ->shouldReceive('getInstalledMagentoPackages')
            ->andReturn($installedMagentoPackages);
        $this->tester->mockService(ComposerInformation::class, $composerInformation);

        $version = $this->tester->getObjectManager()->create(Version::class);
        $this->assertSame($expected, $version->getCommentText(null));
    }

    public function _after(): void
    {
        Mockery::close();
    }

    /**
     * @return array<string, array{0: array<string, array<string, string>>, 1: string}>
     */
    public function commentTextDataProvider(): array
    {
        return [
            'correct-installed' => [
                [
                    'tweakwise/magento2-tweakwise' => [
                        'version' => 'v7.8.3',
                    ],
                ],
                'Tweakwise version v7.8.3',
            ],
            'version-missing' => [
                [
                    'tweakwise/magento2-tweakwise' => [],
                ],
                '',
            ],
            'package-missing' => [
                [
                    'magento/product-community-edition' => [
                        'version' => '2.4.8',
                    ],
                ],
                '',
            ],
        ];
    }
}
