<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Model;

use Magento\Framework\Composer\ComposerInformation;

class ModuleInformation
{
    /**
     * @param ComposerInformation $composerInformation
     */
    public function __construct(
        private readonly ComposerInformation $composerInformation
    ) {
    }

    /**
     * @return string
     */
    public function getModuleVersion(): string
    {
        $installedPackages = $this->composerInformation
            ->getInstalledMagentoPackages();
        if (!isset($installedPackages['tweakwise/magento2-tweakwise']['version'])) {
            // This should never be the case
            return '';
        }

        $version = $installedPackages['tweakwise/magento2-tweakwise']['version'];

        return sprintf('Magento2Tweakwise %s', $version);
    }
}
