<?php

namespace Tweakwise\Magento2Tweakwise\Model;

use InvalidArgumentException;
use Tweakwise\Magento2Tweakwise\Block\LayeredNavigation\RenderLayered\SliderRenderer;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\NavigationContext\CurrentContext;
use Tweakwise\Magento2Tweakwise\Model\FilterFormInputProvider\FilterFormInputProviderInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\UrlInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Tweakwise\Magento2Tweakwise\Model\FilterFormInputProvider\HashInputProvider;
use Tweakwise\Magento2Tweakwise\Model\Catalog\Layer\Url\Strategy\QueryParameterStrategy;

/**
 * Class NavigationConfig
 * This class provides configuration for the various data-mage-init statements in phtml files.
 * It will be used by
 * @see \Tweakwise\Magento2Tweakwise\Block\LayeredNavigation\RenderLayered\DefaultRenderer
 * @see \Tweakwise\Magento2Tweakwise\Block\LayeredNavigation\RenderLayered\SwatchRenderer
 * @see \Tweakwise\Magento2Tweakwise\Block\LayeredNavigation\RenderLayered\SliderRenderer
 */
class NavigationConfig implements ArgumentInterface, FilterFormInputProviderInterface
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Json
     */
    protected $jsonSerializer;

    /**
     * @var UrlInterface
     */
    protected $url;

    /**
     * @var CurrentContext
     */
    protected $currentNavigationContext;

    /**
     * @var ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * @var FilterFormInputProviderInterface
     */
    protected $filterFormInputProvider;

    /**
     * @var Http
     */
    protected $request;

    /**
     * @var HashInputProvider
     */
    protected $hashInputProvider;

    /**
     * NavigationConfig constructor.
     * @param Config $config
     * @param UrlInterface $url
     * @param CurrentContext $currentNavigationContext
     * @param ProductMetadataInterface $productMetadata
     * @param FilterFormInputProviderInterface $filterFormInputProvider
     * @param Json $jsonSerializer
     * @param Http $request
     */
    public function __construct(
        Config $config,
        UrlInterface $url,
        CurrentContext $currentNavigationContext,
        ProductMetadataInterface $productMetadata,
        FilterFormInputProviderInterface $filterFormInputProvider,
        Json $jsonSerializer,
        Http $request,
        HashInputProvider $hashInputProvider
    ) {
        $this->config = $config;
        $this->jsonSerializer = $jsonSerializer;
        $this->url = $url;
        $this->currentNavigationContext = $currentNavigationContext;
        $this->productMetadata = $productMetadata;
        $this->filterFormInputProvider = $filterFormInputProvider;
        $this->request = $request;
        $this->hashInputProvider = $hashInputProvider;
    }

    /**
     * @return array|string[]
     * @throws InvalidArgumentException
     */
    public function getFilterFormInput(): array
    {
        //check request for modified values
        if (!$this->hashInputProvider->validateHash($this->request)) {
            //form is modified, don't accept the request. Should only happen in an xss attack
            throw new InvalidArgumentException('Incorrect/modified form parameters');
        }

        $filterFormInput = $this->filterFormInputProvider->getFilterFormInput();
        unset($filterFormInput['p']);

        return $filterFormInput;
    }

    /**
     * @return string
     */
    public function getJsFormConfig()
    {
        $navigationFormConfig = [
            'tweakwiseNavigationForm' => [
                'formFilters' => $this->isFormFilters(),
                'ajaxFilters' => $this->isAjaxFilters(),
                'seoEnabled' => $this->config->isSeoEnabled(),
                'ajaxEndpoint' => $this->getAjaxEndPoint(),
                'filterSelector' => '#layered-filter-block',
                'productListSelector' => '.products.wrapper',
                'toolbarSelector' => '.toolbar.toolbar-products',
                'ajaxCache' => true,
                'urlStrategy' => $this->config->getUrlStrategy() ===
                QueryParameterStrategy::class
                    ? 'queryparameter'
                    : 'path',
            ],
        ];
        if ($this->config->isPersonalMerchandisingActive()) {
            $pmCookieName = $this->config->getPersonalMerchandisingCookieName();
            if ($pmCookieName) {
                $navigationFormConfig['tweakwiseNavigationForm']['ajaxCache'] = false;
            }
        }

        return $this->jsonSerializer->serialize($navigationFormConfig);
    }

    /**
     * @param SliderRenderer $sliderRenderer
     * @return string
     */
    public function getJsSliderConfig(SliderRenderer $sliderRenderer)
    {
        $slider = $this->getSliderReference();
        return $this->jsonSerializer->serialize(
            [
                $slider => [
                    'ajaxFilters' => $this->isAjaxFilters(),
                    'formFilters' => $this->isFormFilters(),
                    'filterUrl' => $sliderRenderer->getFilterUrl(),
                    'prefix' => "<span class=\"prefix\">{$sliderRenderer->getItemPrefix()}</span>",
                    'postfix' => "<span class=\"postfix\">{$sliderRenderer->getItemPostfix()}</span>",
                    'container' => "#attribute-slider-{$sliderRenderer->getCssId()}",
                    'min' => $sliderRenderer->getMinValue(),
                    'max' => $sliderRenderer->getMaxValue(),
                    'currentMin' => $sliderRenderer->getCurrentMinValue(),
                    'currentMax' => $sliderRenderer->getCurrentMaxValue(),
                    'containsBuckets' => $sliderRenderer->containsBuckets(),
                    'containsClickpoints' => $sliderRenderer->containsClickpoints(),
                ]
            ]
        );
    }

    /**
     * Return which slider to use, the compat version has the full jquery/ui reference.
     * The normal slider definition has jquery-ui-modules/slider, which is only available from 2.3.3 and onwards
     * The widget slider definition has jquery-ui-modules/widget/slider, which is only available from 2.4.4 and onwards
     *
     * @return string
     */
    protected function getSliderReference()
    {
        $mVersion = $this->productMetadata->getVersion();
        if (version_compare($mVersion, '2.3.3', '<')) {
            return 'tweakwiseNavigationSliderCompat';
        } elseif (version_compare($mVersion, '2.4.4', '<')) {
            return 'tweakwiseNavigationSlider';
        }

        return 'tweakwiseNavigationSliderWidget';
    }

    /**
     * @param bool $hasAlternateSort
     */
    public function getJsSortConfig($hasAlternateSort = null)
    {
        return $this->jsonSerializer->serialize(
            [
                'tweakwiseNavigationSort' => [
                    'hasAlternateSort' => (bool)$hasAlternateSort
                ]
            ]
        );
    }

    /**
     * @return bool
     */
    public function isFormFilters()
    {
        return $this->config->isFormFilters();
    }

    /**
     * @return bool
     */
    public function isAjaxFilters()
    {
        return $this->config->isAjaxFilters();
    }

    /**
     * @return string
     */
    protected function getAjaxEndPoint()
    {
        return $this->url->getUrl('tweakwise/ajax/navigation');
    }

    public function getNavigationContext()
    {
        return $this->currentNavigationContext;
    }
}
