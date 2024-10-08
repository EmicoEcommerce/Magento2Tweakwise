<?php

namespace Tweakwise\Magento2Tweakwise\Api\Data;

interface VisualInterface
{
    public const IMAGE_URL = 'image';
    public const URL = 'url';

    /**
     * @return string
     */
    public function getImageUrl(): string;

    /**
     * @param string $imageUrl
     * @return self
     */
    public function setImageUrl(string $imageUrl): self;

    /**
     * @return string
     */
    public function getUrl(): string;

    /**
     * @param string $url
     * @return self
     */
    public function setUrl(string $url): self;
}
