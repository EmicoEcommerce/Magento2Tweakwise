<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Api\Data;

use Magento\Framework\View\Element\Block\ArgumentInterface;

interface TagInterface extends ArgumentInterface
{
    public function get(): string;
}
