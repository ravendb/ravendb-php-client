<?php

namespace RavenDB\Json;

use _PHPStan_76800bfb5\Nette\NotImplementedException;
use RavenDB\Documents\Session\MetadataDictionaryInterface;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Type\StringSet;

class MetadataAsDictionary implements MetadataDictionaryInterface
{
    private MetadataDictionaryInterface $parent;
    private string $parentKey;

    private array  $metadata;
    private ?object $source;

    private bool $dirty = false;

    public function __construct(array $metadata = array())
    {
        $this->metadata = $metadata;
        $this->source = null;
    }

    static public function fromObject(object $object, ?MetadataDictionaryInterface $parent = null, ?string $parentKey = null): MetadataAsDictionary
    {
        $instance = new self();

        $instance->source = $object;
        $instance->metadata = [];

        return $instance;
    }

    static public function fromObjectAndParent(object $object, ?MetadataDictionaryInterface $parent = null, ?string $parentKey = null): MetadataAsDictionary
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

    private function initialize(object $metadata): void
    {
        $this->dirty = true;
        $this->metadata = [];
        $fields = get_object_vars($metadata);
        foreach ($fields as $fieldName) {
            $this->metadata[$fieldName] = $this->convertValue($fieldName, $metadata->$fieldName);
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

        return count($this->source);
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
            return $this->metadata[$key];
        }

        return $this->convertValue($key, $this->source->get($key));
    }

//    public static MetadataAsDictionary materializeFromJson(ObjectNode metadata) {
//        MetadataAsDictionary result = new MetadataAsDictionary((Map<String, Object>) null);
//        result.initialize(metadata);
//
//        return result;
//    }
//
//    @Override
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
//
//    @SuppressWarnings("ConstantConditions")
//    @Override
//    public boolean containsKey(Object key) {
//        if (_metadata != null) {
//            return _metadata.containsKey(key);
//        }
//
//        return _source.has((String)key);
//    }
//
//    @Override
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

//    @Override
//    public String getString(String key) {
//        Object obj = get(key);
//        return obj != null ? obj.toString() : null;
//    }
//
//    @Override
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
