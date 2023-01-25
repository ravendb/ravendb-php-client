<?php

namespace RavenDB\Primitives;

use DateTimeInterface;

class DateWithContext
{
    private ?DateTimeInterface $date = null;
    private DateContext $context;

    public function __construct(?DateTimeInterface $date, DateContext $context)
    {
        $this->date = $date;
        $this->context = $context;
    }

    public function getDate(): ?DateTimeInterface
    {
        return $this->date;
    }

    public function getContext(): DateContext
    {
        return $this->context;
    }
}
