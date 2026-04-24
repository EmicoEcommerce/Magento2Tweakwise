<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Tweakwise\Magento2Tweakwise\Model\Client\Request\Suggestions;

use Tweakwise\Magento2Tweakwise\Model\Client\Request;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\SearchRequestInterface;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\SearchRequestTrait;
use Tweakwise\Magento2Tweakwise\Model\Client\Response\Suggestions\ProductSuggestionsResponse;

class ProductSuggestionsRequest extends Request implements SearchRequestInterface
{
    use SearchRequestTrait;

    /**
     * @var string
     */
    protected $path = 'suggestions/items';

    protected $groupedPath = 'suggestions/items/grouped';

    /**
     * @return string
     */
    public function getResponseType()
    {
        return ProductSuggestionsResponse::class;
    }
}
