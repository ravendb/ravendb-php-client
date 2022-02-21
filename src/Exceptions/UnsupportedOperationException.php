<?php

namespace RavenDB\Exceptions;

class UnsupportedOperationException extends RavenException
{
    const MESSAGE = 'Unsupported operation exception';

    public function __construct()
    {
        parent::__construct(self::MESSAGE);
    }
}
