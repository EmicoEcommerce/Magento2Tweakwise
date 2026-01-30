<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Tweakwise\Magento2Tweakwise\Controller\Ajax;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Tweakwise\Magento2Tweakwise\Model\Client;
use Tweakwise\Magento2Tweakwise\Model\Client\Request\AnalyticsRequest;
use Tweakwise\Magento2Tweakwise\Model\PersonalMerchandisingConfig;
use Tweakwise\Magento2Tweakwise\Model\Client\RequestFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultInterface;
use Tweakwise\Magento2Tweakwise\Service\Event\SessionStartEventService;
use Tweakwise\Magento2TweakwiseExport\Model\Helper;
use Magento\Store\Model\StoreManagerInterface;
use Exception;
use Tweakwise\Magento2Tweakwise\Model\Client\Request;
use Magento\Framework\Serialize\Serializer\Json as JsonSerializer;
use InvalidArgumentException;

class Analytics extends Action
{
    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     * @param Client $client
     * @param PersonalMerchandisingConfig $config
     * @param RequestFactory $requestFactory
     * @param Helper $helper
     * @param StoreManagerInterface $storeManager
     * @param JsonSerializer $jsonSerializer
     * @param SessionStartEventService $sessionStartEventService
     */
    public function __construct(
        Context $context,
        private JsonFactory $resultJsonFactory,
        private Client $client,
        private PersonalMerchandisingConfig $config,
        private readonly RequestFactory $requestFactory,
        private readonly Helper $helper,
        private readonly StoreManagerInterface $storeManager,
        private readonly JsonSerializer $jsonSerializer,
        private readonly SessionStartEventService $sessionStartEventService,
    ) {
        parent::__construct($context);
    }

    /**
     * @return ResponseInterface|Json|ResultInterface
     */
    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $result->setData(['success' => false, 'message' => 'Invalid request.']);

        if (!$this->config->isAnalyticsEnabled()) {
            return $result->setData(['success' => false, 'message' => 'Analytics is disabled.']);
        }

        $request = $this->getRequest();
        $eventsData = $request->getParam('eventsData');

        //hyva theme
        // @phpstan-ignore-next-line
        if (empty($eventsData) && !empty($request->getContent())) {
            // @phpstan-ignore-next-line
            $content = $this->jsonSerializer->unserialize($request->getContent());
            $eventsData = $content['eventsData'] ?? null;
        }

        if (empty($eventsData)) {
            return $result->setData(['success' => false, 'message' => 'Missing required parameters.']);
        }

        try {
            foreach ($eventsData as $eventData) {
                $this->processAnalyticsRequest($eventData);
            }
            return $result->setData(['success' => true]);
        } catch (Exception $e) {
            return $result->setData(['success' => false, 'message' => $e->getMessage()]);
        }

        // @phpstan-ignore-next-line
        return $result;
    }

    /**
     * Process the analytics request based on type and value.
     * @param array $eventData
     * @throws NoSuchEntityException
     */
    private function processAnalyticsRequest(array $eventData): void
    {
        $profileKey = $this->config->getProfileKey();
        /** @var AnalyticsRequest $tweakwiseRequest */
        $tweakwiseRequest = $this->requestFactory->create();
        $tweakwiseRequest->setProfileKey($profileKey);
        $type = $eventData['type'];
        $value = $eventData['value'];

        switch ($type) {
            case 'product':
                $this->handleProductType($tweakwiseRequest, $value);
                break;
            case 'search':
                $this->handleSearchType($tweakwiseRequest, $value);
                break;
            case 'itemclick':
                $this->handleItemClickType($tweakwiseRequest, $value, $eventData['requestId']);
                break;
            case 'session_start':
                if ($this->sessionStartEventService->isSessionStartEventSent()) {
                    return;
                }

                $this->sessionStartEventService->handleSessionStartType($tweakwiseRequest);
                break;
            default:
                throw new InvalidArgumentException('Invalid type parameter.');
        }

        $this->client->request($tweakwiseRequest);
    }

    /**
     * @param Request $tweakwiseRequest
     * @param string $productKey
     *
     * @return void
     */
    private function handleProductType(Request $tweakwiseRequest, string $productKey): void
    {
        $tweakwiseRequest->setParameter('SessionKey', $this->sessionStartEventService->getSessionKey());
        $tweakwiseRequest->setParameter('ProductKey', $productKey);
        $tweakwiseRequest->setPath('pageview');
    }

    /**
     * @param Request $tweakwiseRequest
     * @param string $searchTerm
     *
     * @return void
     */
    private function handleSearchType(Request $tweakwiseRequest, string $searchTerm): void
    {
        $tweakwiseRequest->setParameter('SessionKey', $this->sessionStartEventService->getSessionKey());
        $tweakwiseRequest->setParameter('SearchTerm', $searchTerm);
        $tweakwiseRequest->setPath('search');
    }

    /**
     * @param Request $tweakwiseRequest
     * @param string $itemId
     * @param string|null $requestId
     * @return void
     * @throws NoSuchEntityException
     */
    private function handleItemClickType(Request $tweakwiseRequest, string $itemId, ?string $requestId): void
    {
        $storeId = (int)$this->storeManager->getStore()->getId();

        if (empty($requestId)) {
            throw new InvalidArgumentException('Missing requestId for itemclick.');
        }

        if (ctype_digit($itemId)) {
            // @phpstan-ignore-next-line
            $itemId = $this->helper->getTweakwiseId($storeId, $itemId);
        }

        $tweakwiseRequest->setParameter('SessionKey', $this->sessionStartEventService->getSessionKey());
        $tweakwiseRequest->setParameter('RequestId', $requestId);
        $tweakwiseRequest->setParameter('ItemId', $itemId);
        $tweakwiseRequest->setPath('itemclick');
    }
}
