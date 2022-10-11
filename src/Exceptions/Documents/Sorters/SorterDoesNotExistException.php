<?php

namespace RavenDB\Exceptions\Documents\Sorters;

use RavenDB\Exceptions\RavenException;

class SorterDoesNotExistException extends RavenException
{
    public static function throwFor(?string $sorterName): SorterDoesNotExistException
    {
        throw new SorterDoesNotExistException("There is no sorter with '" . $sorterName . "' name.");
    }
}
