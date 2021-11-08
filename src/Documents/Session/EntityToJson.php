<?php

namespace RavenDB\Documents\Session;

use Symfony\Component\Serializer\Exception\ExceptionInterface;

class EntityToJson
{
    private InMemoryDocumentSessionOperations $session;

    public function __construct(InMemoryDocumentSessionOperations $session)
    {
        $this->session = $session;
    }

    /**
     * @throws ExceptionInterface
     */
    public function convertToEntity(string $entityType, string $id, array $document, bool $trackEntity)
    {
        return $this->session->getConventions()->getEntityMapper()->denormalize($document, $entityType, 'json');
    }
}
