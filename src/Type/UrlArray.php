<?php

namespace RavenDB\Type;

class UrlArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(Url::class);
    }

    public function offsetSet($key, $value): void
    {
        $v = is_string($value) ? new Url($value) : $value;
        parent::offsetSet($key, $v);
    }
}
