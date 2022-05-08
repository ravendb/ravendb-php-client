<?php

namespace RavenDB\Documents\Session;

use RavenDB\Constants\DocumentsMetadata;
use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Extensions\EntityMapper;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Serializer;

use Ds\Map as DSMap;

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
        object $entity,
        DocumentConventions $conventions,
        ?DocumentInfo $documentInfo,
        bool $removeIdentityProperty = true
    ): array {
        $mapper = $conventions->getEntityMapper();

        $jsonNode = $mapper->normalize($entity);

         self::writeMetadata($mapper, $jsonNode, $documentInfo);

        if ($removeIdentityProperty) {
            self::tryRemoveIdentityProperty($jsonNode, get_class($entity), $conventions);
        }

        return $jsonNode;
    }

    public static function convertEntityToJsonStatic(
        object $entity,
        DocumentConventions $conventions,
        ?DocumentInfo $documentInfo,
        bool $removeIdentityProperty = true
    ): array {
        if (is_array($entity)) {
            return $entity;
        }

        return self::convertEntityToJsonInternal($entity, $conventions, $documentInfo, $removeIdentityProperty);
    }

    private static function writeMetadata(Serializer $mapper, array &$jsonNode, ?DocumentInfo $documentInfo): void
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
     * @param string $entityType Class of entity
     * @param string $id ID of entity
     * @param array $document Raw entity
     * @param bool $trackEntity Track entity
     * @return object Entity instance
     *
     * @throws ExceptionInterface
     */
    public function convertToEntity(string $entityType, string $id, array $document, bool $trackEntity): object
    {
        // @todo: implement method
        // note: this method is not implemented at all, just made to work somehow
        $entity = $this->session->getConventions()->getEntityMapper()->denormalize($document, $entityType, 'json');

        return $entity;
    }

//    @SuppressWarnings("unchecked")
//    public Object convertToEntity(Class entityType, String id, ObjectNode document, boolean trackEntity) {
//        try {
//            if (ObjectNode.class.equals(entityType)) {
//                return document;
//            }
//
//            Reference<ObjectNode> documentRef = new Reference<>(document);
//            _session.onBeforeConversionToEntityInvoke(id, entityType, documentRef);
//            document = documentRef.value;
//
//            Object defaultValue = InMemoryDocumentSessionOperations.getDefaultValue(entityType);
//            Object entity = defaultValue;
//
//            //TODO: if track! -> RegisterMissingProperties
//
//            String documentType =_session.getConventions().getJavaClass(id, document);
//            if (documentType != null) {
//                Class type = _session.getConventions().getJavaClassByName(documentType);
//                if (entityType.isAssignableFrom(type)) {
//                    entity = _session.getConventions().getEntityMapper().treeToValue(document, type);
//                }
//            }
//
//            if (entity == defaultValue) {
//                entity = _session.getConventions().getEntityMapper().treeToValue(document, entityType);
//            }
//
//            JsonNode projectionNode = document.get(Constants.Documents.Metadata.PROJECTION);
//            boolean isProjection = projectionNode != null && projectionNode.isBoolean() && projectionNode.asBoolean();
//
//            if (id != null) {
//                _session.getGenerateEntityIdOnTheClient().trySetIdentity(entity, id, isProjection);
//            }
//
//            _session.onAfterConversionToEntityInvoke(id, document, entity);
//
//            return entity;
//        } catch (Exception e) {
//            throw new IllegalStateException("Could not convert document " + id + " to entity of type " + entityType.getName(), e);
//        }
//    }

    public function populateEntity(?object &$entity, ?string $id, array $document): void
    {
        if ($id == null) {
            throw new IllegalArgumentException("Id cannot be null");
        }

        self::populateEntityS($entity, $document, $this->session->getConventions()->getEntityMapper());

        $this->session->getGenerateEntityIdOnTheClient()->trySetIdentity($entity, $id);
    }

    public static function populateEntityS(?object &$entity, ?array $document, ?EntityMapper $objectMapper): void
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
        } catch (\Throwable $e) {
            throw new IllegalStateException("Could not populate entity", $e);
        }
    }

    private static function tryRemoveIdentityProperty(array $document, string $className, DocumentConventions $conventions): bool
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

//    @SuppressWarnings("UnnecessaryLocalVariable")
//    public static Object convertToEntity(Class< ? > entityClass, String id, ObjectNode document, DocumentConventions conventions) {
//        try {
//
//            Object defaultValue = InMemoryDocumentSessionOperations.getDefaultValue(entityClass);
//
//            Object entity = defaultValue;
//
//            String documentType = conventions.getJavaClass(id, document);
//            if (documentType != null) {
//                Class<? > clazz = class.forName(documentType);
//                if (clazz != null && entityClass.isAssignableFrom(clazz)) {
//                    entity = conventions.getEntityMapper().treeToValue(document, clazz);
//                }
//            }
//
//            if (entity == null) {
//                entity = conventions.getEntityMapper().treeToValue(document, entityClass);
//            }
//
//            return entity;
//        } catch (Exception e) {
//            throw new IllegalStateException("Could not convert document " + id + " to entity of type " + entityClass, e);
//        }
//    }
//
//    public void removeFromMissing(Object entity) {
//        _missingDictionary.remove(entity);
//    }

    public function clear(): void
    {
        $this->missingDictionary->clear();
    }
}
