<?php

namespace RavenDB\Utils;

use RavenDB\Type\Duration;
use Symfony\Component\Stopwatch\Stopwatch as SfStopwatch;

class Stopwatch
{
    private SfStopwatch $stopwatch;

    private function __construct(bool $morePrecision = true)
    {
        $this->stopwatch = new SfStopwatch($morePrecision);
    }

    public static function createStarted(bool $morePrecision = true): Stopwatch
    {
        $stopwatch = new Stopwatch($morePrecision);
        $stopwatch->start();

        return $stopwatch;
    }

    private function start(): void
    {
        $this->stopwatch->start('ravendb');
    }

    public function stop(): void
    {
        $this->stopwatch->stop('ravendb');
    }

    /**
     * @return float|int Elapsed time
     */
    public function elapsed()
    {
        $event = $this->stopwatch->getEvent('ravendb');

        return $event->getDuration();
    }

    /**
     * @return int Elapsed time in milliseconds
     */
    public function elapsedInMillis(): int
    {
        $event = $this->stopwatch->getEvent('ravendb');

        return $event->getDuration() * 100;
    }


}
