<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Model\ResourceModel\AttributeSlug;

use Magento\Framework\ObjectManagerInterface;

class CollectionFactory
{
    /**
     * @param ObjectManagerInterface $objectManager
     * @param string $instanceName
     */
    public function __construct(
        private readonly ObjectManagerInterface $objectManager,
        private readonly string $instanceName = Collection::class
    ) {
    }

    /**
     * @param array $data
     * @return Collection
     */
    public function create(array $data = []): Collection
    {
        return $this->objectManager->create($this->instanceName, $data);
    }
}
