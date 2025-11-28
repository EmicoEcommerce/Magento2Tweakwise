<?php

namespace Tweakwise\Magento2Tweakwise\Model;

use Magento\Catalog\Model\Product;
use Tweakwise\Magento2Tweakwise\Api\Data\VisualInterface;

class Visual extends Product implements VisualInterface
{
    /**
     * @return string
     */
    public function getImageUrl(): string
    {
        return $this->getData(self::IMAGE_URL);
    }

    /**
     * @param string $imageUrl
     * @return VisualInterface
     */
    public function setImageUrl(string $imageUrl): VisualInterface
    {
        return $this->setData(self::IMAGE_URL, $imageUrl);
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->getData(self::URL);
    }

    /**
     * @param string $url
     * @return VisualInterface
     */
    public function setUrl(string $url): VisualInterface
    {
        return $this->setData(self::URL, $url);
    }
}
