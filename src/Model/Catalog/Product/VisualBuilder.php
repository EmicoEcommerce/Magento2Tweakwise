<?php

declare(strict_types=1);

namespace Tweakwise\Magento2Tweakwise\Model\Catalog\Product;

use Tweakwise\Magento2Tweakwise\Api\Data\VisualInterface;
use Tweakwise\Magento2Tweakwise\Model\Client\Type\ItemType as ClientItemType;
use Tweakwise\Magento2Tweakwise\Model\Visual;
use Tweakwise\Magento2Tweakwise\Model\VisualFactory;

class VisualBuilder
{
    /**
     * @param VisualFactory $visualFactory
     */
    public function __construct(private readonly VisualFactory $visualFactory)
    {
    }

    /**
     * @param ClientItemType $item
     * @return VisualInterface&Visual
     */
    public function build(ClientItemType $item): VisualInterface
    {
        /** @var VisualInterface&Visual $visual */
        $visual = $this->visualFactory->create();
        $visualId = (string)$item->getValue(ClientItemType::ID);
        if ($visualId === '') {
            $visualId = (string)$item->getId();
        }

        // @phpstan-ignore-next-line
        $visual->setId($visualId);
        $visual->setData(ClientItemType::TWEAKWISE_ID, $visualId);
        $visual->setImageUrl($item->getImage());
        $visual->setUrl($item->getUrl());

        $colspan = $item->getColspan();
        if ($colspan) {
            $visual->setColspan($colspan);
        }

        $rowspan = $item->getRowspan();
        if ($rowspan) {
            $visual->setRowspan($rowspan);
        }

        $visual->setVisualAttributes($item->getAttributeValues());

        return $visual;
    }
}
