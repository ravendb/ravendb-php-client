<?php

namespace RavenDB\Type;

use DateTimeInterface;

class DateTimeArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(DateTimeInterface::class);
    }

    public static function fromArray(array $array): self
    {
        $dta = new self();

        foreach ($array as $key => $item) {
            $dta->offsetSet($key, $item);
        }
        return $dta;
    }
}
