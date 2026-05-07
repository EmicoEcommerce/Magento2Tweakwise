<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Model\Config\Comment;

use Magento\Config\Model\Config\CommentInterface;
use Magento\Framework\Composer\ComposerInformation;

class Version implements CommentInterface
{
    /**
     * @var ComposerInformation
     */
    private readonly ComposerInformation $composerInformation;

    /**
     * @param ComposerInformation $composerInformation
     */
    public function __construct(ComposerInformation $composerInformation)
    {
        $this->composerInformation = $composerInformation;
    }

    /**
     * Returns the installed Tweakwise module version as a comment string.
     *
     * @param string $elementValue
     * @return string
     */
    public function getCommentText($elementValue): string
    {
        $installedPackages = $this->composerInformation->getInstalledMagentoPackages();

        if (!isset($installedPackages['tweakwise/magento2-tweakwise']['version'])) {
            return '';
        }

        return sprintf('Tweakwise version %s', $installedPackages['tweakwise/magento2-tweakwise']['version']);
    }
}
