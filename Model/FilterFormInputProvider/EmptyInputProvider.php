<?php

namespace Tweakwise\Magento2Tweakwise\Model\FilterFormInputProvider;

class EmptyInputProvider implements FilterFormInputProviderInterface
{
    /**
     * @inheritDoc
     */
    public function getFilterFormInput(): array
    {
        return [];
    }
}
