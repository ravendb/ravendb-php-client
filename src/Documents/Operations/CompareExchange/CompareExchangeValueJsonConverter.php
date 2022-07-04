<?php

namespace RavenDB\Documents\Operations\CompareExchange;

use RavenDB\Documents\Session\EntityToJson;
use RavenDB\Documents\Conventions\DocumentConventions;

class CompareExchangeValueJsonConverter
{
    public static function convertToJson(?object $value, ?DocumentConventions $conventions): ?array
    {
        if ($value == null) {
            return null;
        }

        // @todo: with tests we should check do we need this line
//        if (ClassUtils.isPrimitiveOrWrapper(value.getClass()) || value instanceof String || value.getClass().isArray()) {
//            return value;
//        }

        return EntityToJson::convertEntityToJsonStatic($value, $conventions, null);
    }
}
