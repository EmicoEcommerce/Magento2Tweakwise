<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

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
