<?php
/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\FilterList;

use Closure;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2Tweakwise\Exception\TweakwiseException;
use Tweakwise\Magento2TweakwiseExport\Model\Logger;
use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\Layer\Filter\AbstractFilter;
use Magento\Catalog\Model\Layer\FilterList;

class Plugin
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Logger
     */
    protected $log;

    /**
     * @var Tweakwise
     */
    protected $tweakwiseFilterList;

    /**
     * Proxy constructor.
     *
     * @param Config $config
     * @param Logger $log
     * @param Tweakwise $tweakwiseFilterList
     */
    public function __construct(Config $config, Logger $log, Tweakwise $tweakwiseFilterList)
    {
        $this->config = $config;
        $this->log = $log;
        $this->tweakwiseFilterList = $tweakwiseFilterList;
    }

    /**
     * @param FilterList $subject
     * @param Closure $proceed
     * @param Layer $layer
     * @return AbstractFilter[]
     */
    public function aroundGetFilters(FilterList $subject, Closure $proceed, Layer $layer)
    {
        if (!$this->config->isLayeredEnabled()) {
            if (!$this->config->isSearchEnabled() || !($layer instanceof Layer\Search)) {
                return $proceed($layer);
            }
        }

        try {
            return $this->tweakwiseFilterList->getFilters($layer);
        } catch (TweakwiseException $e) {
            $this->log->critical($e);
            $this->config->setTweakwiseExceptionThrown();

            return $proceed($layer);
        }
    }
}
