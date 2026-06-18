<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Api\Data;

use Magento\Framework\ObjectManagerInterface;

class AttributeSlugInterfaceFactory
{
    /**
     * @param ObjectManagerInterface $objectManager
     * @param string $instanceName
     */
    public function __construct(
        private readonly ObjectManagerInterface $objectManager,
        private readonly string $instanceName = AttributeSlugInterface::class
    ) {
    }

    /**
     * @param array $data
     * @return AttributeSlugInterface
     */
    public function create(array $data = []): AttributeSlugInterface
    {
        return $this->objectManager->create($this->instanceName, $data);
    }
}
