<?php

namespace RavenDB\Exceptions\Documents;

use RavenDB\Exceptions\RavenException;
use Throwable;

class DocumentDoesNotExistException extends RavenException
{
    public function __construct(?string $id = null, ?Throwable $cause = null) {
        parent::__construct("Document '" . ($id ?? '')  . "' does not exist.", $cause);
    }
}
