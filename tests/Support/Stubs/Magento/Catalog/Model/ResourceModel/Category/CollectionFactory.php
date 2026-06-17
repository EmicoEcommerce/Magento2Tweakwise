<?php

declare(strict_types=1);

namespace Magento\Catalog\Model\ResourceModel\Category;

use BadMethodCallException;
use Magento\Catalog\Model\ResourceModel\Category\Collection;

class CollectionFactory
{
    public function create(): Collection
    {
        throw new BadMethodCallException('Stub method should be mocked in unit test');
    }
}
