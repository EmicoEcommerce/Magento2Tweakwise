<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2Tweakwise\Model\Client\Request;

class ProductSearchRequest extends ProductNavigationRequest implements SearchRequestInterface
{
    use SearchRequestTrait;

    /**
     * @var string
     */
    protected $path = 'navigation-search';

    /**
     * @var string
     */
    protected $groupedPath = 'navigation-search/grouped';
}
