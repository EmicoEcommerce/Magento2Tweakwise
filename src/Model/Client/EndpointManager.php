<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Tweakwise\Magento2Tweakwise\Model\Client;

use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Variable\Model\ResourceModel\Variable as VariableResource;
use Magento\Variable\Model\Variable;
use Magento\Variable\Model\VariableFactory;
use Tweakwise\Magento2Tweakwise\Exception\ApiException;

class EndpointManager
{
    public const DOWN_PERIOD = 300; // 5 minutes
    public const VARIABLE_NAME = '__tw_primary_down_timer';
    public const FALLBACK_VARIABLE_NAME = '__tw_fallback_down_timer';
    public const FALLBACK_DOWN_PERIOD = 30;

    public const SERVER_URL = 'https://gateway.tweakwisenavigator.net';
    public const FALLBACK_SERVER_URL = 'https://gateway.tweakwisenavigator.com';

    /**
     * @var VariableFactory
     */
    protected $variableFactory;

    /**
     * @var VariableResource
     */
    protected $variableResource;

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @var Variable|null
     */
    protected $variable = null;

    protected ?Variable $fallbackVariable = null;

    /**
     * @var bool
     */
    protected $isFallback = false;

    /**
     * EndpointManager constructor.
     * @param VariableFactory $variableFactory
     * @param VariableResource $variableResource
     * @param DateTime $dateTime
     */
    public function __construct(
        VariableFactory $variableFactory,
        VariableResource $variableResource,
        DateTime $dateTime
    ) {
        $this->variableFactory = $variableFactory;
        $this->variableResource = $variableResource;
        $this->dateTime = $dateTime;
    }

    /**
     * @return bool
     */
    public function isFallback(): bool
    {
        return $this->isFallback;
    }

    /**
     * @return string
     */
    public function getServerUrl(): string
    {
        if ($this->isFallback) {
            return $this->getFallbackServerUrl();
        }

        $downUntil = (int) $this->getVariable()->getValue(Variable::TYPE_TEXT);
        if (!$downUntil) {
            return self::SERVER_URL;
        }

        if ($this->dateTime->gmtTimestamp() < $downUntil) {
            // Primary endpoint is considered "down", use fallback
            $this->isFallback = true;
            return $this->getFallbackServerUrl();
        }

        return self::SERVER_URL;
    }

    /**
     * Update the downtime flag if appropriate
     * phpcs:disable Magento2.CodeAnalysis.EmptyBlock.DetectedCatch
     * phpcs:disable Generic.CodeAnalysis.EmptyStatement.DetectedCatch
     */
    public function handleConnectException(bool $isFallback = false): void
    {
        if (!$isFallback) {
            $this->isFallback = true;
        }

        $variable = $isFallback ? $this->getFallbackVariable() : $this->getVariable();
        $downUntil = (int) $variable->getValue(Variable::TYPE_TEXT);
        $now = $this->dateTime->gmtTimestamp();
        if ($downUntil > $now) {
            return;
        }

        $period = $isFallback ? self::FALLBACK_DOWN_PERIOD : self::DOWN_PERIOD;
        $variable->setData('plain_value', $now + $period);
        try {
            $this->variableResource->save($variable);
        } catch (AlreadyExistsException $e) {
            // Wont happen in practice, and if it does it is no good reason to halt execution
        }
    }

    protected function getFallbackServerUrl(): string
    {
        $downUntil = (int) $this->getFallbackVariable()->getValue(Variable::TYPE_TEXT);
        if ($this->dateTime->gmtTimestamp() < $downUntil) {
            throw new ApiException('Tweakwise gateways are temporarily unavailable');
        }

        return self::FALLBACK_SERVER_URL;
    }

    protected function getFallbackVariable(): Variable
    {
        if ($this->fallbackVariable === null) {
            $this->fallbackVariable = $this->loadVariable(self::FALLBACK_VARIABLE_NAME);
        }

        return $this->fallbackVariable;
    }

    /**
     * @return Variable
     */
    protected function getVariable(): Variable
    {
        if ($this->variable) {
            return $this->variable;
        }

        $this->variable = $this->loadVariable(self::VARIABLE_NAME);
        return $this->variable;
    }

    protected function loadVariable(string $code): Variable
    {
        $twPrimaryLastDown = $this->variableFactory->create();
        $this->variableResource->loadByCode(
            $twPrimaryLastDown,
            $code
        );

        if (!$twPrimaryLastDown->getCode()) {
            $twPrimaryLastDown->setCode($code);
            $twPrimaryLastDown->setName('Tweakwise system flag, do not modify');
        }

        return $twPrimaryLastDown;
    }
}
