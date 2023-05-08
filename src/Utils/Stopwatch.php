<?php

namespace RavenDB\Utils;

class Stopwatch
{
    private ?float $startTimeInMilliseconds = null;
    private ?float $endTimeInMilliseconds = null;

    public static function createStarted(): Stopwatch
    {
        $stopwatch = new Stopwatch();
        $stopwatch->start();

        return $stopwatch;
    }

    public function start(): void
    {
        $this->reset();
    }

    public function stop(): void
    {
        $this->endTimeInMilliseconds = microtime(true);
    }

    public function reset(): void
    {
        $this->startTimeInMilliseconds = microtime(true);
    }

    /**
     * @return float Elapsed time in seconds
     */
    public function elapsed(): float
    {
        if ($this->endTimeInMilliseconds == null) {
            return microtime(true) - $this->startTimeInMilliseconds;
        }

        return $this->endTimeInMilliseconds - $this->startTimeInMilliseconds;
    }

    /**
     * @return int
     */
    public function elapsedInMillis(): int
    {
        return intval(1000*round($this->elapsed(), 6));
    }

}
