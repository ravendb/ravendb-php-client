<?php

namespace RavenDB\Type;

class UrlArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(Url::class);
    }
}
