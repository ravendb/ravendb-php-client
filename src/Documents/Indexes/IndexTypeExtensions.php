<?php

namespace RavenDB\Documents\Indexes;

// !status: DONE
class IndexTypeExtensions
{
    private function __construct() {}

    public static function isMap(IndexType $type): bool
    {
        return $type->isMap() || $type->isAutoMap() || $type->isJavaScriptMap();
    }

    public static function isMapReduce(IndexType $type): bool
    {
        return $type->isMapReduce() || $type->isAutoMapReduce() || $type->isJavaScriptMapReduce();
    }

    public static function isAuto(IndexType $type): bool
    {
        return $type->isAutoMap() || $type->isAutoMapReduce();
    }

    public static function isStale(IndexType $type): bool
    {
        return $type->isMap() || $type->isMapReduce() || $type->isJavaScriptMap() || $type->isJavaScriptMapReduce();
    }

    public static function isJavaScript(IndexType $type): bool
    {
        return $type->isJavaScriptMap() || $type->isJavaScriptMapReduce();
    }
}
