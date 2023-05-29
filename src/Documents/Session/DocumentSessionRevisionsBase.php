<?php

namespace RavenDB\Documents\Session;

use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Utils\StringUtils;

class DocumentSessionRevisionsBase extends AdvancedSessionExtensionBase
{
    public function __construct(?InMemoryDocumentSessionOperations $session)
    {
        parent::__construct($session);
    }

    public function forceRevisionCreationFor(null|string|object $idOrEntity, ?ForceRevisionStrategy $strategy = null): void
    {
        if ($strategy == null) {
            $strategy = ForceRevisionStrategy::Before();
        }

        if ($idOrEntity == null) {
            throw new IllegalArgumentException("Entity cannot be null");
        }

        if (is_object($idOrEntity)) {
            $documentInfo = $this->session->documentsByEntity->get($idOrEntity);
            if ($documentInfo == null) {
                throw new IllegalStateException("Cannot create a revision for the requested entity because it is Not tracked by the session");
            }
            $id = $documentInfo->getId();
        } else {
            $id = $idOrEntity;
        }

        $this->addIdToList($id, $strategy);
    }

    private function addIdToList(?string $id, ?ForceRevisionStrategy $requestedStrategy): void
    {
        if (StringUtils::isEmpty($id)) {
            throw new IllegalArgumentException("Id cannot be null or empty");
        }


        $existingStrategy = null;
        if ($this->session->idsForCreatingForcedRevisions->offsetExists($id)) {
            $existingStrategy = $this->session->idsForCreatingForcedRevisions[$id];
        }
        $idAlreadyAdded = $existingStrategy != null;
        if ($idAlreadyAdded && $existingStrategy->getValue() != $requestedStrategy->getValue()) {
            throw new IllegalStateException("A request for creating a revision was already made for document "
                    . $id . " in the current session but with a different force strategy." . "New strategy requested: "
                    . $requestedStrategy . ". Previous strategy: " . $existingStrategy . " .");
        }

        if (!$idAlreadyAdded) {
            $this->session->idsForCreatingForcedRevisions->offsetSet($id, $requestedStrategy);
        }
    }
}
