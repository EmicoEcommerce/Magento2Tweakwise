<?php

namespace Tweakwise\Magento2Tweakwise\Model\Client;

class Timer
{
    private $timers = [];

    public function startTimer($name)
    {
        $this->timers[$name] = [
            'start' => microtime(true),
        ];
    }

    public function endTimer($name)
    {
        $this->timers[$name]['end'] = microtime(true);
    }

    public function getTimers()
    {
        $metrics = [];

        if (count($this->timers)) {
            foreach($this->timers as $name => $timer) {
                $timeTaken = ($timer['end'] - $timer['start']) * 1000;
                $output = sprintf('%s;dur=%f', $name, $timeTaken);

                $metrics[] = $output;
            }
        }

        return implode($metrics, ', ');
    }

    public function getTime($name)
    {
        if (isset($this->timers[$name])) {
            $timeTaken = ($this->timers[$name]['end'] - $this->timers[$name]['start']);
            return $timeTaken;
        }

        return 0;
    }
}
