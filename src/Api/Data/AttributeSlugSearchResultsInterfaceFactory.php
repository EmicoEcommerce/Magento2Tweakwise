<?php

declare (strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Api\Data;

use Magento\Framework\ObjectManagerInterface;

class AttributeSlugSearchResultsInterfaceFactory
{
    /**
     * @param ObjectManagerInterface $objectManager
     * @param string $instanceName
     */
    public function __construct(
        private readonly ObjectManagerInterface $objectManager,
        private readonly string $instanceName = AttributeSlugSearchResultsInterface::class
    ) {
    }

    /**
     * @param array $data
     * @return AttributeSlugSearchResultsInterface
     */
    public function create(array $data = []): AttributeSlugSearchResultsInterface
    {
        return $this->objectManager->create($this->instanceName, $data);
    }
}
