<?php

namespace RavenDB\Documents\Session;

use Closure;
use DateTime;
use RavenDB\Constants\Headers;
use RavenDB\Constants\HttpStatusCode;
use RavenDB\Documents\Commands\ConditionalGetDocumentsCommand;
use RavenDB\Documents\Commands\MultiGet\GetRequestList;
use RavenDB\Documents\Lazy;
use RavenDB\Documents\Operations\TimeSeries\AbstractTimeSeriesRange;
use RavenDB\Documents\Operations\TimeSeries\TimeSeriesConfiguration;
use RavenDB\Documents\Session\Operations\Lazy\EagerSessionOperationsInterface;
use RavenDB\Documents\Session\Operations\Lazy\LazyLoadOperation;
use RavenDB\Documents\Session\Operations\Lazy\LazyOperationInterface;
use RavenDB\Documents\Session\Operations\Lazy\LazySessionOperations;
use RavenDB\Documents\Session\Operations\Lazy\LazySessionOperationsInterface;
use RavenDB\Documents\Session\Operations\MultiGetOperation;
use RavenDB\Documents\TimeSeries\TimeSeriesOperations;
use RavenDB\Exceptions\RavenException;
use RavenDB\Type\Duration;
use RavenDB\Utils\Stopwatch;
use ReflectionException;
use InvalidArgumentException;
use Ramsey\Uuid\UuidInterface;
use RavenDB\Json\MetadataAsDictionary;
use RavenDB\Constants\DocumentsMetadata;
use RavenDB\Documents\Commands\Batches\CommandType;
use RavenDB\Documents\Commands\Batches\PatchCommandData;
use RavenDB\Documents\Commands\GetDocumentsCommand;
use RavenDB\Documents\Commands\GetDocumentsResult;
use RavenDB\Documents\Commands\HeadDocumentCommand;
use RavenDB\Documents\DocumentStore;
use RavenDB\Documents\Indexes\AbstractCommonApiForIndexes;
use RavenDB\Documents\Linq\DocumentQueryGeneratorInterface;
use RavenDB\Documents\Operations\PatchRequest;
use RavenDB\Documents\Operations\TimeSeries\AbstractTimeSeriesRangeSet;
use RavenDB\Documents\Queries\Query;
use RavenDB\Documents\Session\Loaders\IncludeBuilder;
use RavenDB\Documents\Session\Loaders\IncludeBuilderInterface;
use RavenDB\Documents\Session\Loaders\LoaderWithIncludeInterface;
use RavenDB\Documents\Session\Loaders\MultiLoaderWithInclude;
use RavenDB\Documents\Session\Operations\BatchOperation;
use RavenDB\Documents\Session\Operations\LoadOperation;
use RavenDB\Documents\Session\Operations\LoadStartingWithOperation;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Exceptions\NotImplementedException;
use RavenDB\Primitives\Consumer;
use RavenDB\Type\ObjectArray;
use RavenDB\Type\ObjectMap;
use RavenDB\Type\StringArray;
use RavenDB\Utils\StringUtils;
use RuntimeException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Throwable;

class DocumentSession extends InMemoryDocumentSessionOperations implements
    AdvancedSessionOperationsInterface,
    DocumentSessionImplementationInterface,
    DocumentQueryGeneratorInterface
{
    /**
     * Get the accessor for advanced operations
     *
     * Note: Those operations are rarely needed, and have been moved to a separate
     * property to avoid cluttering the API
     */
    public function advanced(): AdvancedSessionOperationsInterface
    {
        return $this;
    }

    public function lazily(): LazySessionOperationsInterface
    {
        return new LazySessionOperations($this);
    }

    public function eagerly(): EagerSessionOperationsInterface
    {
        return $this;
    }

    private ?AttachmentsSessionOperationsInterface $attachments = null;

    public function attachments(): AttachmentsSessionOperationsInterface
    {
        if ($this->attachments == null) {
            $this->attachments = new DocumentSessionAttachments($this);
        }
        return $this->attachments;
    }

    private ?RevisionsSessionOperationsInterface $revisions = null;

    public function revisions(): RevisionsSessionOperationsInterface
    {
        if ($this->revisions == null) {
            $this->revisions = new DocumentSessionRevisions($this);
        }
        return $this->revisions;
    }

    private ?ClusterTransactionOperationsInterface $clusterTransaction = null;

    public function clusterTransaction(): ClusterTransactionOperationsInterface
    {
        if ($this->clusterTransaction == null) {
            $this->clusterTransaction = new  ClusterTransactionOperations($this);
        }

        return $this->clusterTransaction;
    }

    protected function hasClusterSession(): bool
    {
        return $this->clusterTransaction != null;
    }

    protected function clearClusterSession(): void
    {
        if (!$this->hasClusterSession()) {
            return;
        }

        $this->getClusterSession()->clear();
    }

    public function getClusterSession(): ClusterTransactionOperationsBase
    {
        if ($this->clusterTransaction == null) {
            $this->clusterTransaction = new ClusterTransactionOperations($this);
        }

        /** @var ClusterTransactionOperationsBase  $clusterTransaction */
        $clusterTransaction = $this->clusterTransaction;
        return $clusterTransaction;
    }

    /**
     * Initializes new DocumentSession
     *
     * @param DocumentStore $documentStore Parent document store
     * @param UuidInterface $id Identifier
     * @param SessionOptions $options SessionOptions
     */
    public function __construct(DocumentStore $documentStore, UuidInterface $id, SessionOptions $options)
    {
        parent::__construct($documentStore, $id, $options);
    }

    /**
     * Saves all the changes to the Raven server.
     *
     * @throws IllegalStateException
     * @throws \RavenDB\Exceptions\IllegalArgumentException
     * @throws \RavenDB\Exceptions\ClientVersionMismatchException
     */
    public function saveChanges(): void
    {
        $saveChangeOperation = new BatchOperation($this);

        $command = $saveChangeOperation->createRequest();

        if ($command == null) {
            return;
        }

        if ($this->noTracking) {
            throw new IllegalStateException("Cannot execute saveChanges when entity tracking is disabled in session.");
        }

        try {
            $this->requestExecutor->execute($command, $this->sessionInfo);
            $this->updateSessionAfterSaveChanges($command->getResult());
            $saveChangeOperation->setResult($command->getResult());
        } finally {
            $command->close();
        }
    }

    /**
     * Check if document exists without loading it
     */
    public function exists(?string $id): bool
    {
        if ($id == null) {
            throw new IllegalArgumentException("id cannot be null");
        }

        if (in_array($id, $this->knownMissingIds)) {
            return false;
        }

        if ($this->documentsById->getValue($id) != null) {
            return true;
        }

        $command = new HeadDocumentCommand($id, null);

        $this->requestExecutor->execute($command, $this->sessionInfo);

        return $command->getResult() != null;
    }

    /**
     * Refreshes the specified entity from Raven server.
     *
     * @template T extends object
     * @param T $entity
     *
     * @throws ExceptionInterface
     */
    public function refresh($entity): void
    {
        $documentInfo = $this->documentsByEntity->get($entity);
        if ($documentInfo == null) {
            throw new IllegalStateException("Cannot refresh a transient instance");
        }

        $this->incrementRequestCount();

        $command = GetDocumentsCommand::forSingleDocument($documentInfo->getId());
        $this->requestExecutor->execute($command, $this->sessionInfo);

        $this->refreshInternal($entity, $command, $documentInfo);
    }

    /**
     * Generates the document ID.
     */
    protected function generateId(?object $entity): string
    {
        return $this->getConventions()->generateDocumentId($this->getDatabaseName(), $entity);
    }

    public function executeAllPendingLazyOperations(): ResponseTimeInformation
    {
        $requests = new GetRequestList();

        for ($i = 0; $i < count($this->pendingLazyOperations); $i++) {
            $req = $this->pendingLazyOperations[$i]->createRequest();
            if ($req == null) {
                $this->pendingLazyOperations->removeValues($i, 1);
                $i--; // so we'll recheck this index
                continue;
            }
            $requests->append($req);
        }

        if (count($requests) == 0) {
            return new ResponseTimeInformation();
        }

        try  {
            $sw = Stopwatch::createStarted();

            $responseTimeDuration = new ResponseTimeInformation();

            while ($this->executeLazyOperationsSingleStep($responseTimeDuration, $requests, $sw)) {
                usleep(100000); // 100 millis
            }

            $responseTimeDuration->computeServerTotal();

            foreach ($this->pendingLazyOperations as $pendingLazyOperation) {
                $value = $this->onEvaluateLazy->get($pendingLazyOperation, null);
                if ($value != null) {
                    $value($pendingLazyOperation->getResult());
                }
            }

            $sw->stop();
            $responseTimeDuration->setTotalClientDuration(Duration::ofMillis($sw->elapsedInMillis()));
            return $responseTimeDuration;
        } catch (Throwable $e) {
            if ($e instanceof RavenException) {
                throw $e;
            }
            throw new RuntimeException("Unable to execute pending operations: "  . $e->getMessage(), $e->getCode());
        } finally {
            $this->pendingLazyOperations->clear();
        }
    }

    private function executeLazyOperationsSingleStep(?ResponseTimeInformation $responseTimeInformation, GetRequestList $requests, Stopwatch $sw): bool
    {
        $multiGetOperation = new MultiGetOperation($this);

        $multiGetCommand = $multiGetOperation->createRequest($requests);
        try {
            $this->getRequestExecutor()->execute($multiGetCommand, $this->sessionInfo);

            $responses = $multiGetCommand->getResult();

            if (!$multiGetCommand->aggressivelyCached) {
                $this->incrementRequestCount();
            }

            for ($i = 0; $i < count($this->pendingLazyOperations); $i++) {
                $totalTime = 0;
                $tempReqTime = '';
                $response = $responses[$i];


                $tempReqTime = $response->getHeaders()->offsetExists(Headers::REQUEST_TIME) ? $response->getHeaders()[Headers::REQUEST_TIME] : null;
                $response->setElapsed(Duration::ofMillis($sw->elapsedInMillis()));
                $totalTime = $tempReqTime != null ? intval($tempReqTime) : 0;

                $timeItem = new ResponseTimeItem();
                $timeItem->setUrl($requests[$i]->getUrlAndQuery());
                $timeItem->setDuration(Duration::ofMillis($totalTime));

                $responseTimeInformation->getDurationBreakdown()->append($timeItem);

                if ($response->requestHasErrors()) {
                    throw new IllegalStateException("Got an error from server, status code: " . $response->getStatusCode() . PHP_EOL . $response->getResult());
                }

                $this->pendingLazyOperations[$i]->handleResponse($response);
                if ($this->pendingLazyOperations[$i]->isRequiresRetry()) {
                    return true;
                }
            }
            return false;
        } finally {
            $multiGetCommand->close();
        }
    }

    /**
     * Begin a load while including the specified path
     */
    public function include(?string $path): LoaderWithIncludeInterface
    {
        return (new MultiLoaderWithInclude($this))->include($path);
    }

    public function addLazyOperation(?string $className, LazyOperationInterface $operation, ?Closure $onEval = null): Lazy
    {
        $this->pendingLazyOperations->append($operation);
        $lazyValue = new Lazy(function() use ($className, $operation) {
            $this->executeAllPendingLazyOperations();
            return self::getOperationResult($className, $operation->getResult());
        });

        if ($onEval != null) {
            $this->onEvaluateLazy->put($operation,  function($theResult) use ($className, $onEval) { return $onEval(self::getOperationResult($className, $theResult)); });
        }

        return $lazyValue;
    }

    public function addLazyCountOperation(LazyOperationInterface $operation): Lazy
    {
        $this->pendingLazyOperations->append($operation);

        $session = $this;
        return new Lazy(function() use ($session, $operation) {
            $session->executeAllPendingLazyOperations();
            return $operation->getQueryResult()->getTotalResults();
        });
    }

    public function lazyLoadInternal(?string $className, array|StringArray $ids, array|StringArray $includes, ?Closure $onEval): Lazy
    {
        if (is_array($ids)) {
            $ids = StringArray::fromArray($ids);
        }
        if (is_array($includes)) {
            $includes = StringArray::fromArray($includes);
        }

        if ($this->checkIfIdAlreadyIncluded($ids, $includes)) {
            $session = $this;
            return new Lazy(function() use ($session, $className, $ids) { return $session->load($className, $ids); });
        }

        $loadOperation = (new LoadOperation($this))
                ->byIds($ids)
                ->withIncludes($includes);

        $lazyOp = (new LazyLoadOperation($className, $this, $loadOperation))
                ->byIds($ids)
                ->withIncludes($includes);

        return $this->addLazyOperation(null, $lazyOp, $onEval);
    }

    /**
     * Loads the specified entity with the specified id.
     *
     * load(string $className, string $id): ?object
     * load(string $className, string $id, Closure $includes) ?Object;
     *
     * load(string $className, StringArray $ids): ObjectArray
     * load(string $className, StringArray $ids, Closure $includes): ObjectArray;
     *
     * load(string $className, array $ids): ObjectArray
     * load(string $className, array $ids, Closure $includes): ObjectArray;
     *
     * load(string $className, string $id1, string $id2, string $id3 ... ): ObjectArray
     *
     * @param ?string $className Object class
     * @param mixed $params Identifier of an entity that will be loaded.
     *
     * @return null|object|ObjectArray Loaded entity or entities
     *
     * @throws ExceptionInterface
     */
    public function load(?string $className, ...$params)
    {
        if (empty($params)) {
            throw new \http\Exception\InvalidArgumentException('Id or ids must be defined for loading.');
        }

        // called: load(string $className, string $id): object
        if (count($params) == 1) {
            if ($params[0] == null) {
                return null;
            }

            if (is_string($params[0])) {
                return $this->loadById($className, $params[0]);
            }
        }

        $ids = null;

        // called: load(string $className, StringArray $ids): ObjectArray
        if (count($params) == 1) {
            if ($params[0] instanceof StringArray) {
                $ids = $params[0];
            }

            if (is_array($params[0])) {
                $ids = new StringArray();
                foreach ($params[0] as $id) {
                    if (($id != null) && !empty(trim($id))) {
                        $ids->append($id);
                    }
                }
            }
        }

        if (count($params) == 2) {
            if ($params[1] instanceof Closure) {

                // called: load(string $className, StringArray $ids, Closure $includes): ObjectArray;
                if ($params[0] instanceof StringArray) {
                    return $this->loadMultipleWithIncludes($className, $params[0], $params[1]);
                }

                // called: load(string $className, array $ids, Closure $includes): ObjectArray;
                if (is_array($params[0])) {
                    return $this->loadMultipleWithIncludes($className, StringArray::fromArray($params[0]), $params[1]);
                }

                // called: load(string $className, string $id, Closure $includes) ?Object;
                if (is_string($params[0])) {
                    return $this->loadSingleWithIncludes($className, $params[0], $params[1]);
                }
            }
        }

        // called: load(string $className, string $id1, string $id2, string $id3 ... ): ObjectArray
        $allParamsString = true;
        foreach ($params as $param) {
            if (!is_string($param)) {
                $allParamsString = false;
             }
        }

        if ($allParamsString) {
            $ids = StringArray::fromArray($params);
        }

        if ($ids) {
            $loadOperation = new LoadOperation($this);
            $this->loadInternalByOperation($ids, $loadOperation/*, null*/);
            return $loadOperation->getDocuments($className);
        }

        throw new \LogicException('Load method with this arguments is not possible.');
    }

    /**
     *
     * @throws IllegalStateException
     * @throws InvalidArgumentException
     * @throws ExceptionInterface
     */
    private function loadById(?string $className, string $id): ?object
    {
        $id = trim($id);
        if (empty($id)) {
            return null;
        }

        $loadOperation = new LoadOperation($this);

        $loadOperation->byId($id);

        $command = $loadOperation->createRequest();

        if ($command != null) {
            $this->requestExecutor->execute($command, $this->sessionInfo);

            /** @var GetDocumentsResult $result */
            $result = $command->getResult();
            $loadOperation->setResult($result);
        }

        return $loadOperation->getDocument($className);
    }

    private function loadInternalByOperation(StringArray $ids, LoadOperation $operation /*, OutputStream stream*/): void
    {
        $operation->byIds($ids);

        $command = $operation->createRequest();
        if ($command != null) {
            $this->requestExecutor->execute($command, $this->sessionInfo);

//            if (stream != null) {
//                try {
//                    GetDocumentsResult result = command.getResult();
//                    JsonExtensions.getDefaultMapper().writeValue(stream, result);
//                } catch (IOException e) {
//                    throw new RuntimeException("Unable to serialize returned value into stream" + e.getMessage(), e);
//                }
//            } else {
            /** @var GetDocumentsResult $result */
            $result = $command->getResult();
            $operation->setResult($result);
//            }
        }
    }

    /**
     * @param string $className
     * @param string|null $id
     * @param Closure $includes
     * @return object|null
     */
    private function loadSingleWithIncludes(string $className, ?string $id, Closure $includes): ?object
    {
        if ($id == null) {
            return null;
        }

        $values = $this->loadMultipleWithIncludes($className, StringArray::fromArray([$id]), $includes);
        return empty($values) ? null : $values->first();
    }

    /**
     * @param string $className
     * @param StringArray|null $ids
     * @param Closure $includes
     * @return ObjectArray
     */
    private function loadMultipleWithIncludes(string $className, ?StringArray $ids, Closure $includes): ObjectArray
    {
        if ($ids == null) {
            throw new IllegalArgumentException("ids cannot be null");
        }

        $includeBuilder = new IncludeBuilder($this->getConventions());
        $includes($includeBuilder);

        return $this->loadInternal($className,
            $ids,
            $includeBuilder->documentsToInclude,
            $includeBuilder->getCountersToInclude(),
            $includeBuilder->isAllCounters(),
            $includeBuilder->getTimeSeriesToInclude(),
            $includeBuilder->getCompareExchangeValuesToInclude(),
            $includeBuilder->getRevisionsToIncludeByChangeVector(),
            $includeBuilder->getRevisionsToIncludeByDateTime()
        );
    }

    public function loadInternal(
        string                      $className,
        ?StringArray                $ids,
        ?StringArray                $includes,
        ?StringArray                $counterIncludes = null,
        bool                        $includeAllCounters = false,
        ?AbstractTimeSeriesRangeSet $timeSeriesIncludes = null,
        ?StringArray                $compareExchangeValueIncludes = null,
        ?StringArray                $revisionsIncludesByChangeVector = null,
        ?DateTime                   $revisionsToIncludeByDateTime = null
    ): ObjectArray {
        if ($ids == null) {
            throw new IllegalArgumentException("Ids cannot be null");
        }

        $loadOperation = new LoadOperation($this);
        $loadOperation->byIds($ids);
        $loadOperation->withIncludes($includes);

        if ($includeAllCounters) {
            $loadOperation->withAllCounters();
        } else {
            $loadOperation->withCounters($counterIncludes);
        }

        $loadOperation->withRevisionsByChangeVector($revisionsIncludesByChangeVector);
        $loadOperation->withRevisionsByDateTimeBefore($revisionsToIncludeByDateTime);
        $loadOperation->withTimeSeries($timeSeriesIncludes);
        $loadOperation->withCompareExchange($compareExchangeValueIncludes);

        $command = $loadOperation->createRequest();
        if ($command != null) {
            $this->requestExecutor->execute($command, $this->sessionInfo);
            /** @var GetDocumentsResult $result */
            $result = $command->getResult();
            $loadOperation->setResult($result);
        }

        return $loadOperation->getDocuments($className);
    }

    public function loadStartingWith(
        string $className,
        ?string $idPrefix,
        ?string $matches = null,
        int $start = 0,
        int $pageSize = 25,
        ?string $exclude = null,
        ?string $startAfter = null
    ): ObjectArray {
        $loadStartingWithOperation = new LoadStartingWithOperation($this);
        $this->loadStartingWithInternal($idPrefix, $loadStartingWithOperation, null, $matches, $start, $pageSize, $exclude, $startAfter);
      return $loadStartingWithOperation->getDocuments($className);
    }

//    @Override
//    public void loadStartingWithIntoStream(String idPrefix, OutputStream output) {
//        loadStartingWithIntoStream(idPrefix, output, null, 0, 25, null, null);
//    }
//
//    @Override
//    public void loadStartingWithIntoStream(String idPrefix, OutputStream output, String matches) {
//        loadStartingWithIntoStream(idPrefix, output, matches, 0, 25, null, null);
//    }
//
//    @Override
//    public void loadStartingWithIntoStream(String idPrefix, OutputStream output, String matches, int start) {
//        loadStartingWithIntoStream(idPrefix, output, matches, start, 25, null, null);
//    }
//
//    @Override
//    public void loadStartingWithIntoStream(String idPrefix, OutputStream output, String matches, int start, int pageSize) {
//        loadStartingWithIntoStream(idPrefix, output, matches, start, pageSize, null, null);
//    }
//
//    @Override
//    public void loadStartingWithIntoStream(String idPrefix, OutputStream output, String matches, int start, int pageSize, String exclude) {
//        loadStartingWithIntoStream(idPrefix, output, matches, start, pageSize, exclude, null);
//    }
//
//    @Override
//    public void loadStartingWithIntoStream(String idPrefix, OutputStream output, String matches, int start, int pageSize, String exclude, String startAfter) {
//        if (output == null) {
//            throw new IllegalArgumentException("Output cannot be null");
//        }
//        if (idPrefix == null) {
//            throw new IllegalArgumentException("idPrefix cannot be null");
//        }
//        loadStartingWithInternal(idPrefix, new LoadStartingWithOperation(this), output, matches, start, pageSize, exclude, startAfter);
//    }

    private function loadStartingWithInternal(
        ?string $idPrefix,
        ?LoadStartingWithOperation & $operation,
        $stream,
        ?string $matches,
        int $start,
        int $pageSize,
        ?string $exclude = null,
        ?string $startAfter = null
    ): GetDocumentsCommand {
        $operation->withStartWith($idPrefix, $matches, $start, $pageSize, $exclude, $startAfter);

        $command = $operation->createRequest();
        if ($command != null) {
            $this->requestExecutor->execute($command, $this->sessionInfo);

            if ($stream != null) {
                throw new NotImplementedException('Working with streams not implemented yet');
//                try {
//                    GetDocumentsResult result = command.getResult();
//                    JsonExtensions.getDefaultMapper().writeValue(stream, result);
//                } catch (IOException e) {
//                    throw new RuntimeException("Unable to serialize returned value into stream" + e.getMessage(), e);
//                }
            } else {
                /** @var GetDocumentsResult $result */
                $result = $command->getResult();
                $operation->setResult($result);
            }
        }
        return $command;
    }

//    @Override
//    public void loadIntoStream(Collection<String> ids, OutputStream output) {
//        if (ids == null) {
//            throw new IllegalArgumentException("Ids cannot be null");
//        }
//
//        loadInternal(ids.toArray(new String[0]), new LoadOperation(this), output);
//    }

    /**
     * @param object|string|null $idOrEntity
     * @param string|null $path
     * @param mixed $valueToAdd
     */
    public function increment($idOrEntity, ?string $path, $valueToAdd): void
    {
        if (is_object($idOrEntity)) {
            $this->incrementByEntity($idOrEntity, $path, $valueToAdd);
            return;
        }
        if (is_string($idOrEntity)) {
            $this->incrementById($idOrEntity, $path, $valueToAdd);
            return;
        }
        throw new IllegalArgumentException('Wrong argument type');
    }

    protected function incrementByEntity(?object $entity, ?string $path, $valueToAdd): void
    {
        $metadata = $this->getMetadataFor($entity);
        $id = $metadata->get(DocumentsMetadata::ID);
        $this->incrementById($id, $path, $valueToAdd);
    }

    private int $valsCount = 0;
    private int $customCount = 0;

    protected function incrementById(?string $id, ?string $path, $valueToAdd): void
    {
        $patchRequest = new PatchRequest();

        $variable = "this." . $path;
        $value = "args.val_" . $this->valsCount;
        $patchRequest->setScript($variable . " = " . $variable
                . " ? " . $variable . " + " . $value
                . " : " . $value . ";");
        $objectMap = new ObjectMap();
        $objectMap->offsetSet("val_" . $this->valsCount, $valueToAdd);
        $patchRequest->setValues($objectMap);

        $this->valsCount++;

        if (!$this->tryMergePatches($id, $patchRequest)) {
            $this->defer(new PatchCommandData($id, null, $patchRequest, null));
        }
    }

    /**
     * @param string|null $id
     * @param object      $entity
     * @param string|null $pathToObject
     * @param mixed       $valToAdd
     *
     * @throws ReflectionException
     */
    public function addOrIncrement(?string $id, object $entity, ?string $pathToObject, $valToAdd): void
    {
        $variable = "this." . $pathToObject;
        $value = "args.val_" . $this->valsCount;

        $patchRequest = new PatchRequest();
        $patchRequest->setScript($variable . " = " . $variable . " ? " . $variable . " + " . $value . " : " . $value);

        $values = new ObjectMap();
        $values->offsetSet("val_" . $this->valsCount, $valToAdd);
        $patchRequest->setValues($values);

        $collectionName = $this->requestExecutor->getConventions()->getCollectionName($entity);
        $phpType = $this->requestExecutor->getConventions()->getPhpClassName($entity);

        $metadataAsDictionary = new MetadataAsDictionary();
        $metadataAsDictionary->put(DocumentsMetadata::COLLECTION, $collectionName);
        $metadataAsDictionary->put(DocumentsMetadata::RAVEN_PHP_TYPE, $phpType);

        $documentInfo = new DocumentInfo();
        $documentInfo->setId($id);
        $documentInfo->setCollection($collectionName);
        $documentInfo->setMetadataInstance($metadataAsDictionary);

        $newInstance = $this->getEntityToJson()->convertEntityToJson($entity, $documentInfo);

        $this->valsCount++;

        $patchCommandData = new PatchCommandData($id, null, $patchRequest);
        $patchCommandData->setCreateIfMissing($newInstance);
        $this->defer($patchCommandData);
    }

    public function addOrPatchArray(?string $id, object $entity, ?string $pathToArray, Closure $arrayAdder): void
    {
        $scriptArray = new JavaScriptArray($this->customCount++, $pathToArray);

        $arrayAdder($scriptArray);

        $patchRequest = new PatchRequest();
        $patchRequest->setScript($scriptArray->getScript());
        $patchRequest->setValues($scriptArray->getParameters());

        $collectionName = $this->requestExecutor->getConventions()->getCollectionName($entity);
        $phpType = $this->requestExecutor->getConventions()->getPhpClassName($entity);

        $metadataAsDictionary = new MetadataAsDictionary();
        $metadataAsDictionary->put(DocumentsMetadata::COLLECTION, $collectionName);
        $metadataAsDictionary->put(DocumentsMetadata::RAVEN_PHP_TYPE, $phpType);

        $documentInfo = new DocumentInfo();
        $documentInfo->setId($id);
        $documentInfo->setCollection($collectionName);
        $documentInfo->setMetadataInstance($metadataAsDictionary);

        $newInstance = $this->getEntityToJson()->convertEntityToJson($entity, $documentInfo);

        $this->valsCount++;

        $patchCommandData = new PatchCommandData($id, null, $patchRequest);
        $patchCommandData->setCreateIfMissing($newInstance);
        $this->defer($patchCommandData);
    }

    /**
     * @param string|null $id
     * @param object      $entity
     * @param string|null $pathToObject
     * @param mixed       $value
     *
     * @throws ReflectionException
     */
    public function addOrPatch(?string $id, object $entity, ?string $pathToObject, $value): void
    {
        $patchRequest = new PatchRequest();
        $patchRequest->setScript("this." . $pathToObject . " = args.val_" . $this->valsCount);

        $values = new ObjectMap();
        $values->offsetSet("val_" . $this->valsCount, $value);
        $patchRequest->setValues($values);

        $collectionName = $this->requestExecutor->getConventions()->getCollectionName($entity);
        $phpType = $this->requestExecutor->getConventions()->getPhpClassName($entity);

        $metadataAsDictionary = new MetadataAsDictionary();
        $metadataAsDictionary->put(DocumentsMetadata::COLLECTION, $collectionName);
        $metadataAsDictionary->put(DocumentsMetadata::RAVEN_PHP_TYPE, $phpType);

        $documentInfo = new DocumentInfo();
        $documentInfo->setId($id);
        $documentInfo->setCollection($collectionName);
        $documentInfo->setMetadataInstance($metadataAsDictionary);

        $newInstance = $this->getEntityToJson()->convertEntityToJson($entity, $documentInfo);

        $this->valsCount++;

        $patchCommandData = new PatchCommandData($id, null, $patchRequest);
        $patchCommandData->setCreateIfMissing($newInstance);
        $this->defer($patchCommandData);
    }

    /**
     * @param string|object|null $idOrEntity
     * @param string|null $path
     * @param mixed $value
     */
    public function patch($idOrEntity, ?string $path, $value): void
    {
        if (is_object($idOrEntity)) {
            $this->patchByEntity($idOrEntity, $path, $value);
            return;
        }
        if (is_string($idOrEntity)) {
            $this->patchById($idOrEntity, $path, $value);
            return;
        }
        throw new IllegalArgumentException('Wrong argument type');
    }

    /**
     * @param object|null $entity
     * @param string|null $path
     * @param mixed $value
     */
    protected function patchByEntity(?object $entity, ?string $path, $value): void
    {
        $metadata = $this->getMetadataFor($entity);
        $id = $metadata->get(DocumentsMetadata::ID);
        $this->patchById($id, $path, $value);
    }

    /**
     * @param string|null $id
     * @param string|null $path
     * @param mixed $value
     */
    protected function patchById(?string $id, ?string $path, $value): void
    {
        $patchRequest = new PatchRequest();
        $patchRequest->setScript("this." . $path . " = args.val_" . $this->valsCount . ";");
        $objectMap = new ObjectMap();
        $objectMap->offsetSet("val_" . $this->valsCount, $value);
        $patchRequest->setValues($objectMap);

        $this->valsCount++;

        if (!$this->tryMergePatches($id, $patchRequest)) {
            $this->defer(new PatchCommandData($id, null, $patchRequest, null));
        }
    }

    /**
     * @param string|object|null $idOrEntity
     * @param string|null $pathToArray
     * @param Closure $arrayAdder
     */
    public function patchArray($idOrEntity, ?string $pathToArray, Closure $arrayAdder): void
    {
        if (is_object($idOrEntity)) {
            $this->patchArrayByEntity($idOrEntity, $pathToArray, $arrayAdder);
            return;
        }
        if (is_string($idOrEntity)) {
            $this->patchArrayById($idOrEntity, $pathToArray, $arrayAdder);
            return;
        }
        throw new IllegalArgumentException('Wrong argument type');
    }

    /**
     * @param object|null $entity
     * @param string|null $pathToArray
     * @param Closure  $arrayAdder
     */
    protected function patchArrayByEntity(?object $entity, ?string $pathToArray, Closure $arrayAdder): void
    {
        $metadata = $this->getMetadataFor($entity);
        $id = $metadata->get(DocumentsMetadata::ID);
        $this->patchArrayById($id, $pathToArray, $arrayAdder);
    }

    /**
     * @param string|null $id
     * @param string|null $pathToArray
     * @param Closure  $arrayAdder
     */
    protected function patchArrayById(?string $id, ?string $pathToArray, Closure $arrayAdder): void
    {
        $scriptArray = new JavaScriptArray($this->customCount++, $pathToArray);

        $arrayAdder($scriptArray);

        $patchRequest = new PatchRequest();
        $patchRequest->setScript($scriptArray->getScript());
        $patchRequest->setValues($scriptArray->getParameters());

        if (!$this->tryMergePatches($id, $patchRequest)) {
            $this->defer(new PatchCommandData($id, null, $patchRequest, null));
        }
    }

    /**
     * @param string|object|null $idOrEntity
     * @param string|null $pathToObject
     * @param Closure $dictionaryAdder
     */
    public function patchObject($idOrEntity, ?string $pathToObject, Closure $dictionaryAdder): void
    {
        if (is_object($idOrEntity)) {
            $this->patchObjectByEntity($idOrEntity, $pathToObject, $dictionaryAdder);
            return;
        }
        if (is_string($idOrEntity)) {
            $this->patchObjectById($idOrEntity, $pathToObject, $dictionaryAdder);
            return;
        }
        throw new IllegalArgumentException('Wrong argument type');
    }

    /**
     * @param object|null $entity
     * @param string|null $pathToObject
     * @param Closure $dictionaryAdder
     */
    protected function patchObjectByEntity(?object $entity, ?string $pathToObject, Closure $dictionaryAdder): void
    {
        $metadata = $this->getMetadataFor($entity);
        $id = $metadata->get(DocumentsMetadata::ID);
        $this->patchObjectById($id, $pathToObject, $dictionaryAdder);
    }

    /**
     * @param string|null $id
     * @param string|null $pathToObject
     * @param Closure $mapAdder
     */
    protected function patchObjectById(?string $id, ?string $pathToObject, Closure $mapAdder): void
    {
        $scriptMap = new JavaScriptMap($this->customCount++, $pathToObject);

        $mapAdder($scriptMap);

        $patchRequest = new PatchRequest();
        $patchRequest->setScript($scriptMap->getScript());
        $patchRequest->setValues($scriptMap->getParameters());

        if (!$this->tryMergePatches($id, $patchRequest)) {
            $this->defer(new PatchCommandData($id, null, $patchRequest, null));
        }
    }

    private function tryMergePatches(?string $id, ?PatchRequest $patchRequest): bool
    {
//        $command = null;
//        /** @var IdTypeAndName $commandMap */
//        foreach ($this->deferredCommandsMap as $commandMap => $c) {
//            if ($commandMap->getId() == $id && $commandMap->getType()->isPatch() && $commandMap->getName() == null) {
//                $command = $c;
//                break;
//            }
//        }

        $commandMap = $this->deferredCommandsMap->getIndexFor($id, CommandType::patch(), null);

        if ($commandMap == null) {
            return false;
        }
        $command = $this->deferredCommandsMap->get($commandMap);

        if(($key = array_search($command, $this->deferredCommands, true)) !== FALSE) {
            unset($this->deferredCommands[$key]);
        }

        // We'll overwrite the deferredCommandsMap when calling Defer
        // No need to call deferredCommandsMap.remove((id, CommandType.PATCH, null));

        /** @var PatchCommandData $oldPatch */
        $oldPatch = $command;
        $newScript = $oldPatch->getPatch()->getScript() . "\n" . $patchRequest->getScript();
        $newVals = $oldPatch->getPatch()->getValues();

        foreach ($patchRequest->getValues() as $key => $value) {
            $newVals->offsetSet($key, $value);
        }

        $newPatchRequest = new PatchRequest();
        $newPatchRequest->setScript($newScript);
        $newPatchRequest->setValues($newVals);

        $this->defer(new PatchCommandData($id, null, $newPatchRequest, null));

        return true;
    }

    /**
     * Query the specified index using Lucene syntax
     * @param ?string $className The result of the query
     * @param string|null|AbstractCommonApiForIndexes $indexNameOrClass Name of the index (mutually exclusive with collectionName) or AbstractCommonApiForIndexes class name
     * @param string|null $collectionName Name of the collection (mutually exclusive with indexName)
     * @param bool $isMapReduce Whether we are querying a map/reduce index (modify how we treat identifier properties)
     */
    public function documentQuery(?string $className, $indexNameOrClass = null, ?string $collectionName = null, bool $isMapReduce = false): DocumentQueryInterface
    {
        if (!empty($indexNameOrClass) && class_exists($indexNameOrClass) && is_a($indexNameOrClass,  AbstractCommonApiForIndexes::class, true)) {
            try {
                $index = new $indexNameOrClass();
                return  $this->_documentQuery($className, $index->getIndexName(), null, $index->isMapReduce());
            } catch (IllegalStateException $e) {
                throw new RuntimeException("Unable to query index: " . $indexNameOrClass . '. ' . $e->getMessage(), $e->getCode());
            }
        }

        return $this->_documentQuery($className, $indexNameOrClass, $collectionName, $isMapReduce);
    }

    protected function _documentQuery(?string $className, ?string $indexName = null, ?string $collectionName = null, bool $isMapReduce = false): DocumentQueryInterface
    {
        [$indexName, $collectionName] = $this->processQueryParameters($className, $indexName, $collectionName, $this->getConventions());

        return new DocumentQuery($className, $this, $indexName, $collectionName, $isMapReduce);
    }


    public function getSession(): InMemoryDocumentSessionOperations
    {
        return $this;
    }

    public function rawQuery(?string $className, string $query): RawDocumentQueryInterface
    {
        return new RawDocumentQuery($className, $this, $query);
    }

    /**
     *
     * @param ?string $className
     * @param Query|null|string $collectionOrIndexName
     *
     * @return DocumentQueryInterface
     */
    public function query(?string $className, $collectionOrIndexName = null): DocumentQueryInterface
    {
        if (empty($collectionOrIndexName)) {
            return $this->_documentQuery($className, null, null, false);
        }

        if (is_string($collectionOrIndexName)) {
            return $this->documentQuery($className, $collectionOrIndexName, null, false);
        }

        if (StringUtils::isNotEmpty($collectionOrIndexName->getCollection())) {
            return $this->_documentQuery($className, null, $collectionOrIndexName->getCollection(), false);
        }

        return $this->_documentQuery($className, $collectionOrIndexName->getIndexName(), null, false);
    }


//    @Override
//    public <T> CloseableIterator<StreamResult<T>> stream(IDocumentQuery<T> query) {
//        StreamOperation streamOperation = new StreamOperation(this);
//        QueryStreamCommand command = streamOperation.createRequest(query.getIndexQuery());
//
//        getRequestExecutor().execute(command, sessionInfo);
//
//        CloseableIterator<ObjectNode> result = streamOperation.setResult(command.getResult());
//        return yieldResults((AbstractDocumentQuery) query, result);
//    }
//
//    @Override
//    public <T> CloseableIterator<StreamResult<T>> stream(IDocumentQuery<T> query, Reference<StreamQueryStatistics> streamQueryStats) {
//        StreamQueryStatistics stats = new StreamQueryStatistics();
//        StreamOperation streamOperation = new StreamOperation(this, stats);
//        QueryStreamCommand command = streamOperation.createRequest(query.getIndexQuery());
//
//        getRequestExecutor().execute(command, sessionInfo);
//
//        CloseableIterator<ObjectNode> result = streamOperation.setResult(command.getResult());
//        streamQueryStats.value = stats;
//
//        return yieldResults((AbstractDocumentQuery)query, result);
//    }
//
//    @Override
//    public <T> CloseableIterator<StreamResult<T>> stream(IRawDocumentQuery<T> query) {
//        StreamOperation streamOperation = new StreamOperation(this);
//        QueryStreamCommand command = streamOperation.createRequest(query.getIndexQuery());
//
//        getRequestExecutor().execute(command, sessionInfo);
//
//        CloseableIterator<ObjectNode> result = streamOperation.setResult(command.getResult());
//        return yieldResults((AbstractDocumentQuery) query, result);
//    }
//
//    @Override
//    public <T> CloseableIterator<StreamResult<T>> stream(IRawDocumentQuery<T> query, Reference<StreamQueryStatistics> streamQueryStats) {
//        StreamQueryStatistics stats = new StreamQueryStatistics();
//        StreamOperation streamOperation = new StreamOperation(this, stats);
//        QueryStreamCommand command = streamOperation.createRequest(query.getIndexQuery());
//
//        getRequestExecutor().execute(command, sessionInfo);
//
//        CloseableIterator<ObjectNode> result = streamOperation.setResult(command.getResult());
//        streamQueryStats.value = stats;
//
//        return yieldResults((AbstractDocumentQuery) query, result);
//    }
//
//    @SuppressWarnings("unchecked")
//    private <T> CloseableIterator<StreamResult<T>> yieldResults(AbstractDocumentQuery query, CloseableIterator<ObjectNode> enumerator) {
//        return new StreamIterator<T>(query.getQueryClass(), enumerator, query.fieldsToFetchToken, query.isProjectInto, query::invokeAfterStreamExecuted);
//    }
//
//    @Override
//    public <T> void streamInto(IRawDocumentQuery<T> query, OutputStream output) {
//        StreamOperation streamOperation = new StreamOperation(this);
//        QueryStreamCommand command = streamOperation.createRequest(query.getIndexQuery());
//
//        getRequestExecutor().execute(command, sessionInfo);
//
//        try {
//            IOUtils.copy(command.getResult().getStream(), output);
//        } catch (IOException e) {
//            throw new RuntimeException("Unable to stream results into OutputStream: " + e.getMessage(), e);
//        } finally {
//            EntityUtils.consumeQuietly(command.getResult().getResponse().getEntity());
//        }
//    }
//
//    @Override
//    public <T> void streamInto(IDocumentQuery<T> query, OutputStream output) {
//        StreamOperation streamOperation = new StreamOperation(this);
//        QueryStreamCommand command = streamOperation.createRequest(query.getIndexQuery());
//
//        getRequestExecutor().execute(command, sessionInfo);
//
//        try {
//            IOUtils.copy(command.getResult().getStream(), output);
//        } catch (IOException e) {
//            throw new RuntimeException("Unable to stream results into OutputStream: " + e.getMessage(), e);
//        } finally {
//            EntityUtils.consumeQuietly(command.getResult().getResponse().getEntity());
//        }
//    }
//
//    private <T> StreamResult<T> createStreamResult(Class<T> clazz, ObjectNode json, FieldsToFetchToken fieldsToFetch, boolean isProjectInto) throws IOException {
//
//        ObjectNode metadata = (ObjectNode) json.get(Constants.Documents.Metadata.KEY);
//        String changeVector = metadata.get(Constants.Documents.Metadata.CHANGE_VECTOR).asText();
//        // MapReduce indexes return reduce results that don't have @id property
//        String id = null;
//        JsonNode idJson = metadata.get(Constants.Documents.Metadata.ID);
//        if (idJson != null && !idJson.isNull()) {
//            id = idJson.asText();
//        }
//
//
//        T entity = QueryOperation.deserialize(clazz, id, json, metadata, fieldsToFetch, true, this, isProjectInto);
//
//        StreamResult<T> streamResult = new StreamResult<>();
//        streamResult.setChangeVector(changeVector);
//        streamResult.setId(id);
//        streamResult.setDocument(entity);
//        streamResult.setMetadata(new MetadataAsDictionary(metadata));
//
//        return streamResult;
//    }
//
//    @Override
//    public <T> CloseableIterator<StreamResult<T>> stream(Class<T> clazz, String startsWith) {
//        return stream(clazz, startsWith, null, 0, Integer.MAX_VALUE, null);
//    }
//
//    @Override
//    public <T> CloseableIterator<StreamResult<T>> stream(Class<T> clazz, String startsWith, String matches) {
//        return stream(clazz, startsWith, matches, 0, Integer.MAX_VALUE, null);
//    }
//
//    @Override
//    public <T> CloseableIterator<StreamResult<T>> stream(Class<T> clazz, String startsWith, String matches, int start) {
//        return stream(clazz, startsWith, matches, start, Integer.MAX_VALUE, null);
//    }
//
//    @Override
//    public <T> CloseableIterator<StreamResult<T>> stream(Class<T> clazz, String startsWith, String matches, int start, int pageSize) {
//        return stream(clazz, startsWith, matches, start, pageSize, null);
//    }
//
//    @Override
//    public <T> CloseableIterator<StreamResult<T>> stream(Class<T> clazz, String startsWith, String matches, int start, int pageSize, String startAfter) {
//        StreamOperation streamOperation = new StreamOperation(this);
//
//        StreamCommand command = streamOperation.createRequest(startsWith, matches, start, pageSize, null, startAfter);
//        getRequestExecutor().execute(command, sessionInfo);
//
//        CloseableIterator<ObjectNode> result = streamOperation.setResult(command.getResult());
//        return new StreamIterator<>(clazz, result, null, false, null);
//    }
//
//    private class StreamIterator<T> implements CloseableIterator<StreamResult<T>> {
//
//        private final Class<T> _clazz;
//        private final CloseableIterator<ObjectNode> _innerIterator;
//        private final FieldsToFetchToken _fieldsToFetchToken;
//        private final boolean _isProjectInto;
//        private final Consumer<ObjectNode> _onNextItem;
//
//        public StreamIterator(Class<T> clazz, CloseableIterator<ObjectNode> innerIterator, FieldsToFetchToken fieldsToFetch, boolean isProjectInto, Consumer<ObjectNode> onNextItem) {
//            _clazz = clazz;
//            _innerIterator = innerIterator;
//            _fieldsToFetchToken = fieldsToFetch;
//            _isProjectInto = isProjectInto;
//            _onNextItem = onNextItem;
//        }
//
//        @Override
//        public boolean hasNext() {
//            return _innerIterator.hasNext();
//        }
//
//        @Override
//        public StreamResult<T> next() {
//            ObjectNode nextValue = _innerIterator.next();
//            try {
//                if (_onNextItem != null) {
//                    _onNextItem.accept(nextValue);
//                }
//                return createStreamResult(_clazz, nextValue, _fieldsToFetchToken, _isProjectInto);
//            } catch (IOException e) {
//                throw new RuntimeException("Unable to parse stream result: " + e.getMessage(), e);
//            }
//        }
//
//        @Override
//        public void close() {
//            _innerIterator.close();
//        }
//    }

    public function countersFor(string|object $idOrEntity): SessionDocumentCountersInterface
    {
        return new SessionDocumentCounters($this, $idOrEntity);
    }

//    @Override
//    public <T> IGraphDocumentQuery<T> graphQuery(Class<T> clazz, String query) {
//        GraphDocumentQuery<T> graphQuery = new GraphDocumentQuery<T>(clazz, this, query);
//        return graphQuery;
//    }

    public function timeSeriesFor(string|object|null $idOrEntity, ?string $name): SessionDocumentTimeSeriesInterface
    {
        return new SessionDocumentTimeSeries($this, $idOrEntity, $name);
    }

    public function typedTimeSeriesFor(string $className, string|object|null $idOrEntity, ?string $name = null): SessionDocumentTypedTimeSeriesInterface
    {
        $tsName = $name ?? TimeSeriesOperations::getTimeSeriesName($className, $this->getConventions());
        return new SessionDocumentTypedTimeSeries($className, $this, $idOrEntity, $tsName);
    }

    public function timeSeriesRollupFor(string $className, string|object|null $idOrEntity, ?string $policy, ?string $raw = null): SessionDocumentRollupTypedTimeSeriesInterface
    {
        $tsName = $raw ?? TimeSeriesOperations::getTimeSeriesName($className, $this->getConventions());
        return new SessionDocumentRollupTypedTimeSeries($className, $this, $idOrEntity, $tsName . TimeSeriesConfiguration::TIME_SERIES_ROLLUP_SEPARATOR . $policy);
    }

    function conditionalLoad(?string $className, ?string $id, ?string $changeVector): ConditionalLoadResult
    {
        if (StringUtils::isEmpty($id)) {
            throw new IllegalArgumentException("Id cannot be null");
        }

        if ($this->advanced()->isLoaded($id)) {
            $entity = $this->load($className, $id);
            if ($entity == null) {
                return ConditionalLoadResult::create(null, null);
            }

            $cv = $this->advanced()->getChangeVectorFor($entity);
            return ConditionalLoadResult::create($entity, $cv);
        }

        if (StringUtils::isEmpty($changeVector)) {
            throw new IllegalArgumentException("The requested document with id '" . $id . "' is not loaded into the session and could not conditional load when changeVector is null or empty.");
        }

        $this->incrementRequestCount();

        $cmd = new ConditionalGetDocumentsCommand($id, $changeVector);
        $this->advanced()->getRequestExecutor()->execute($cmd);

        switch ($cmd->getStatusCode()) {
            case HttpStatusCode::NOT_MODIFIED:
                return ConditionalLoadResult::create(null, $changeVector); // value not changed
            case HttpStatusCode::NOT_FOUND:
                $this->registerMissing($id);
                return ConditionalLoadResult::create(null, null); // value is missing
        }

        $documentInfo = DocumentInfo::getNewDocumentInfo($cmd->getResult()->getResults()[0]);
        $r = $this->trackEntity($className, $documentInfo);
        return ConditionalLoadResult::create($r, $cmd->getResult()->getChangeVector());
    }
}
