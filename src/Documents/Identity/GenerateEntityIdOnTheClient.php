<?php

namespace RavenDB\Documents\Identity;

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

    public function __construct(DocumentConventions $conventions, callable $generateId)
    {
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
     * @param ?Object $entity    Entity to get id from
     *
     * @return null|string      Return id that was read from entity, otherwise null
     *
     * @throws InvalidArgumentException|IllegalStateException
     */
    public function tryGetIdFromInstance(?Object $entity): ?string
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

            $value = $entity->$identityProperty;

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
    public function trySetIdentity(object $entity, string $id, bool $isProjection = false): void
    {
        $this->trySetIdentityInternal($entity, $id, $isProjection);
    }

    /**
     * @throws RavenException
     * @throws IllegalStateException
     */
    private function trySetIdentityInternal(object $entity, string $id, bool $isProjection): void
    {
        $entityType = get_class($entity);
        $identityProperty = $this->conventions->getIdentityProperty($entityType);

        if ($identityProperty == null) {
            return;
        }

        try {
            $identity = property_exists($identityProperty, $entity) ? $entity->$identityProperty : null;
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
    private function setPropertyOrField(string $propertyOrFieldType, object $entity, string $field, string $id): void
    {
        try {
            if ($propertyOrFieldType == 'string') {
                $entity->$field = $id;
            } else {
                throw new InvalidArgumentException("Cannot set identity value '" . $id .
                    "' on field " . $propertyOrFieldType . " because field type is not string.");
            }
        } catch (Throwable $e) {
            throw new IllegalStateException($e->getMessage());
        }
    }
}
