<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

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

    /**
     * @return int|null
     */
    public function getColspan(): ?int
    {
        return $this->getData(self::COLSPAN);
    }

    /**
     * @param int $colspan
     * @return VisualInterface
     */
    public function setColspan(int $colspan): VisualInterface
    {
        return $this->setData(self::COLSPAN, $colspan);
    }

    /**
     * @return int|null
     */
    public function getRowspan(): ?int
    {
        return $this->getData(self::ROWSPAN);
    }

    /**
     * @param int $rowspan
     * @return VisualInterface
     */
    public function setRowspan(int $rowspan): VisualInterface
    {
        return $this->setData(self::ROWSPAN, $rowspan);
    }
}
