<?php

namespace RavenDB\Exceptions;

class MalformedURLException extends RavenException
{
    public const MESSAGE = 'Malformed URL exception';

    public function __construct()
    {
        parent::__construct(self::MESSAGE);
    }
}
