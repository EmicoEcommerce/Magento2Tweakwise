<?php // phpcs:ignore SlevomatCodingStandard.TypeHints.DeclareStrictTypes.DeclareStrictTypesMissing

namespace Tweakwise\Magento2Tweakwise\Model\Client;

use Magento\Framework\App\ResponseInterface;

class Timer
{
    /**
     * @var array
     */
    private $timers = [];

    /**
     * @var ResponseInterface
     */
    protected $response;

    /**
     * @param ResponseInterface $response
     */
    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    /**
     * @param string $name
     * @return void
     */
    public function startTimer($name)
    {
        $this->timers[$name] = [
            'start' => microtime(true),
        ];
    }

    /**
     * @param string $name
     * @return void
     */
    public function endTimer($name)
    {
        $this->timers[$name]['end'] = microtime(true);
        $this->setHeader($name);
    }

    /**
     * @param string $name
     * @return string
     */
    public function getServerTiming($name)
    {
        if (isset($this->timers[$name]['start']) && isset($this->timers[$name]['end'])) {
            $timeTaken = ($this->timers[$name]['end'] - $this->timers[$name]['start']) * 1000;
            $name = str_replace('/', '-', $name); //no slashes in header value
            return sprintf('%s;dur=%f', 'TW-' . $name, $timeTaken);
        }

        // @phpstan-ignore-next-line
        return 0;
    }

    /**
     * @param string $name
     * @return int|mixed
     */
    public function getTime($name)
    {
        if (isset($this->timers[$name])) {
            return $this->timers[$name]['end'] - $this->timers[$name]['start'];
        }

        return 0;
    }

    /**
     * @param string $name
     * @return void
     */
    private function setHeader($name)
    {
        // @phpstan-ignore-next-line
        $currentHeader = $this->response->getHeader('Server-Timing');
        $timing = $this->getServerTiming($name);

        if ($timing <= 0) {
            return;
        }

        if (empty($currentHeader)) {
            // @phpstan-ignore-next-line
            $this->response->setHeader('Server-Timing', $timing, false);
        } else {
            // @phpstan-ignore-next-line
            $this->response->setHeader('Server-Timing', $currentHeader->getFieldValue() . ', ' . $timing, true);
        }
    }
}
