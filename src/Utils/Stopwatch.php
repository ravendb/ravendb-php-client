<?php

namespace RavenDB\Utils;

use RavenDB\Type\Duration;

class Stopwatch
{

    public static function createStarted(): Stopwatch
    {
        $stopwatch = new Stopwatch();
        $stopwatch->start();

        return $stopwatch;
    }

    private function start(): void
    {

    }

    public function stop(): void
    {

    }

    public function elapsed(): ?Duration
    {
        return null;
    }


}
