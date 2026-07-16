<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Model\Config\Comment;

use Magento\Config\Model\Config\CommentInterface;
use Magento\Framework\Composer\ComposerInformation;

class Version implements CommentInterface
{
    /**
     * @param ComposerInformation $composerInformation
     */
    public function __construct(private readonly ComposerInformation $composerInformation)
    {
    }

    /**
     * Returns installed Tweakwise module version as comment string.
     *
     * @param mixed $elementValue
     * @return string
     * phpcs:disable Generic.CodeAnalysis.UnusedFunctionParameter.FoundInImplementedInterface
     */
    public function getCommentText(mixed $elementValue): string
    {
        $installedPackages = $this->composerInformation->getInstalledMagentoPackages();

        if (!isset($installedPackages['tweakwise/magento2-tweakwise']['version'])) {
            return '';
        }

        return sprintf('Tweakwise version %s', $installedPackages['tweakwise/magento2-tweakwise']['version']);
    }
}
