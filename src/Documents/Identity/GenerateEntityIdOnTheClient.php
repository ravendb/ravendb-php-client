<?php

namespace RavenDB\Documents\Identity;

use RavenDB\Type\StringArray;
use Throwable;
use InvalidArgumentException;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Exceptions\RavenException;
use RavenDB\Utils\StringUtils;

class GenerateEntityIdOnTheClient
{
    private DocumentConventions $conventions;

    /** @var callable */
    private $generateId;

    private StringArray $identityGetterList;
    private StringArray $identitySetterList;

    public function __construct(DocumentConventions $conventions, callable $generateId)
    {
        $this->identityGetterList = new StringArray();
        $this->identitySetterList = new StringArray();

        $this->conventions = $conventions;
        $this->generateId = $generateId;
    }

    private function getIdentityProperty(string $entityType): ?string
    {
        return $this->conventions->getIdentityProperty($entityType);
    }

    /**
     * Attempts to get the document key from an instance
     *
     * @param ?Object $entity Entity to get id from
     *
     * @return null|string      Return id that was read from entity, otherwise null
     *
     * @throws InvalidArgumentException|IllegalStateException
     */
    public function tryGetIdFromInstance(?object $entity): ?string
    {
        if ($entity == null) {
            throw new InvalidArgumentException("Entity cannot be null");
        }

        try {
            $identityProperty = $this->getIdentityProperty(get_class($entity));

            if ($identityProperty == null) {
                return null;
            }

            if (!property_exists($entity, $identityProperty)) {
                return null;
            }

            $value = $this->extractPropertyValue($entity, $identityProperty);

            if (!is_string($value)) {
                return null;
            }

            return $value;
        } catch (Throwable $e) {
            throw new IllegalStateException($e->getMessage());
        }
    }

    /**
     * @throws IllegalStateException
     * @throws InvalidArgumentException
     */
    public function entityHasId(object $entity): bool
    {
        return $this->tryGetIdFromInstance($entity) !== null;
    }

    /**
     * Tries to get the identity.
     *
     * @throws IllegalStateException
     * @throws InvalidArgumentException
     */
    public function getOrGenerateDocumentId(object $entity): string
    {
        $id = $this->tryGetIdFromInstance($entity);

        if ($id == null) {
            // Generate the key up front
            $id = call_user_func($this->generateId, $entity);
        }

        if ($id != null && StringUtils::startsWith("/", $id)) {
            throw new IllegalStateException(
                "Cannot use value '" . $id . "' as a document id because it begins with a '/'"
            );
        }

        return $id;
    }

    /**
     * @throws IllegalStateException
     * @throws InvalidArgumentException
     */
    public function generateDocumentKeyForStorage(object $entity): string
    {
        $id = $this->getOrGenerateDocumentId($entity);
        $this->trySetIdentity($entity, $id);
        return $id;
    }

    /**
     * Tries to set the identity property
     */
    public function trySetIdentity(object &$entity, string $id, bool $isProjection = false): void
    {
        $this->trySetIdentityInternal($entity, $id, $isProjection);
    }

    /**
     * @throws RavenException
     * @throws IllegalStateException
     */
    private function trySetIdentityInternal(object &$entity, string $id, bool $isProjection): void
    {
        $entityType = get_class($entity);
        $identityProperty = $this->conventions->getIdentityProperty($entityType);

        if ($identityProperty == null) {
            return;
        }

        try {
            $identity = $this->extractPropertyValue($entity, $identityProperty);

            if ($isProjection && $identity != null) {
                // identity property was already set
                return;
            }
        } catch (Throwable $e) {
            throw new RavenException("Unable to read identity field: " . $e->getMessage());
        }

        //$identityProperty->getType()
        $identityPropertyType = 'string';
        $this->setPropertyOrField($identityPropertyType, $entity, $identityProperty, $id);
    }

    /**
     * @throws IllegalStateException
     */
    private function setPropertyOrField(string $propertyOrFieldType, object &$entity, string $field, string $id): void
    {
        try {
            if ($propertyOrFieldType == 'string') {
                $this->setStringPropertyOrField($entity, $field, $id);
            } else {
                throw new InvalidArgumentException("Cannot set identity value '" . $id .
                    "' on field " . $propertyOrFieldType . " because field type is not string.");
            }
        } catch (Throwable $e) {
            throw new IllegalStateException($e->getMessage());
        }
    }

    private function extractPropertyValue(object $entity, string $identityProperty)
    {
        $value = null;
        try {
            $value = $entity->$identityProperty;
        } catch (Throwable $e) {
            // ignore
        }

        $identityGetter = $this->identityGetterList->get(get_class($entity));
        if ($identityGetter == null) {
            $identityGetter = $this->getIdentityGetter($entity, $identityProperty);
            $this->identityGetterList->offsetSet(get_class($entity), $identityGetter);
        }

        try {
            $value = $entity->$identityGetter();
        } catch (Throwable $e) {
            // ignore
        }

        return $value;
    }

    private function getIdentityGetter(object $entity, string $identityProperty): ?string
    {

        try {
            $method = 'get' . ucfirst($identityProperty);
            if (method_exists($entity, $method)) {
                $value = $entity->$method();
            }
            return $method;
        } catch (Throwable $e) {
            // ignore
        }

        try {
            if (method_exists($entity, $identityProperty)) {
                $value = $entity->$identityProperty();
            }
            return $identityProperty;
        } catch (Throwable $e) {
            // ignore
        }

        try {
            $method = 'get_' . $identityProperty;
            if (method_exists($entity, $method)) {
                $value = $entity->$method();
            }
            return $method;
        } catch (Throwable $e) {
            // ignore
        }

        return null;
    }

    private function setStringPropertyOrField(object &$entity, ?string $identityProperty, ?string $value): void
    {
        try {
            $entity->$identityProperty = $value;
            return;
        } catch (Throwable $e) {
            // ignore
        }

        $identitySetter = $this->identitySetterList->get(get_class($entity));
        if ($identitySetter == null) {
            $identitySetter = $this->getIdentitySetter($entity, $identityProperty, $value);
            $this->identitySetterList->offsetSet(get_class($entity), $identitySetter);
        }

        try {
            $entity->$identitySetter($value);
        } catch (Throwable $e) {
            // ignore
        }
    }

    private function getIdentitySetter(object $entity, string $identityProperty, $value): ?string
    {
        try {
            $method = 'set' . ucfirst($identityProperty);
            if (method_exists($entity, $method)) {
                $entity->$method($value);
            }
            return $method;
        } catch (Throwable $e) {
            // ignore
        }

        try {
            $method = 'set_' . $identityProperty;
            if (method_exists($entity, $method)) {
                 $entity->$method($value);
            }
            return $method;
        } catch (Throwable $e) {
            // ignore
        }

        return null;
    }
}
