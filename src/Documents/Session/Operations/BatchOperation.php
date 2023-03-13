<?php

namespace RavenDB\Documents\Session\Operations;

use RavenDB\Constants\DocumentsMetadata;
use RavenDB\Documents\Commands\Batches\ClusterWideBatchCommand;
use RavenDB\Documents\Commands\Batches\CommandType;
use RavenDB\Documents\Commands\Batches\SingleNodeBatchCommand;
use RavenDB\Documents\Operations\PatchStatus;
use RavenDB\Documents\Session\ActionsToRunOnSuccess;
use RavenDB\Documents\Session\AfterSaveChangesEventArgs;
use RavenDB\Documents\Session\DocumentInfo;
use RavenDB\Documents\Session\DocumentInfoArray;
use RavenDB\Documents\Session\InMemoryDocumentSessionOperations;
use RavenDB\Exceptions\ClientVersionMismatchException;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Json\BatchCommandResult;
use RavenDB\Type\ExtendedArrayObject;

// !status: IN PROGRESS
class BatchOperation
{
    private InMemoryDocumentSessionOperations $session;

    private array $entities;
    private int $sessionCommandsCount = 0;
    private int $allCommandsCount = 0;
    private ActionsToRunOnSuccess $onSuccessfulRequest;

    private ?DocumentInfoArray $modifications = null;

    public function __construct(InMemoryDocumentSessionOperations $session)
    {
        $this->session = $session;
    }

    /**
     * @throws IllegalArgumentException|IllegalStateException
     */
    public function createRequest(): ?SingleNodeBatchCommand
    {
        $result = $this->session->prepareForSaveChanges();
        $this->onSuccessfulRequest = $result->getOnSuccess();
        $this->sessionCommandsCount = count($result->getSessionCommands());

        foreach ($result->getDeferredCommands() as $deferredCommand) {
            $result->addSessionCommand($deferredCommand);
        }

        $this->session->validateClusterTransaction($result);

        $this->allCommandsCount = count($result->getSessionCommands());

        if ($this->allCommandsCount == 0) {
            return null;
        }

        $this->session->incrementRequestCount();

        $this->entities = $result->getEntities();

        if ($this->session->getTransactionMode()->isClusterWide()) {
            return new ClusterWideBatchCommand(
                $this->session->getConventions(),
                $result->getSessionCommands(),
                $result->getOptions(),
                $this->session->disableAtomicDocumentWritesInClusterWideTransaction
            );
        }

        return new SingleNodeBatchCommand(
            $this->session->getConventions(),
            $result->getSessionCommands(),
            $result->getOptions()
        );
    }

    public function setResult(BatchCommandResult $result): void
    {
        $getCommandType = function ($batchResult): CommandType {
            $type = null;
            if (key_exists('Type', $batchResult)) {
                $type = $batchResult['Type'];
            }

            if ($type == null) {
                return CommandType::none();
            }

            return CommandType::parseCSharpValue($type);
        };

        if (empty($result->getResults())) {
            $this->throwOnNullResults();
            return;
        }

        $this->onSuccessfulRequest->clearSessionStateAfterSuccessfulSaveChanges();

        if ($this->session->getTransactionMode()->isClusterWide()) {
            if ($result->getTransactionIndex() <= 0) {
                throw new ClientVersionMismatchException(
                    "Cluster transaction was send to a node that is not supporting it. " .
                    "So it was executed ONLY on the requested node on " . $this->session->getRequestExecutor()->getUrl());
            }
        }

        for ($i = 0; $i < $this->sessionCommandsCount; $i++) {
            if ($i >= count($result->getResults())) {
                continue;
            }
            $batchResult = $result->getResults()[$i];
            if ($batchResult == null) {
                continue;
            }

            $type = $getCommandType($batchResult);

            switch ($type->getValue()) {
                case CommandType::PUT:
                    $this->handlePut($i, $batchResult, false);
                    break;
                case CommandType::FORCE_REVISION_CREATION:
//                    handleForceRevisionCreation(batchResult);
                    break;
                case CommandType::DELETE:
                    $this->handleDelete($batchResult);
                    break;
                case CommandType::COMPARE_EXCHANGE_PUT:
                    $this->handleCompareExchangePut($batchResult);
                    break;
                case CommandType::COMPARE_EXCHANGE_DELETE:
                    $this->handleCompareExchangeDelete($batchResult);
                    break;
                default:
                    throw new IllegalStateException("Command " . $type . " is not supported");
            }
        }

        for ($i = $this->sessionCommandsCount; $i < $this->allCommandsCount; $i++) {
            if ($i >= count($result->getResults())) {
                continue;
            }
            $batchResult = $result->getResults()[$i];
            if ($batchResult == null) {
                continue;
            }

            $type = $getCommandType($batchResult);

            switch ($type->getValue()) {
                case CommandType::PUT:
                    $this->handlePut($i, $batchResult, true);
                    break;
                case CommandType::DELETE:
                    $this->handleDelete($batchResult);
                    break;
                case CommandType::PATCH:
                    $this->handlePatch($batchResult);
                    break;
                case CommandType::ATTACHMENT_PUT:
                    $this->handleAttachmentPut($batchResult);
                    break;
                case CommandType::ATTACHMENT_DELETE:
                    $this->handleAttachmentDelete($batchResult);
                    break;
                case CommandType::ATTACHMENT_MOVE:
                    $this->handleAttachmentMove($batchResult);
                    break;
                case CommandType::ATTACHMENT_COPY:
                    $this->handleAttachmentCopy($batchResult);
                    break;
                case CommandType::COMPARE_EXCHANGE_PUT:
                case CommandType::COMPARE_EXCHANGE_DELETE:
                case CommandType::FORCE_REVISION_CREATION:
                    break;
                case CommandType::COUNTERS:
                    $this->handleCounters($batchResult);
                    break;
                case CommandType::TIME_SERIES:
//                    //TODO: RavenDB-13474 add to time series cache
                    break;
                case CommandType::TIME_SERIES_COPY:
                    break;
                case CommandType::BATCH_PATCH:
                    break;
                default:
                    throw new IllegalStateException("Command " . $type . " is not supported");
            }
        }
        $this->finalizeResult();
    }

    private function finalizeResult(): void
    {
        if (($this->modifications == null) || !count($this->modifications)) {
            return;
        }

        /**
         * @var string $id
         * @var DocumentInfo $documentInfo
         */
        foreach ($this->modifications as $id => $documentInfo) {
            $this->applyMetadataModifications($id, $documentInfo);
        }
    }

    private function applyMetadataModifications(string $id, DocumentInfo &$documentInfo): void
    {
        $documentInfo->setMetadataInstance(null);
        $metadata = $documentInfo->getMetadata();
        $cloned =  $metadata;

        $cloned[DocumentsMetadata::CHANGE_VECTOR] = $documentInfo->getChangeVector() ?? $metadata[DocumentsMetadata::CHANGE_VECTOR];
        $documentInfo->setMetadata($cloned);

        $document = $documentInfo->getDocument();
        $documentCopy = $document;
        $documentCopy[DocumentsMetadata::KEY] = $documentInfo->getMetadata();

        $documentInfo->setDocument($documentCopy);
    }

    private function &getOrAddModifications(
        string       $id,
        DocumentInfo &$documentInfo,
        bool         $applyModifications
    ): DocumentInfo
    {
        if ($this->modifications == null) {
            $this->modifications = new DocumentInfoArray();
        }

        $modifiedDocumentInfo = $this->modifications->getValue($id);
        if ($modifiedDocumentInfo != null) {
            if ($applyModifications) {
                $this->applyMetadataModifications($id, $modifiedDocumentInfo);
            }
        } else {
            $this->modifications->offsetSet($id, $modifiedDocumentInfo = $documentInfo);
        }

        return $modifiedDocumentInfo;
    }

    private function handleCompareExchangePut($batchResult): void
    {
        $this->handleCompareExchangeInternal(CommandType::compareExchangePut(), $batchResult);
    }

    private function handleCompareExchangeDelete($batchResult): void
    {
        $this->handleCompareExchangeInternal(CommandType::compareExchangeDelete(), $batchResult);
    }

    private function handleCompareExchangeInternal(CommandType $commandType, $batchResult): void
    {
        if (!array_key_exists('Key', $batchResult) || empty($batchResult['Key'])) {
            $this->throwMissingField($commandType, 'Key');
        }
        $key = strval($batchResult['Key']);

        if (!array_key_exists('Index', $batchResult) || empty($batchResult['Index'])) {
            $this->throwMissingField($commandType, 'Index');
        }
        $index = intval($batchResult['Index']);

        $clusterSession = $this->session->getClusterSession();
        $clusterSession->updateState($key, $index);
    }

    private function handleAttachmentCopy(array $batchResult): void
    {
        $this->handleAttachmentPutInternal($batchResult, CommandType::attachmentCopy(), "Id", "Name", "DocumentChangeVector");
    }

    private function handleAttachmentMove(array $batchResult): void
    {
        $this->handleAttachmentDeleteInternal($batchResult, CommandType::attachmentMove(), "Id", "Name", "DocumentChangeVector");
        $this->handleAttachmentPutInternal($batchResult, CommandType::attachmentMove(), "DestinationId", "DestinationName", "DocumentChangeVector");
    }

    private function handleAttachmentDelete(array $batchResult): void
    {
        $this->handleAttachmentDeleteInternal($batchResult, CommandType::attachmentDelete(), DocumentsMetadata::ID, "Name", "DocumentChangeVector");
    }

    private function handleAttachmentDeleteInternal(array $batchResult, CommandType $type, ?string $idFieldName, ?string $attachmentNameFieldName, ?string $documentChangeVectorFieldName): void
    {
        $id = $this->getStringField($batchResult, $type, $idFieldName);

        $sessionDocumentInfo = $this->session->documentsById->getValue($id);
        if ($sessionDocumentInfo == null) {
            return;
        }

        $documentInfo = $this->getOrAddModifications($id, $sessionDocumentInfo, true);

        $documentChangeVector = $this->getStringField($batchResult, $type, $documentChangeVectorFieldName, false);
        if ($documentChangeVector != null) {
            $documentInfo->setChangeVector($documentChangeVector);
        }

        if (!array_key_exists(DocumentsMetadata::ATTACHMENTS, $documentInfo->getMetadata())) {
            return;
        }
        $attachmentsJson = $documentInfo->getMetadata()[DocumentsMetadata::ATTACHMENTS];

        if (count($attachmentsJson) == 0) {
            return;
        }

        $name = $this->getStringField($batchResult, $type, $attachmentNameFieldName);

        $documentInfo->getMetadata()[DocumentsMetadata::ATTACHMENTS] = [];

        foreach ($attachmentsJson as $attachment) {
            $attachmentName = $this->getStringField($attachment, $type, 'Name');
            if ($attachmentName == $name) {
                continue;
            }

            $documentInfo->getMetadata()[DocumentsMetadata::ATTACHMENTS][] = $attachment;
        }
    }

    private function handleAttachmentPut(array $batchResult): void
    {
        $this->handleAttachmentPutInternal($batchResult, CommandType::attachmentPut(), "Id", "Name", "DocumentChangeVector");
    }

    private function handleAttachmentPutInternal(array $batchResult, CommandType $type, ?string $idFieldName, ?string $attachmentNameFieldName, ?string $documentChangeVectorFieldName): void
    {
        $id = $this->getStringField($batchResult, $type, $idFieldName);

        $sessionDocumentInfo = $this->session->documentsById->getValue($id);
        if ($sessionDocumentInfo == null) {
            return;
        }

        $documentInfo = $this->getOrAddModifications($id, $sessionDocumentInfo, false);

        $documentChangeVector = $this->getStringField($batchResult, $type, $documentChangeVectorFieldName, false);
        if ($documentChangeVector != null) {
            $documentInfo->setChangeVector($documentChangeVector);
        }

        if (!array_key_exists(DocumentsMetadata::ATTACHMENTS, $documentInfo->getMetadata())) {
            $attachments = [];
            $documentInfo->getMetadata()[DocumentsMetadata::ATTACHMENTS] = $attachments;
        }

        $dynamicNode = [];
        $dynamicNode["ChangeVector"] = $this->getStringField($batchResult, $type, "ChangeVector");
        $dynamicNode["ContentType"] = $this->getStringField($batchResult, $type, "ContentType");
        $dynamicNode["Hash"] =  $this->getStringField($batchResult, $type, "Hash");
        $dynamicNode["Name"] = $this->getStringField($batchResult, $type, "Name");
        $dynamicNode["Size"] = $this->getIntField($batchResult, $type, "Size");

        $documentInfo->getMetadata()[DocumentsMetadata::ATTACHMENTS][] = $dynamicNode;
    }

    private function handlePatch(array $batchResult): void
    {
        $patchStatus = null;
        if (array_key_exists('PatchStatus', $batchResult)) {
            $patchStatus = $batchResult['PatchStatus'];
        }
        if (($patchStatus == null) || empty($patchStatus)) {
            self::throwMissingField(CommandType::patch(), 'PatchStatus');
        }

        $status = new PatchStatus($patchStatus);

        switch ($status->getValue()) {
            case PatchStatus::CREATED:
            case PatchStatus::PATCHED:
                if (!array_key_exists('ModifiedDocument', $batchResult)) {
                    return;
                }
                $document = $batchResult['ModifiedDocument'];

                $id = $this->getStringField($batchResult, CommandType::put(), "Id");

                $sessionDocumentInfo = $this->session->documentsById->getValue($id);
                if ($sessionDocumentInfo == null) {
                    return;
                }

                $documentInfo = $this->getOrAddModifications($id, $sessionDocumentInfo, true);

                $changeVector = $this->getStringField($batchResult, CommandType::patch(), "ChangeVector");
                $lastModified = $this->getStringField($batchResult, CommandType::patch(), "LastModified");

                $documentInfo->setChangeVector($changeVector);

                $metadata = $documentInfo->getMetadata();
                $metadata[DocumentsMetadata::ID] = $id;
                $metadata[DocumentsMetadata::CHANGE_VECTOR] = $changeVector;
                $metadata[DocumentsMetadata::LAST_MODIFIED] = $lastModified;
                $documentInfo->setMetadata($metadata);

                $documentInfo->setDocument($document);
                $this->applyMetadataModifications($id, $documentInfo);

                if ($documentInfo->getEntity() != null) {
                    $entity = $documentInfo->getEntity();
                    $this->session->getEntityToJson()->populateEntity($entity, $id, $documentInfo->getDocument());
                    $afterSaveChangesEventArgs = new AfterSaveChangesEventArgs($this->session, $documentInfo->getId(), $documentInfo->getEntity());
                    $this->session->onAfterSaveChangesInvoke($afterSaveChangesEventArgs);
                }

                break;
        }
    }

    private function handleDelete(array $batchResult): void
    {
        $this->handleDeleteInternal($batchResult, CommandType::delete());
    }

    private function handleDeleteInternal(array $batchResult, CommandType $type): void
    {
        $id = $this->getStringField($batchResult, $type, "Id");

        /** @var DocumentInfo $documentInfo */
        $documentInfo = $this->session->documentsById->getValue($id);
        if ($documentInfo == null) {
            return;
        }

        $this->session->documentsById->remove($id);

        if ($documentInfo->getEntity() != null) {
            $this->session->documentsByEntity->remove($documentInfo->getEntity());
            $this->session->deletedEntities->remove($documentInfo->getEntity());
        }
    }

//    private void handleForceRevisionCreation(ObjectNode batchResult) {
//        // When forcing a revision for a document that does Not have any revisions yet then the HasRevisions flag is added to the document.
//        // In this case we need to update the tracked entities in the session with the document new change-vector.
//
//        if (!getBooleanField(batchResult, CommandType.FORCE_REVISION_CREATION, "RevisionCreated")) {
//            // no forced revision was created...nothing to update.
//            return;
//        }
//
//        String id = getStringField(batchResult, CommandType.FORCE_REVISION_CREATION, Constants.Documents.Metadata.ID);
//        String changeVector = getStringField(batchResult, CommandType.FORCE_REVISION_CREATION, Constants.Documents.Metadata.CHANGE_VECTOR);
//
//        DocumentInfo documentInfo = _session.documentsById.getValue(id);
//        if (documentInfo == null) {
//            return;
//        }
//
//        documentInfo.setChangeVector(changeVector);
//
//        handleMetadataModifications(documentInfo, batchResult, id, changeVector);
//
//        AfterSaveChangesEventArgs afterSaveChangesEventArgs = new AfterSaveChangesEventArgs(_session, documentInfo.getId(), documentInfo.getEntity());
//        _session.onAfterSaveChangesInvoke(afterSaveChangesEventArgs);
//    }
//
    private function handlePut(int $index, array $batchResult, bool $isDeferred): void
    {
        $entity = null;
        $documentInfo = null;

        if (!$isDeferred) {
            $entity = $this->entities[$index];

            $documentInfo = $this->session->documentsByEntity->get($entity);

            if ($documentInfo == null) {
                return;
            }
        }

        $id = $this->getStringField($batchResult, CommandType::put(), DocumentsMetadata::ID);
        $changeVector = $this->getStringField($batchResult, CommandType::put(), DocumentsMetadata::CHANGE_VECTOR);

        if ($isDeferred) {
            $sessionDocumentInfo = $this->session->documentsById->getValue($id);
            if ($sessionDocumentInfo == null) {
                return;
            }

            $documentInfo = $this->getOrAddModifications($id, $sessionDocumentInfo, true);
            $entity = $documentInfo->getEntity();
        }

        $this->handleMetadataModifications($documentInfo, $batchResult, $id, $changeVector);

        $this->session->documentsById->add($documentInfo);

        if ($entity != null) {
            $this->session->getGenerateEntityIdOnTheClient()->trySetIdentity($entity, $id);
        }

        $afterSaveChangesEventArgs = new AfterSaveChangesEventArgs($this->session, $documentInfo->getId(), $documentInfo->getEntity());
        $this->session->onAfterSaveChangesInvoke($afterSaveChangesEventArgs);
    }

    private function handleMetadataModifications(
        DocumentInfo $documentInfo,
        array        $batchResult,
        string       $id,
        string       $changeVector
    ): void
    {
        foreach ($batchResult as $key => $value) {
            if ($key == "Type") continue;

            $metadata = $documentInfo->getMetadata();
            $metadata[$key] = $value;
            $documentInfo->setMetadata($metadata);
        }
        $documentInfo->setId($id);
        $documentInfo->setChangeVector($changeVector);

        $this->applyMetadataModifications($id, $documentInfo);
    }

    private function handleCounters(array $batchResult): void
    {
        $docId = $this->getStringField($batchResult, CommandType::counters(), "Id");

        $countersDetail = array_key_exists('CountersDetail', $batchResult) ? $batchResult['CountersDetail'] : null;
        if ($countersDetail === null) {
            $this->throwMissingField(CommandType::counters(), "CountersDetail");
        }

        $counters = array_key_exists('Counters', $countersDetail) ? $countersDetail['Counters'] : null;
        if ($counters === null) {
            $this->throwMissingField(CommandType::counters(), "Counters");
        }

        if (array_key_exists($docId, $this->session->getCountersByDocId())) {
            $cache = $this->session->getCountersByDocId()[$docId];
        } else {
            $array = new ExtendedArrayObject();
            $array->setKeysCaseInsensitive(true);
            $cache = [false, $array];
        }

        $changeVector = $this->getStringField($batchResult, CommandType::counters(), "DocumentChangeVector", false);
        if ($changeVector != null) {
            $documentInfo = $this->session->documentsById->getValue($docId);
            $documentInfo?->setChangeVector($changeVector);
        }

        foreach ($counters as $counter) {
            $name = array_key_exists('CounterName', $counter) ? $counter['CounterName'] : null;
            $value = array_key_exists('TotalValue', $counter) ? $counter['TotalValue'] : null;

            if (!empty($name) && !empty($value)) {
                $cache[1][$name] = $value;
            }
        }

        $this->session->getCountersByDocId()[$docId] = $cache;
    }

    private function getStringField(
        array       $json,
        CommandType $type,
        string      $fieldName,
        bool        $throwOnMissing = true
    ): string
    {
        $jsonNode = null;
        if (key_exists($fieldName, $json)) {
            $jsonNode = $json[$fieldName];
        }

        if (($jsonNode === null) && $throwOnMissing) {
            self::throwMissingField($type, $fieldName);
        }

        return (string)$jsonNode;
    }

    private static function getIntField(array $json, CommandType $type, ?string $fieldName): int
    {
        $jsonNode = array_key_exists($fieldName, $json) ? $json[$fieldName] : null;
        if ($jsonNode == null || !is_int($jsonNode)) {
            self::throwMissingField($type, $fieldName);
        }

        return intval($jsonNode);
    }

//    private static boolean getBooleanField(ObjectNode json, CommandType type, String fieldName) {
//        JsonNode jsonNode = json.get(fieldName);
//        if (jsonNode == null || !jsonNode.isBoolean()) {
//            throwMissingField(type, fieldName);
//        }
//
//        return jsonNode.asBoolean();
//    }


    private static function throwMissingField(CommandType $type, string $fieldName): void
    {
        throw new IllegalStateException($type . " response is invalid. Field '" . $fieldName . "' is missing.");
    }

    private static function throwOnNullResults(): void
    {
        throw new IllegalStateException("Received empty response from the server. This is not supposed to happen and is likely a bug.");
    }
}
