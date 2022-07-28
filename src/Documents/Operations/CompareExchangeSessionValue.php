<?php

namespace RavenDB\Documents\Operations;

// @todo: implement this class
use RavenDB\Extensions\EntityMapper;
use RavenDB\Constants\CompareExchange;
use RavenDB\Exceptions\RavenException;
use RavenDB\Constants\DocumentsMetadata;
use RavenDB\Documents\Session\EntityToJson;
use RavenDB\Exceptions\UnsupportedOperationException;
use RavenDB\Documents\Session\MetadataDictionaryInterface;
use RavenDB\Documents\Commands\Batches\CommandDataInterface;
use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Commands\Batches\PutCompareExchangeCommandData;
use RavenDB\Documents\Operations\CompareExchange\CompareExchangeValue;
use RavenDB\Documents\Commands\Batches\DeleteCompareExchangeCommandData;
use RavenDB\Documents\Operations\CompareExchange\CompareExchangeValueInterface;
use RavenDB\Documents\Operations\CompareExchange\CompareExchangeValueState;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Documents\Operations\CompareExchange\CompareExchangeValueJsonConverter;

class CompareExchangeSessionValue
{
    private string $key;
    private ?int $index;
    private ?CompareExchangeValue $originalValue = null;

    private ?CompareExchangeValueInterface $value = null;
    private ?CompareExchangeValueState $state;


    /**
     * @param CompareExchangeValue|string|null  $keyOrValue
     * @param int|null                       $index
     * @param CompareExchangeValueState|null $state
     */
    public function __construct($keyOrValue, ?int $index = null, ?CompareExchangeValueState $state = null)
    {
        if ($keyOrValue instanceof CompareExchangeValue) {
            $this->initWithValue($keyOrValue);
            return;
        }

        $this->initWithKey($keyOrValue, $index, $state);
    }

    public function initWithKey(?string $key, ?int $index, ?CompareExchangeValueState $state): void
    {
        if ($key == null) {
            throw new IllegalArgumentException("Key cannot be null");
        }

        $this->key   = $key;
        $this->index = $index;
        $this->state = $state;
    }

    private function initWithValue(CompareExchangeValue $value): void
    {
        $state = $value->getIndex() >= 0 ? CompareExchangeValueState::none() : CompareExchangeValueState::missing();
        $this->initWithKey($value->getKey(), $value->getIndex(), $state);

        if ($value->getIndex() > 0) {
            $this->originalValue = $value;
        }
    }

    public function getValue(?string $className, ?DocumentConventions $conventions): ?CompareExchangeValue
    {
        switch ($this->state->getValue()) {
            case CompareExchangeValueState::NONE:
            case CompareExchangeValueState::CREATED:
                if ($this->value instanceof CompareExchangeValue) {
                    return $this->value;
                }

                if ($this->value != null) {
                    throw new IllegalStateException("Value cannot be null");
                }

                $entity = null;
                if ($this->originalValue != null && $this->originalValue->getValue() !== null) {
                    if ($className == null) {
                        try {
                            $originalValue = $this->originalValue->getValue();
                            if (!is_array($originalValue)) {
                                $entity = $originalValue;
                            } else {
                                $entityJsonValue = null;
                                if (array_key_exists(CompareExchange::OBJECT_FIELD_NAME, $originalValue)) {
                                    $entityJsonValue = $originalValue[CompareExchange::OBJECT_FIELD_NAME];
                                }

                                $entity = !empty($entityJsonValue) ? $conventions->getEntityMapper()->denormalize($entityJsonValue, $className) : null;
                            }
                        } catch (\Throwable $ex) {
                            throw new RavenException("Unable to read compare exchange value: " . $this->originalValue->getValue(), $ex);
                        }
                    } else {
                        $entity = EntityToJson::convertToEntityStatic($className, $this->key, $this->originalValue->getValue(), $conventions);
                    }
                }

                $this->value = new CompareExchangeValue($this->key, $this->index, $entity);

                return $this->value;

            case CompareExchangeValueState::MISSING:
            case CompareExchangeValueState::DELETED:
                return null;
            default:
                throw new UnsupportedOperationException("Not supported state: " . $this->state);
        }
    }

    public function & create($item): CompareExchangeValue
    {
        $this->assertState();

        if ($this->value != null) {
            throw new IllegalStateException("The compare exchange value with key '" . $this->key . "' is already tracked.");
        }

        $this->index = 0;
        $this->value = new CompareExchangeValue($this->key, $this->index, $item);
        $this->state = CompareExchangeValueState::created();
        return $this->value;
    }

    public function delete(int $index): void
    {
        $this->assertState();

        $this->index = $index;
        $this->state = CompareExchangeValueState::deleted();
    }

    private function assertState(): void
    {
        switch ($this->state->getValue()) {
            case CompareExchangeValueState::NONE:
            case CompareExchangeValueState::MISSING:
                return;
            case CompareExchangeValueState::CREATED:
                throw new IllegalStateException("The compare exchange value with key '" . $this->key . "' was already stored.");
            case CompareExchangeValueState::DELETED:
                throw new IllegalStateException("The compare exchange value with key '" . $this->key . "' was already deleted.");
        }
    }

    public function getCommand(DocumentConventions $conventions): ?CommandDataInterface
    {
        switch ($this->state) {
            case CompareExchangeValueState::NONE:
            case CompareExchangeValueState::CREATED:
                if ($this->value == null) {
                    return null;
                }

                $entity = CompareExchangeValueJsonConverter::convertToJson($this->value->getValue(), $conventions);

                $entityJson = is_array($entity) ? $entity : null;
                $metadata = null;
                if ($this->value->hasMetadata() && $this->value->getMetadata()->count() != 0) {
                    $metadata = $this->prepareMetadataForPut($this->key, $this->value->getMetadata(), $conventions);
                }
                $entityToInsert = null;
                if ($entityJson == null) {
                    $entityJson = $entityToInsert = $this->convertEntity($this->key, $entity, $conventions->getEntityMapper(), $metadata);
                }

                $newValue = new CompareExchangeValue($this->key, $this->index, $entityJson);
                $hasChanged = $this->originalValue == null || $this->hasChanged($this->originalValue, $newValue);
                $this->originalValue = $newValue;

                if (!$hasChanged) {
                    return null;
                }

                if ($entityToInsert == null) {
                    $entityToInsert = $this->convertEntity($this->key, $entity, $conventions->getEntityMapper(), $metadata);
                }

                return new PutCompareExchangeCommandData($newValue->getKey(), $entityToInsert, $newValue->getIndex());
            case CompareExchangeValueState::DELETED:
                return new DeleteCompareExchangeCommandData($this->key, $this->index);
            case CompareExchangeValueState::MISSING:
                return null;
            default:
                throw new IllegalStateException("Not supported state: " . $this->state);
        }
    }


    private function convertEntity(?string $key, $entity, ?EntityMapper $objectMapper, ?array $metadata): array
    {
        $objectNode = [];
        $objectNode[CompareExchange::OBJECT_FIELD_NAME] = $objectMapper->normalize($entity);
        if ($metadata != null) {
            $objectNode[DocumentsMetadata::KEY] = $metadata;
        }
        return $objectNode;
    }

    public function hasChanged(?CompareExchangeValue $originalValue, ?CompareExchangeValue $newValue): bool
    {
        if ($originalValue === $newValue) {
            return false;
        }

        if (strcasecmp($originalValue->getKey(), $newValue->getKey()) != 0) {
            throw new IllegalStateException("Keys do not match. Expected '" . $originalValue->getKey() . " but was: " . $newValue->getKey());
        }

        if ($originalValue->getIndex() != $newValue->getIndex()) {
            return true;
        }

        return $originalValue->getValue() != $newValue->getValue();
    }

    public function updateState(int $index): void
    {
        $this->index = $index;
        $this->state = CompareExchangeValueState::none();

        if ($this->originalValue != null) {
            $this->originalValue->setIndex($index);
        }

        if ($this->value != null) {
            $this->value->setIndex($index);
        }
    }

    public function updateValue(?CompareExchangeValue $value, ?EntityMapper $mapper): void
    {
        $this->index = $value->getIndex();
        $state = $value->getIndex() >= 0 ? CompareExchangeValueState::none() : CompareExchangeValueState::missing();

        $this->originalValue = $value;

        if ($this->value != null) {
            $this->value->setIndex($this->index);

            if ($this->value->getValue() != null) {
                $document = $mapper->normalize($value->getValue());
                EntityToJson::populateEntityStatic($this->value->getValue(), $document, $mapper);
            }
        }
    }

    public static function prepareMetadataForPut(
        ?string                      $key,
        ?MetadataDictionaryInterface $metadataDictionary,
        ?DocumentConventions         $conventions): array
    {

        if ($metadataDictionary->containsKey(DocumentsMetadata::EXPIRES)) {
            $obj = $metadataDictionary->get(DocumentsMetadata::EXPIRES);
            if ($obj == null) {
                self::throwInvalidExpiresMetadata("The values of " . DocumentsMetadata::EXPIRES . " metadata for compare exchange '" . $key . " is null.");
            }
            if (!($obj instanceof \DateTimeInterface) && !is_string($obj)) {
                self::throwInvalidExpiresMetadata("The class of " . DocumentsMetadata::EXPIRES . " metadata for compare exchange '" . $key . " is not valid. Use the following type: Date or string.");
            }
        }

        return $conventions->getEntityMapper()->normalize($metadataDictionary->toSimpleArray());
    }

    private static function throwInvalidExpiresMetadata(?string $message): void
    {
        throw new IllegalArgumentException($message);
    }
}
