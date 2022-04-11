<?php

namespace RavenDB\Documents\Session;

use RavenDB\Constants\DocumentsMetadata;
use RavenDB\Documents\Conventions\DocumentConventions;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Serializer;

use Ds\Map as DSMap;

class EntityToJson
{
    private InMemoryDocumentSessionOperations $session;

    private ?DSMap $missingDictionary = null;


    public function __construct(InMemoryDocumentSessionOperations $session)
    {
        $this->session = $session;
    }

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
//            Reference<ObjectNode> documentReference = new Reference<>(document);
//            _session.onAfterConversionToDocumentInvoke(documentInfo.getId(), entity, documentReference);
//            document = documentReference.value;
        }

        return $document;
    }

//        if (documentInfo != null) {
//            Reference<ObjectNode> documentReference = new Reference<>(document);
//            _session.onAfterConversionToDocumentInvoke(documentInfo.getId(), entity, documentReference);
//            document = documentReference.value;
//        }


    private function convertEntityToJsonInternal(
        object $entity,
        DocumentConventions $conventions,
        DocumentInfo $documentInfo,
        bool $removeIdentityProperty = true
    ): array {
        $mapper = $conventions->getEntityMapper();

        $jsonNode = $mapper->normalize($entity);

        // @todo: implement this method
        $this->writeMetadata($mapper, $jsonNode, $documentInfo);

        if ($removeIdentityProperty) {
            $this->tryRemoveIdentityProperty($jsonNode, get_class($entity), $conventions);
        }

        return $jsonNode;
    }


    /**
     * @throws ExceptionInterface
     */
    public function convertToEntity(string $entityType, string $id, array $document, bool $trackEntity)
    {
        return $this->session->getConventions()->getEntityMapper()->denormalize($document, $entityType, 'json');
    }

    private function tryRemoveIdentityProperty($document, string $className, DocumentConventions $conventions): bool
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

    private function writeMetadata(Serializer $mapper, array &$jsonNode, ?DocumentInfo $documentInfo): void
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
}
