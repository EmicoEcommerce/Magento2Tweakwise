<?php

namespace Tweakwise\Magento2Tweakwise\Block\TargetRule\Catalog\Product\ProductList;

/**
 * Magento docs say that you can register a virtualType as plugin, you can't.
 * We need this class to
 *
 * Class UpsellPlugin
 * @package Tweakwise\Magento2Tweakwise\Block\TargetRule\Catalog\Product\ProductList
 */
class UpsellPlugin extends Plugin
{
    protected $type = 'upsell';
}
