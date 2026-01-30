<?php

namespace Tweakwise\Magento2Tweakwise\Model\Autocomplete\DataProvider;

use Magento\Framework\DataObject;

/**
 * Class SuggestionBlockItem
 */
class SuggestionBlockItem extends DataObject
{
    /**
     * SuggestionBlockItem constructor.
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        parent::__construct($data);

        if (isset($data['block'])) {
            $this->setBlock($data['block']);
        }
    }

    /**
     * Get block data
     *
     * @return array|null
     */
    public function getBlock(): ?array
    {
        return $this->getData('block');
    }

    /**
     * Set block data
     *
     * @param array $block
     * @return void
     */
    public function setBlock(array $block): void
    {
        $this->setData('block', $block);
    }
}
