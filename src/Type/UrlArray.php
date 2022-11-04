<?php

namespace RavenDB\Type;

class UrlArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(Url::class);
    }

    public static function fromArray(array $items, bool $nullAllowed = false): UrlArray
    {
        $a = new UrlArray();
        $a->setNullAllowed($nullAllowed);

        foreach ($items as $key => $value) {
            $url = is_string($value) ? new Url($value) : $value;
            $a->offsetSet($key, $url);
        }

        return $a;

    }
}
