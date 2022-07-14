<?php

namespace RavenDB\Documents\Operations\CompareExchange;

use RavenDB\Documents\Session\EntityToJson;
use RavenDB\Documents\Conventions\DocumentConventions;

class CompareExchangeValueJsonConverter
{
    public static function convertToJson($value, ?DocumentConventions $conventions)
    {
        if ($value === null) {
            return null;
        }

        if (!is_object($value)) {
            return $value;
        }

        return EntityToJson::convertEntityToJsonStatic($value, $conventions, null);
    }
}
