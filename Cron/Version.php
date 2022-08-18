<?php

namespace Tweakwise\Magento2Tweakwise\Cron;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Composer\ComposerInformation;

/**
 * Class Version
 * @package Tweakwise\Magento2Tweakwise\Cron
 */
class Version
{
    /**
     * @var ComposerInformation
     */
    protected $composerInformation;

    /**
     * @var WriterInterface
     */
    protected $configWriter;

    /**
     * Version constructor.
     * @param ComposerInformation $composerInformation
     * @param WriterInterface $configWriter
     */
    public function __construct(
        ComposerInformation $composerInformation,
        WriterInterface $configWriter
    ) {
        $this->composerInformation = $composerInformation;
        $this->configWriter = $configWriter;
    }

    /**
     * Update Tweakwise version number to config table
     */
    public function execute()
    {
        $installedPackages = $this->composerInformation
            ->getInstalledMagentoPackages();

        if (!isset($installedPackages['tweakwise/magento2tweakwise']['version'])) {
            // This should never be the case
            return;
        }

        $version = $installedPackages['tweakwise/magento2tweakwise']['version'];
        $userAgentString = sprintf(
            '%s(%s)',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/76.0.3809.132 Safari/537.36 Magento2Tweakwise',
            $version
        );
        $this->configWriter->save('tweakwise/general/version', $userAgentString);
    }
}
