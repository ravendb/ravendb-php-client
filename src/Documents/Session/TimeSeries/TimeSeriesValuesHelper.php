<?php

namespace RavenDB\Documents\Session\TimeSeries;

use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Exceptions\RavenException;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionObject;

class TimeSeriesValuesHelper
{
//    private static final ConcurrentMap<Class< ? ><!--, SortedMap<Byte, Tuple<Field, String>>> _cache = new ConcurrentHashMap<>();-->
    private static array $cache = [];

    /* SortedMap<Byte, Tuple<Field, String>> */
    public static function getFieldsMapping(?string $className): ?array
    {
        if (array_key_exists($className, self::$cache)) {
            return self::$cache[$className];
        }

        $mapping = null;
        $reflect = new ReflectionClass($className);

        $fields = $reflect->getProperties();

        foreach ($fields as $field) {
            /** @var array<ReflectionAttribute> $attributes */
            $attributes = $field->getAttributes(TimeSeriesValue::class);
            if (count($attributes) == 0) {
                continue;
            }

            if (($field->getType()->getName() != 'float')) {
                throw new IllegalStateException("Cannot create a mapping for '" . $reflect->getShortName() . "' class, because field '" . $field->getName() . "' is not a float.");
            };

            /** @var TimeSeriesValue $tsv */
            $tsv = $attributes[0]->newInstance();
            $i = $tsv->getIdx();

            if ($mapping == null) {
                $mapping = [];
            };

            if (array_key_exists($i, $mapping)) {
                    throw new IllegalStateException("Cannot map '" . $field->getName() . " to " . $i . ", since '" . $mapping[$i]->getName() . "' already mapped to it.");
            }

            $name = $tsv->getName() ?? $field->getName();

            $mapping[$i] = $name;
        }

        if ($mapping == null) {
            return null;
        }

        if ((array_key_first($mapping) !== 0) || (array_key_last($mapping) !== (count($mapping)-1))) {
                throw new IllegalStateException("The mapping of '" . $reflect->getShortName() . "' must contain consecutive values starting from 0.");

        }

        self::$cache[$className] = $mapping;

        return $mapping;
    }

    public static function getValues(?string $className, mixed $obj): ?array
    {
        $mapping = self::getFieldsMapping($className);
        if ($mapping == null) {
            return null;
        }

        try {
            $values = [];
            $reflection = new ReflectionObject($obj);
            foreach ($mapping as $key => $value) {
                $values[$key] = $reflection->getProperty($value)->getValue($obj);
            }

            return $values;
        } catch (\Throwable $e) {
            throw new RavenException("Unable to read time series values.", $e);
        }
    }

    /**
     * @param string $className
     * @param array<float> $values
     * @param bool $asRollup
     * @return mixed
     */
    public static function setFields(string $className, ?array $values, bool $asRollup = false): mixed
    {
        if ($values == null) {
            return null;
        }

        $mapping = self::getFieldsMapping($className);
        if ($mapping == null) {
            return null;
        }

        try {
            $obj = new $className();

            $reflection = new ReflectionObject($obj);
            foreach ($mapping as $key => $propertyName) {
                $value = NAN;
                if ($key < count($values)) {
                    if ($asRollup) {
                        $key *= 6;
                    }
                    $value = $values[$key];
                }
                $reflection->getProperty($propertyName)->setValue($obj, $value);
            }

            return $obj;
        } catch (\Throwable $e) {
            throw new RavenException("Unable to read time series values.", $e);
        }
    }
}
