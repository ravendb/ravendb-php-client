<?php

namespace RavenDB\Documents\Operations\CompareExchange;

use RavenDB\Json\MetadataAsDictionary;
use RavenDB\Constants\DocumentsMetadata;
use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Documents\Conventions\DocumentConventions;

class CompareExchangeValueResultParser
{
    public static function getValues(
        ?string              $className,
        ?array               $response,
        bool                 $materializeMetadata,
        ?DocumentConventions $conventions
    ): ?CompareExchangeValueArray
    {
        $results = new CompareExchangeValueArray();
        if (empty($response)) { // 404
            return $results;
        }

        $jsonResponse = $response; //json_decode($response, true);

        if (!array_key_exists('Results', $jsonResponse) || empty($jsonResponse['Results'])) {
            throw new IllegalStateException('Response is invalid. Results is missing.');
        }
        $items = $jsonResponse['Results'];

        foreach ($items as $item) {
            if ($item == null) {
                throw new IllegalStateException('Response is invalid. Item is null');
            }

            $value = self::getSingleValue($className, $item, $materializeMetadata, $conventions);
            $results->offsetSet($value->getKey(), $value);
        }

        return $results;
    }

    public static function getValue(
        ?string              $className,
        ?array              $response,
        bool                 $materializeMetadata,
        ?DocumentConventions $conventions
    ): ?CompareExchangeValue
    {
        if (empty($response)) {
            return null;
        }

        $values = self::getValues($className, $response, $materializeMetadata, $conventions);
        if (empty($values)) {
            return null;
        }
        return $values->first();
    }

    public static function getSingleValue(
        ?string              $className,
        array                $item,
        bool                 $materializeMetadata,
        ?DocumentConventions $conventions
    ): ?CompareExchangeValue
    {
        if (empty($item)) {
            return null;
        }

        if (!array_key_exists('Key', $item) || $item['Key'] == null) {
            throw new IllegalStateException("Response is invalid. Key is missing.");
        }
        $keyNode = $item["Key"];

        if (!array_key_exists('Index', $item) || $item['Index'] == null) {
            throw new IllegalStateException("Response is invalid. Index is missing");
        }
        $indexNode = $item["Index"];

        if (!array_key_exists('Value', $item)) {
            throw new IllegalStateException("Response is invalid. Value is missing.");
        }
        $rawJsonNode = $item["Value"];

        $raw = is_array($rawJsonNode) ? $rawJsonNode : null;

        $key   = strval($keyNode);
        $index = intval($indexNode);

        if ($raw == null) {
            return new CompareExchangeValue($key, $index, null);
        }

        $metadata = null;
        $bjro     = array_key_exists(DocumentsMetadata::KEY, $raw) ? $raw[DocumentsMetadata::KEY] : null;
        if ($bjro != null && is_array($bjro)) {
            $metadata = !$materializeMetadata ? new MetadataAsDictionary($bjro) : MetadataAsDictionary::materializeFromJson($bjro);
        }

//        if (clazz.isPrimitive() || String.class.equals(clazz)) {
//        if ($className != null) {
            // simple
            $value = null;

            if ($raw != null) {
                $rawValue = array_key_exists('Object', $raw) ? $raw["Object"] : null;
                $value = $className == null ? $rawValue : $conventions->getEntityMapper()->denormalize($rawValue, $className);
            }

            return new CompareExchangeValue($key, $index, $value, $metadata);
//        } else if (ObjectNode.class.equals(clazz)) {
//            if (raw == null || !raw.has(Constants.CompareExchange.OBJECT_FIELD_NAME)) {
//                return new CompareExchangeValue<>(key, index, null, metadata);
//            }
//
//            Object rawValue = raw.get(Constants.CompareExchange.OBJECT_FIELD_NAME);
//            if (rawValue == null) {
//                return new CompareExchangeValue<>(key, index, null, metadata);
//            } else if (rawValue instanceof ObjectNode) {
//                return new CompareExchangeValue<>(key, index, (T) rawValue, metadata);
//            } else {
//                return new CompareExchangeValue<>(key, index, (T) raw, metadata);
//            }
//        } else {
//            JsonNode object = raw.get(Constants.CompareExchange.OBJECT_FIELD_NAME);
//            if (object == null || object.isNull()) {
//                return new CompareExchangeValue<>(key, index, Defaults.defaultValue(clazz), metadata);
//            } else {
//                T converted = conventions.getEntityMapper().convertValue(object, clazz);
//                return new CompareExchangeValue<>(key, index, converted, metadata);
//            }
//        }
    }
}
