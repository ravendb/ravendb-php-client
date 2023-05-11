<?php

namespace RavenDB\Documents\Session;

use RavenDB\Type\Duration;

class ResponseTimeInformation
{
    private ?Duration $totalServerDuration = null;
    private ?Duration $totalClientDuration = null;

    private ?ResponseTimeItemArray $durationBreakdown = null;

    public function computeServerTotal(): void
    {
        $this->totalServerDuration = Duration::ofMillis(
            array_reduce(
                array_map(function($x) {
                        return $x->getDuration()->toMillis();
                    },
                    $this->durationBreakdown->getArrayCopy()
                ),
                function($carry, $durationInMillis) {
                    return $carry  + $durationInMillis;
                },
                0
            )
        );
    }

    public function __construct()
    {
        $this->totalServerDuration = Duration::zero();
        $this->totalClientDuration = Duration::zero();
        $this->durationBreakdown = new ResponseTimeItemArray();
    }

    public function getTotalServerDuration(): ?Duration
    {
        return $this->totalServerDuration;
    }

    public function setTotalServerDuration(?Duration $totalServerDuration): void
    {
        $this->totalServerDuration = $totalServerDuration;
    }

    public function getTotalClientDuration(): ?Duration
    {
        return $this->totalClientDuration;
    }

    public function setTotalClientDuration(?Duration $totalClientDuration): void
    {
        $this->totalClientDuration = $totalClientDuration;
    }

    public function getDurationBreakdown(): ?ResponseTimeItemArray
    {
        return $this->durationBreakdown;
    }

    public function setDurationBreakdown(?ResponseTimeItemArray $durationBreakdown): void
    {
        $this->durationBreakdown = $durationBreakdown;
    }
}
