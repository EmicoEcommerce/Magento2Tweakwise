<?php

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
     * @param $name
     * @return void
     */
    public function startTimer($name)
    {
        $this->timers[$name] = [
            'start' => microtime(true),
        ];
    }

    /**
     * @param $name
     * @return void
     */
    public function endTimer($name)
    {
        $this->timers[$name]['end'] = microtime(true);
        $this->setHeader($name);
    }

    /**
     * @param $name
     * @return string
     */
    public function getServerTiming($name)
    {
        $timeTaken = ($this->timers[$name]['end'] - $this->timers[$name]['start']) * 1000;
        $name = str_replace('/', '-', $name); //no slashes in header value
        return sprintf('%s;dur=%f', 'TW-' . $name, $timeTaken);
    }

    /**
     * @param $name
     * @return int|mixed
     */
    public function getTime($name)
    {
        if (isset($this->timers[$name])) {
            $timeTaken = ($this->timers[$name]['end'] - $this->timers[$name]['start']);
            return $timeTaken;
        }

        return 0;
    }

    /**
     * @param $name
     * @return void
     */
    private function setHeader($name)
    {
        $currentHeader = $this->response->getHeader('Server-Timing');

        if (empty($currentHeader)) {
            $this->response->setHeader('Server-Timing', $this->getServerTiming($name), false);
        } else {
            $this->response->setHeader('Server-Timing', $currentHeader->getFieldValue() . ', ' . $this->getServerTiming($name), true);
        }
    }
}
