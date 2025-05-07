<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class UpdatePersonalMerchandisingConfigs implements DataPatchInterface
{
    /**
     * @param WriterInterface $configWriter
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly WriterInterface $configWriter,
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * @return $this
     */
    public function apply(): UpdatePersonalMerchandisingConfigs
    {
        $configurationPaths = [
            'tweakwise/personal_merchandising/enabled' =>
                'tweakwise/merchandising_builder/personal_merchandising/enabled',
            'tweakwise/personal_merchandising/cookie_name' =>
                'tweakwise/merchandising_builder/personal_merchandising/cookie_name',
            'tweakwise/personal_merchandising/product_card_lifetime' =>
                'tweakwise/merchandising_builder/personal_merchandising/product_card_lifetime',
            'tweakwise/personal_merchandising/analytics_enabled' =>
                'tweakwise/merchandising_builder/personal_merchandising/analytics_enabled',
        ];

        foreach ($configurationPaths as $oldPath => $newPath) {
            $value = $this->scopeConfig->getValue($oldPath);
            if ($value !== null) {
                $this->configWriter->save($newPath, $value);
            }
        }

        return $this;
    }

    /**
     * @return array|string[]
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * @return array|string[]
     */
    public function getAliases(): array
    {
        return [];
    }
}
