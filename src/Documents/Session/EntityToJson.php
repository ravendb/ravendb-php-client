<?php

namespace RavenDB\Documents\Session;

use ArrayObject;
use RavenDB\Constants\DocumentsMetadata;
use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Extensions\EntityMapper;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Serializer;

use Ds\Map as DSMap;
use Throwable;

// @todo: rewrite this class
class EntityToJson
{
    private InMemoryDocumentSessionOperations $session;

    /**
     * All the listeners for this session
     * @param InMemoryDocumentSessionOperations $session Session to use
     */
    public function __construct(InMemoryDocumentSessionOperations $session)
    {
        $this->missingDictionary = new DSMap();

        $this->session = $session;
    }

    private ?DSMap $missingDictionary = null;

    public function getMissingDictionary(): ?DSMap
    {
        return $this->missingDictionary;
    }

    /**
     * @param object|array $entity
     * @param DocumentInfo|null $documentInfo
     *
     * @return array
     */
    public function convertEntityToJson($entity, ?DocumentInfo $documentInfo): array
    {
        // maybe we don't need to do anything?
        if (is_array($entity)) {
            return $entity;
        }

        if ($documentInfo != null) {
            $this->session->onBeforeConversionToDocumentInvoke($documentInfo->getId(), $entity);
        }

        $document = $this->convertEntityToJsonInternal($entity, $this->session->getConventions(), $documentInfo);

        if ($documentInfo != null) {
            $this->session->onAfterConversionToDocumentInvoke($documentInfo->getId(), $entity,$document);
        }

        return $document;
    }

    private static function convertEntityToJsonInternal(
        $entity,
        DocumentConventions $conventions,
        ?DocumentInfo $documentInfo,
        bool $removeIdentityProperty = true
    ) {
        $mapper = $conventions->getEntityMapper();

        $jsonNode = $mapper->normalize($entity);

        self::writeMetadata($mapper, $jsonNode, $documentInfo);

        if ($removeIdentityProperty) {
            self::tryRemoveIdentityProperty($jsonNode, get_class($entity), $conventions);
        }

        return $jsonNode;
    }

    /**
     * @param mixed               $entity
     * @param DocumentConventions $conventions
     * @param DocumentInfo|null   $documentInfo
     * @param bool                $removeIdentityProperty
     *
     * @return array|ArrayObject|bool|float|int|mixed|string|null
     */
    public static function convertEntityToJsonStatic(
        $entity,
        DocumentConventions $conventions,
        ?DocumentInfo $documentInfo,
        bool $removeIdentityProperty = true
    ) {
        if (is_array($entity)) {
            return $entity;
        }

        return self::convertEntityToJsonInternal($entity, $conventions, $documentInfo, $removeIdentityProperty);
    }

    private static function writeMetadata(Serializer $mapper, &$jsonNode, ?DocumentInfo $documentInfo): void
    {
        if ($documentInfo == null) {
            return;
        }
        $setMetadata = false;
        $metadataNode = [];

        if ($documentInfo->getMetadata() != null && count($documentInfo->getMetadata()) > 0) {
            $setMetadata = true;

            foreach ($documentInfo->getMetadata() as $key => $value) {
                $metadataNode[$key] = $value;
            }
        } elseif ($documentInfo->getMetadataInstance() != null) {
            $setMetadata = true;
            foreach ($documentInfo->getMetadataInstance() as $key => $value) {
                $metadataNode[$key] = $mapper->normalize($value);
            }
        }

        if ($documentInfo->getCollection() != null) {
            $setMetadata = true;
            $metadataNode[DocumentsMetadata::COLLECTION] = $mapper->normalize($documentInfo->getCollection());
        }

        if ($setMetadata) {
            $jsonNode[DocumentsMetadata::KEY] = $metadataNode;
        }
    }

    /**
     * Converts a json object to an entity.
     * @param ?string $entityType Class of entity
     * @param ?string $id ID of entity
     * @param array $document Raw entity
     * @param bool $trackEntity Track entity
     * @return object Entity instance
     *
     * @throws ExceptionInterface
     */
    public function convertToEntity(?string $entityType, ?string $id, array $document, bool $trackEntity): object
    {
        try {
            if ($entityType == null) {
                return (object)$document;
            }

            $this->session->onBeforeConversionToEntityInvoke($id, $entityType, $document);

            $defaultValue = InMemoryDocumentSessionOperations::getDefaultValue($entityType);
            $entity = $defaultValue;

            //TODO: if track! -> RegisterMissingProperties

            $documentType = $this->session->getConventions()->getPhpClass($id, $document);
            if ($documentType != null) {
                $type = $this->session->getConventions()->getPhpClassByName($documentType);
                if (is_subclass_of($type, $entityType)) {
                    $entity = $this->session->getConventions()->getEntityMapper()->denormalize($document, $type);
                }
            }

            if ($entity == $defaultValue) {
                $entity = $this->session->getConventions()->getEntityMapper()->denormalize($document, $entityType, 'json');
            }

            $projectionNode = array_key_exists(DocumentsMetadata::PROJECTION, $document) ? $document[DocumentsMetadata::PROJECTION] : null;
            $isProjection = ($projectionNode != null) && is_bool($projectionNode) && !empty($projectionNode);

            if ($id != null) {
                $this->session->getGenerateEntityIdOnTheClient()->trySetIdentity($entity, $id, $isProjection);
            }

            $this->session->onAfterConversionToEntityInvoke($id, $document, $entity);

            return $entity;
        } catch (Throwable $e) {
            throw new IllegalStateException("Could not convert document " . $id . " to entity of type " . $entityType, $e);
        }
    }

    public function populateEntity(?object &$entity, ?string $id, array $document): void
    {
        if ($id == null) {
            throw new IllegalArgumentException("Id cannot be null");
        }

        self::populateEntityStatic($entity, $document, $this->session->getConventions()->getEntityMapper());

        $this->session->getGenerateEntityIdOnTheClient()->trySetIdentity($entity, $id);
    }

    public static function populateEntityStatic(?object &$entity, ?array $document, ?EntityMapper $objectMapper): void
    {
        if ($entity == null) {
            throw new IllegalArgumentException("Entity cannot be null");
        }
        if ($document == null) {
            throw new IllegalArgumentException("Document cannot be null");
        }
        if ($objectMapper == null) {
            throw new IllegalArgumentException("ObjectMapper cannot be null");
        }

        try {
            $objectMapper->updateValue($entity, $document);
        } catch (Throwable $e) {
            throw new IllegalStateException("Could not populate entity. " . $e->getMessage());
        }
    }

    private static function tryRemoveIdentityProperty(array &$document, string $className, DocumentConventions $conventions): bool
    {
        $identityProperty = $conventions->getIdentityProperty($className);

        if ($identityProperty == null) {
            return false;
        }

        if (array_key_exists($identityProperty, $document)) {
            unset($document[$identityProperty]);
        }

        return true;
    }

    /**
     * @param string                   $entityClass
     * @param string|null              $id
     * @param object|array             $document
     * @param DocumentConventions|null $conventions
     *
     * @return object
     */
    public static function convertToEntityStatic(string $entityClass, ?string $id, $document, ?DocumentConventions $conventions): object
    {
        try {

            $defaultValue = InMemoryDocumentSessionOperations::getDefaultValue($entityClass);

            $entity = $defaultValue;

            $documentType = !is_array($document) ? $conventions->getPhpClassName($document) : null;
            if ($documentType != null) {
                $className = $documentType; //class.forName(documentType);
//                if (clazz != null && entityClass.isAssignableFrom(clazz)) {
                if (is_a($entityClass, $className, true)) {
                    $entity = $document; //$conventions->getEntityMapper()->denormalize($document, $className);
                }
            }

            if ($entity == null) {
                $arr = $conventions->getEntityMapper()->normalize($document);
                $entity = $conventions->getEntityMapper()->denormalize($arr, $entityClass);
            }

            return $entity;
        } catch (Throwable $e) {
            throw new IllegalStateException('Could not convert document ' . $id . ' to entity of type ' . $entityClass, $e);
        }
    }

    public function removeFromMissing(object $entity): void
    {
        if ($this->missingDictionary->hasKey($entity)) {
            $this->missingDictionary->remove($entity);
        }
    }

    public function clear(): void
    {
        $this->missingDictionary->clear();
    }
}
