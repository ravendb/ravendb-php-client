<?php

namespace RavenDB\Documents\Session;

use RavenDB\Type\TypedArrayInterface;

interface MetadataDictionaryInterface extends TypedArrayInterface
{
    public function isDirty(): bool;

    /**
     * @param string|null $key
     * @param null|mixed $value
     * @return mixed
     */
    public function put(?string $key, $value);


    public function get(?string $key);

    public function containsKey($key): bool;
    public function count(): int;
    public function toSimpleArray(): array;

//    public function getObjects(string key): MetadataDictionaryInterface;

//    public function getString(string key): string;
//
//    long getLong(String key);
//
//    boolean getBoolean(String key);
//
//    double getDouble(String key);
//
//    IMetadataDictionary getObject(String key);
}
