<?php

namespace RavenDB\Documents\Session;

use RavenDB\Type\TypedArray;

/**
 * This is helper class that should be mapped as:
 * DocumentInfoDatesArray = Map<String, Map<Date, DocumentInfo>>
 */
class DocumentInfoDatesArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(DocumentInfoDates::class);
    }
}
