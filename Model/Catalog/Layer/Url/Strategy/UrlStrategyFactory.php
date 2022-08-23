<?php
/**
 * Tweakwise  (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\Strategy;

use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\FilterApplierInterface;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\RouteMatchingInterface;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\UrlInterface;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\ObjectManagerInterface;

/**
 * Class UrlStrategyFactory
 * @package Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\Strategy
 */
class UrlStrategyFactory
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param Config $config
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        Config $config
    ) {
        $this->objectManager = $objectManager;
        $this->config = $config;
    }

    /**
     * Get the configured strategy for a given interface
     *
     * @param string $interface
     * @return UrlInterface|RouteMatchingInterface|FilterApplierInterface
     */
    public function create(string $interface = UrlInterface::class)
    {
        $urlStrategy = $this->config->getUrlStrategy();  //path of query
        $implementation = $this->objectManager->get($urlStrategy);

        if ($implementation instanceof UrlInterface
            && !$implementation->isAllowed()
        ) {
            return $this->objectManager->get($interface);
        }

        // Check if concrete implementation implements the given interface.
        // If not return preference in di.xml
        if (!in_array($interface, class_implements($implementation), true)) {
            return $this->objectManager->get($interface);
        }

        return $implementation;
    }
}
