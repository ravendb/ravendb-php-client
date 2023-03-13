<?php

namespace RavenDB\Documents\Session;

use RavenDB\Documents\Commands\Batches\CommandType;
use RavenDB\Documents\Commands\Batches\CountersBatchCommandData;
use RavenDB\Documents\Operations\Counters\CounterOperation;
use RavenDB\Documents\Operations\Counters\CounterOperationType;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Utils\StringUtils;

abstract class SessionCountersBase
{
    protected ?string $docId = null;
    protected ?InMemoryDocumentSessionOperations $session = null;

    public function __construct(?InMemoryDocumentSessionOperations $session, null|string|object $idOrEntity)
    {
        if (is_null($idOrEntity)) {
            $this->throwEntityNotInSession($idOrEntity);
        }

        if (empty($idOrEntity)) {
            throw new IllegalArgumentException("DocumentId cannot be empty");
        }

        $this->docId = is_string($idOrEntity) ? $idOrEntity : $idOrEntity->getId();
        $this->session = $session;
    }

    public function increment(?string $counter, int $delta = 1): void
    {
        if (StringUtils::isBlank($counter)) {
            throw new IllegalArgumentException("Counter cannot be empty");
        }

        $counterOp = new CounterOperation();
        $counterOp->setType(CounterOperationType::increment());
        $counterOp->setCounterName($counter);
        $counterOp->setDelta($delta);

        $documentInfo = $this->session->documentsById->getValue($this->docId);
        if ($documentInfo != null && $this->session->deletedEntities->contains($documentInfo->getEntity())) {
            $this->throwDocumentAlreadyDeletedInSession($this->docId, $counter);
        }

        $index = $this->session->deferredCommandsMap->getIndexFor($this->docId, CommandType::counters(), null);
        if ($index != null) {
            $command = $this->session->deferredCommandsMap->get($index);
            /** @var CountersBatchCommandData $countersBatchCommandData */
            $countersBatchCommandData = $command;
            if ($countersBatchCommandData->hasDelete($counter)) {
                $this->throwIncrementCounterAfterDeleteAttempt($this->docId, $counter);
            }

            $countersBatchCommandData->getCounters()->getOperations()->append($counterOp);
        } else {
            $this->session->defer(new CountersBatchCommandData($this->docId, $counterOp));
        }
    }

    public function delete(?string $counter): void
    {
        if (StringUtils::isBlank($counter)) {
            throw new IllegalArgumentException("Counter is required");
        }

        $index = $this->session->deferredCommandsMap->getIndexFor($this->docId, CommandType::delete(), null);
        if ($index != null) {
            return; // no-op
        }

        $documentInfo = $this->session->documentsById->getValue($this->docId);

        if ($documentInfo != null && $this->session->deletedEntities->contains($documentInfo->getEntity())) {
            return;  //no-op
        }

        $counterOp = new CounterOperation();
        $counterOp->setType(CounterOperationType::delete());
        $counterOp->setCounterName($counter);

        $index = $this->session->deferredCommandsMap->getIndexFor($this->docId, CommandType::counters(), null);
        if ($index != null) {
            $command = $this->session->deferredCommandsMap->get($index);
            /** @var CountersBatchCommandData $countersBatchCommandData */
            $countersBatchCommandData = $command;
            if ($countersBatchCommandData->hasIncrement($counter)) {
                $this->throwDeleteCounterAfterIncrementAttempt($this->docId, $counter);
            }

            $countersBatchCommandData->getCounters()->getOperations()->append($counterOp);
        } else {
            $this->session->defer(new CountersBatchCommandData($this->docId, $counterOp));
        }

        if (array_key_exists($this->docId, $this->session->getCountersByDocId())) {
            $cache = $this->session->getCountersByDocId()[$this->docId];
            if ($cache[1]->offsetExists($counter)) {
                $cache[1]->offsetUnset($counter);
            }
            $this->session->getCountersByDocId()[$this->docId] = $cache;
        }
    }

    protected function throwEntityNotInSession(object $entity): void
    {
        throw new IllegalArgumentException("Entity is not associated with the session, cannot add counter to it. " .
            "Use documentId instead of track the entity in the session");
    }

    private static function throwIncrementCounterAfterDeleteAttempt(?string $documentId, ?string $counter): void
    {
        throw new IllegalStateException("Can't increment counter " . $counter . " of document " . $documentId . ", there is a deferred command registered to delete a counter with the same name.");
    }

    private static function throwDeleteCounterAfterIncrementAttempt(?string $documentId, ?string $counter): void
    {
        throw new IllegalStateException("Can't delete counter " . $counter . " of document " . $documentId . ", there is a deferred command registered to increment a counter with the same name.");
    }

    private static function throwDocumentAlreadyDeletedInSession(?string $documentId, ?string $counter): void
    {
        throw new IllegalStateException("Can't increment counter " . $counter . " of document " . $documentId . ", the document was already deleted in this session.");
    }
}
