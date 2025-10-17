<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Model;

class ConfigAttributeProcessService
{
    /**
     * @param string|null $filterList
     * @return array
     * phpcs:disable Magento2.Functions.StaticFunction.StaticFunction
     */
    public static function extractFilterValuesWhitelist(?string $filterList = null): array
    {
        if (empty($filterList)) {
            return [];
        }

        $filterList = trim($filterList);

        // @phpstan-ignore-next-line
        $filterListExploded = explode(',', $filterList) ? explode(',', $filterList) : [];
        // @phpstan-ignore-next-line
        if (empty($filterListExploded)) {
            return [];
        }

        $return = [];
        foreach ($filterListExploded as $listItem) {
            // @phpstan-ignore-next-line
            $item = explode('=', trim($listItem)) ? explode('=', trim($listItem)) : null;

            // @phpstan-ignore-next-line
            if ($item === null || !isset($item[0]) || !isset($item[1])) {
                continue;
            }

            $return[$item[0]][] = $item[1];
        }

        return $return;
    }

    /**
     * @param string|null $filterList
     * @return array
     * phpcs:disable Magento2.Functions.StaticFunction.StaticFunction
     */
    public static function extractFilterWhitelist(?string $filterList = null): array
    {
        if (empty($filterList)) {
            return [];
        }

        $filterList = trim($filterList);

        // @phpstan-ignore-next-line
        $filterListExploded = explode(',', $filterList) ? explode(',', $filterList) : [];

        // @phpstan-ignore-next-line
        if (empty($filterListExploded)) {
            return [];
        }

        return $filterListExploded;
    }
}
