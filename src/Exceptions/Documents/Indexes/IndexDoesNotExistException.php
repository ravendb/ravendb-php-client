<?php

namespace RavenDB\Exceptions\Documents\Indexes;

use RavenDB\Exceptions\RavenException;
use Throwable;

class IndexDoesNotExistException extends RavenException
{
    public function __construct(string $message = '', ?Throwable $cause = null) {
        parent::__construct($message, $cause);
    }
}
