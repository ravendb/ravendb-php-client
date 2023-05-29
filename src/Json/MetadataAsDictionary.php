<?php

namespace RavenDB\Json;

use RavenDB\Documents\Session\MetadataDictionaryInterface;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Exceptions\NotImplementedException;
use RavenDB\Type\StringSet;

class MetadataAsDictionary implements MetadataDictionaryInterface
{
    private ?MetadataDictionaryInterface $parent = null;
    private ?string $parentKey = null;

    private ?array $metadata = null;
    private ?array $source = null;

    private bool $dirty = false;

    public function __construct(?array $metadata = null)
    {
        $this->metadata = $metadata;
        $this->source = null;
    }

    static public function fromObject(array $object, ?MetadataDictionaryInterface $parent = null, ?string $parentKey = null): MetadataAsDictionary
    {
        $instance = new self();

        $instance->source = $object;
        $instance->metadata = [];

        return $instance;
    }

    static public function fromObjectAndParent(array $object, ?MetadataDictionaryInterface $parent = null, ?string $parentKey = null): MetadataAsDictionary
    {
        $instance = self::fromObject($object);

        if ($parent == null) {
            throw new IllegalArgumentException("Parent cannot be null");
        }

        if ($parentKey == null) {
            throw new IllegalArgumentException("ParentKey cannot be null");
        }

        return $instance;
    }

    public function isDirty(): bool
    {
        return $this->dirty;
    }

    private function initialize(?array $metadata): void
    {
        $this->dirty = true;
        $this->metadata = [];
        $fields = $metadata ? array_keys($metadata) : [];
        foreach ($fields as $fieldName) {
            $this->metadata[$fieldName] = $this->convertValue($fieldName, $metadata[$fieldName]);
        }

        if ($this->parent != null) { // mark parent as dirty
            $this->parent[$this->parentKey] = $this;
        }
    }

    /**
     * @throws IllegalArgumentException
     */
    private function convertValue(string $key, $value)
    {
        if (is_null($value)) {
            return null;
        }

        if (is_numeric($value)) {
            return $value;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return $value;
        }

        if (is_object($value)) {
            $dictionary = MetadataAsDictionary::fromObjectAndParent($value, $this, $key);
            $dictionary->initialize($value);
            return $dictionary;
        }

        if (is_array($value)) {
            $result = [];

            foreach ($value as $k => $item) {
                $result[$k] = $this->convertValue($key, $item);
            }

            return $result;
        }

        throw new NotImplementedException("Implement support for numbers and more");
    }

    public function size(): int
    {
        if ($this->metadata != null) {
            return count($this->metadata);
        }

        return $this->source != null ? count($this->source) : 0;
    }

    /**
     * @param string|null $key
     * @param null|mixed $value
     * @return mixed
     */
    public function put(?string $key, $value)
    {
        if ($this->metadata == null) {
            $this->initialize($this->source);
        }
        $this->dirty = true;

        return $this->metadata[$key] = $value;
    }

    public function get($key)
    {
        if ($this->metadata != null) {
            if (array_key_exists($key, $this->metadata)) {
                return $this->metadata[$key];
            }
            return null;
        }

        return $this->convertValue($key, $this->source[$key]);
    }

    public static function materializeFromJson(?array $metadata): MetadataAsDictionary
    {
        $result = new MetadataAsDictionary(null);
        $result->initialize($metadata);

        return $result;
    }

//    public IMetadataDictionary[] getObjects(String key) {
//        Object[] obj = (Object[]) get(key);
//        if (obj == null) {
//            return null;
//        }
//        IMetadataDictionary[] list = new IMetadataDictionary[obj.length];
//        for (int i = 0; i < obj.length; i++) {
//            list[i] = (IMetadataDictionary) obj[i];
//        }
//
//        return list;
//    }

    public function isEmpty(): bool
    {
        return $this->size() == 0;
    }

//    @Override
//    public void putAll(Map<? extends String, ? > m) {
//        if (_metadata == null) {
//            initialize(_source);
//        }
//        dirty = true;
//
//        _metadata.putAll(m);
//    }
//
//    @Override
//    public void clear() {
//        if (_metadata == null) {
//            initialize(_source);
//        }
//        dirty = true;
//
//        _metadata.clear();
//    }

    public function containsKey($key): bool
    {
        if ($this->metadata != null) {
            return array_key_exists($key, $this->metadata);
        }

        return array_key_exists($key, $this->source);
    }

    public function count(): int
    {
        if ($this->metadata == null) {
            return 0;
        }

        return count($this->metadata);
    }

    public function toSimpleArray(): array
    {
        if ($this->metadata == null) {
            return [];
        }
        return $this->metadata;
    }

//    public Set<Entry<String, Object>> entrySet() {
//        if (_metadata == null) {
//            initialize(_source);
//        }
//
//        return _metadata.entrySet();
//    }
//
//    @Override
//    public Object remove(Object key) {
//        if (_metadata == null) {
//            initialize(_source);
//        }
//        dirty = true;
//
//        return _metadata.remove(key);
//    }
//
//    @Override
//    public boolean containsValue(Object value) {
//        if (_metadata == null) {
//            initialize(_source);
//        }
//
//        return _metadata.containsValue(value);
//    }
//
//    @Override
//    public Collection<Object> values() {
//        if (_metadata == null) {
//            initialize(_source);
//        }
//
//        return _metadata.values();
//    }
//

    public function keySet(): StringSet
    {
        if ($this->metadata == null) {
            $this->initialize($this->source);
        }

        return StringSet::fromArray(array_keys($this->metadata));
    }

    public function getString(?string $key): ?string
    {
        $obj = $this->get($key);
        return $obj != null ? strval($obj) : null;
    }


//    public long getLong(String key) {
//        Object obj = get(key);
//        if (obj == null) {
//            return 0L;
//        }
//
//        if (obj instanceof Long) {
//            return (Long) obj;
//        }
//
//        return Long.parseLong(obj.toString());
//    }
//
//    @Override
//    public boolean getBoolean(String key) {
//        Object obj = get(key);
//        if (obj == null) {
//            return false;
//        }
//
//        if (obj instanceof Boolean) {
//            return (boolean) obj;
//        }
//
//        return Boolean.parseBoolean(obj.toString());
//    }
//
//    @Override
//    public double getDouble(String key) {
//        Object obj = get(key);
//        if (obj == null) {
//            return 0;
//        }
//
//        if (obj instanceof Double) {
//            return (Double) obj;
//        }
//
//        return Double.parseDouble(obj.toString());
//    }
//
//    @Override
//    public IMetadataDictionary getObject(String key) {
//        Object obj = get(key);
//        if (obj == null) {
//            return null;
//        }
//
//        return (IMetadataDictionary) obj;
//    }
}
