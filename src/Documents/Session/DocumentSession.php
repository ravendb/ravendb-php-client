<?php

namespace RavenDB\Documents\Session;

use InvalidArgumentException;
use Ramsey\Uuid\UuidInterface;
use RavenDB\Documents\Commands\GetDocumentsResult;
use RavenDB\Documents\Commands\HeadDocumentCommand;
use RavenDB\Documents\DocumentStore;
use RavenDB\Documents\Indexes\AbstractCommonApiForIndexes;
use RavenDB\Documents\Linq\DocumentQueryGeneratorInterface;
use RavenDB\Documents\Operations\TimeSeries\AbstractTimeSeriesRangeArray;
use RavenDB\Documents\Queries\Query;
use RavenDB\Documents\Session\Loaders\IncludeBuilder;
use RavenDB\Documents\Session\Loaders\IncludeBuilderInterface;
use RavenDB\Documents\Session\Loaders\LoaderWithIncludeInterface;
use RavenDB\Documents\Session\Loaders\MultiLoaderWithInclude;
use RavenDB\Documents\Session\Operations\BatchOperation;
use RavenDB\Documents\Session\Operations\LoadOperation;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Http\ResultMap;
use RavenDB\Primitives\Consumer;
use RavenDB\Type\ObjectArray;
use RavenDB\Type\StringArray;
use RavenDB\Utils\StringUtils;
use RuntimeException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

use DS\Map as DSMap;

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

//    @Override
//    public ILazySessionOperations lazily() {
//        return new LazySessionOperations(this);
//    }
//
//    @Override
//    public IEagerSessionOperations eagerly() {
//        return this;
//    }
//
//    private IAttachmentsSessionOperations _attachments;
//
//    @Override
//    public IAttachmentsSessionOperations attachments() {
//        if (_attachments == null) {
//            _attachments = new DocumentSessionAttachments(this);
//        }
//        return _attachments;
//    }
//
//    private IRevisionsSessionOperations _revisions;
//
//    @Override
//    public IRevisionsSessionOperations revisions() {
//        if (_revisions == null) {
//            _revisions = new DocumentSessionRevisions(this);
//        }
//        return _revisions;
//    }
//
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

        // @todo: implement this
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

//    /**
//     * Refreshes the specified entity from Raven server.
//     */
//    public <T> void refresh(T entity) {
//        DocumentInfo documentInfo = documentsByEntity.get(entity);
//        if (documentInfo == null) {
//            throw new IllegalStateException("Cannot refresh a transient instance");
//        }
//
//        incrementRequestCount();
//
//        GetDocumentsCommand command = new GetDocumentsCommand(new String[]{documentInfo.getId()}, null, false);
//        _requestExecutor.execute(command, sessionInfo);
//
//        refreshInternal(entity, command, documentInfo);
//    }

    /**
     * Generates the document ID.
     */
    protected function generateId(?object $entity): string
    {
        return $this->getConventions()->generateDocumentId($this->getDatabaseName(), $entity);
    }

//
//    public ResponseTimeInformation executeAllPendingLazyOperations() {
//        ArrayList<GetRequest> requests = new ArrayList<>();
//        for (int i = 0; i < pendingLazyOperations.size(); i++) {
//            GetRequest req = pendingLazyOperations.get(i).createRequest();
//            if (req == null) {
//                pendingLazyOperations.remove(i);
//                i--; // so we'll recheck this index
//                continue;
//            }
//            requests.add(req);
//        }
//
//        if (requests.isEmpty()) {
//            return new ResponseTimeInformation();
//        }
//
//        try  {
//            Stopwatch sw = Stopwatch.createStarted();
//
//            ResponseTimeInformation responseTimeDuration = new ResponseTimeInformation();
//
//            while (executeLazyOperationsSingleStep(responseTimeDuration, requests, sw)) {
//                Thread.sleep(100);
//            }
//
//            responseTimeDuration.computeServerTotal();
//
//            for (ILazyOperation pendingLazyOperation : pendingLazyOperations) {
//                Consumer<Object> value = onEvaluateLazy.get(pendingLazyOperation);
//                if (value != null) {
//                    value.accept(pendingLazyOperation.getResult());
//                }
//            }
//
//            sw.stop();
//            responseTimeDuration.setTotalClientDuration(Duration.ofMillis(sw.elapsed(TimeUnit.MILLISECONDS)));
//            return responseTimeDuration;
//        } catch (InterruptedException e) {
//            throw new RuntimeException("Unable to execute pending operations: "  + e.getMessage(), e);
//        } finally {
//            pendingLazyOperations.clear();
//        }
//    }
//
//    private boolean executeLazyOperationsSingleStep(ResponseTimeInformation responseTimeInformation, List<GetRequest> requests, Stopwatch sw) {
//        MultiGetOperation multiGetOperation = new MultiGetOperation(this);
//        try (MultiGetCommand multiGetCommand = multiGetOperation.createRequest(requests)) {
//            getRequestExecutor().execute(multiGetCommand, sessionInfo);
//
//            List<GetResponse> responses = multiGetCommand.getResult();
//
//            if (!multiGetCommand.aggressivelyCached) {
//                incrementRequestCount();
//            }
//
//            for (int i = 0; i < pendingLazyOperations.size(); i++) {
//                long totalTime;
//                String tempReqTime;
//                GetResponse response = responses.get(i);
//
//                tempReqTime = response.getHeaders().get(Constants.Headers.REQUEST_TIME);
//                response.setElapsed(sw.elapsed());
//                totalTime = tempReqTime != null ? Long.parseLong(tempReqTime) : 0;
//
//                ResponseTimeInformation.ResponseTimeItem timeItem = new ResponseTimeInformation.ResponseTimeItem();
//                timeItem.setUrl(requests.get(i).getUrlAndQuery());
//                timeItem.setDuration(Duration.ofMillis(totalTime));
//
//                responseTimeInformation.getDurationBreakdown().add(timeItem);
//
//                if (response.requestHasErrors()) {
//                    throw new IllegalStateException("Got an error from server, status code: " + response.getStatusCode() + System.lineSeparator() + response.getResult());
//                }
//
//                pendingLazyOperations.get(i).handleResponse(response);
//                if (pendingLazyOperations.get(i).isRequiresRetry()) {
//                    return true;
//                }
//            }
//            return false;
//        }
//    }

    /**
     * Begin a load while including the specified path
     */
    public function include(?string $path): LoaderWithIncludeInterface
    {
        return (new MultiLoaderWithInclude($this))->include($path);
    }

//    public <T> Lazy<T> addLazyOperation(Class<T> clazz, ILazyOperation operation, Consumer<T> onEval) {
//        pendingLazyOperations.add(operation);
//        Lazy<T> lazyValue = new Lazy<>(() -> {
//            executeAllPendingLazyOperations();
//            return getOperationResult(clazz, operation.getResult());
//        });
//
//        if (onEval != null) {
//            onEvaluateLazy.put(operation, theResult -> onEval.accept(getOperationResult(clazz, theResult)));
//        }
//
//        return lazyValue;
//    }
//
//    protected Lazy<Integer> addLazyCountOperation(ILazyOperation operation) {
//        pendingLazyOperations.add(operation);
//
//        return new Lazy<>(() -> {
//            executeAllPendingLazyOperations();
//            return operation.getQueryResult().getTotalResults();
//        });
//    }
//
//    @SuppressWarnings("unchecked")
//    @Override
//    public <T> Lazy<Map<String, T>> lazyLoadInternal(Class<T> clazz, String[] ids, String[] includes, Consumer<Map<String, T>> onEval) {
//        if (checkIfIdAlreadyIncluded(ids, Arrays.asList(includes))) {
//            return new Lazy<>(() -> load(clazz, ids));
//        }
//
//        LoadOperation loadOperation = new LoadOperation(this)
//                .byIds(ids)
//                .withIncludes(includes);
//
//        LazyLoadOperation<T> lazyOp = new LazyLoadOperation<>(clazz, this, loadOperation)
//                .byIds(ids).withIncludes(includes);
//
//        return addLazyOperation((Class<Map<String, T>>)(Class< ? >)Map.class, lazyOp, onEval);
//    }

    /**
     * Loads the specified entity with the specified id.
     *
     * load(string $className, string $id): ?object
     * load(string $className, string $id, Consumer $includes) ?Object;
     *
     * load(string $className, StringArray $ids): ObjectArray
     * load(string $className, StringArray $ids, Consumer $includes): ObjectArray;
     *
     * load(string $className, array $ids): ObjectArray
     * load(string $className, array $ids, Consumer $includes): ObjectArray;
     *
     * load(string $className, string $id1, string $id2, string $id3 ... ): ObjectArray
     *
     * @param string $className Object class
     * @param string|StringArray $params Identifier of a entity that will be loaded.
     *
     * @return null|object|ObjectArray Loaded entity or entities
     *
     * @throws ExceptionInterface
     */
    public function load(string $className, ...$params)
    {
        if (empty($params)) {
            throw new \http\Exception\InvalidArgumentException('Id or ids must be defined for loading.');
        }

        // called: load(string $className, string $id): object
        if (count($params) == 1) {
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
                $ids = StringArray::fromArray($params[0]);
            }
        }

        if (count($params) == 2) {
            if ($params[1] instanceof Consumer) {

                    // called: load(string $className, StringArray $ids, Consumer $includes): ObjectArray;
                    if ($params[0] instanceof StringArray) {
                        return $this->loadMultipleWithIncludes($className, $params[0], $params[1]);
                    }

                    // called: load(string $className, string $id, Consumer $includes) ?Object;
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
    private function loadById(string $className, string $id): ?object
    {
        if (empty($id)) {
            return new $className();
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
     * @param Consumer<IncludeBuilderInterface> $includes
     * @return object|null
     */
    private function loadSingleWithIncludes(string $className, ?string $id, Consumer $includes): ?object
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
     * @param Consumer<IncludeBuilderInterface> $includes
     * @return ObjectArray
     */
    private function loadMultipleWithIncludes(string $className, ?StringArray $ids, Consumer $includes): ObjectArray
    {
        if ($ids == null) {
            throw new IllegalArgumentException("ids cannot be null");
        }

        $includeBuilder = new IncludeBuilder($this->getConventions());
        $includes->accept($includeBuilder);

        // @todo: continue work with includes from here
        $timeSeriesIncludes = null;
//        List<AbstractTimeSeriesRange> timeSeriesIncludes = includeBuilder.getTimeSeriesToInclude() != null
//                ? new ArrayList<>(includeBuilder.getTimeSeriesToInclude())
//                : null;

        $compareExchangeValuesToInclude = null;
//        String[] compareExchangeValuesToInclude = includeBuilder.getCompareExchangeValuesToInclude() != null
//                ? includeBuilder.getCompareExchangeValuesToInclude().toArray(new String[0])
//                : null;

        return $this->loadInternal($className,
                $ids,
                $includeBuilder->documentsToInclude != null ? $includeBuilder->documentsToInclude : null,
                $includeBuilder->getCountersToInclude() != null ? $includeBuilder->getCountersToInclude() : null,
                $includeBuilder->isAllCounters(),
                $timeSeriesIncludes,
                $compareExchangeValuesToInclude
        );
    }

    public function loadInternal(
        string $className,
        ?StringArray $ids,
        ?StringArray $includes,
        ?StringArray $counterIncludes = null,
        bool $includeAllCounters = false,
        ?AbstractTimeSeriesRangeArray $timeSeriesIncludes = null,
        ?StringArray $compareExchangeValueIncludes = null
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

//    public <T> T[] loadStartingWith(Class<T> clazz, String idPrefix) {
//        return loadStartingWith(clazz, idPrefix, null, 0, 25, null, null);
//    }
//
//    public <T> T[] loadStartingWith(Class<T> clazz, String idPrefix, String matches) {
//        return loadStartingWith(clazz, idPrefix, matches, 0, 25, null, null);
//    }
//
//    public <T> T[] loadStartingWith(Class<T> clazz, String idPrefix, String matches, int start) {
//        return loadStartingWith(clazz, idPrefix, matches, start, 25, null, null);
//    }
//
//    public <T> T[] loadStartingWith(Class<T> clazz, String idPrefix, String matches, int start, int pageSize) {
//        return loadStartingWith(clazz, idPrefix, matches, start, pageSize, null, null);
//    }
//
//    public <T> T[] loadStartingWith(Class<T> clazz, String idPrefix, String matches, int start, int pageSize, String exclude) {
//        return loadStartingWith(clazz, idPrefix, matches, start, pageSize, exclude, null);
//    }
//
//    public <T> T[] loadStartingWith(Class<T> clazz, String idPrefix, String matches, int start, int pageSize, String exclude, String startAfter) {
//        LoadStartingWithOperation loadStartingWithOperation = new LoadStartingWithOperation(this);
//        loadStartingWithInternal(idPrefix, loadStartingWithOperation, null, matches, start, pageSize, exclude, startAfter);
//        return loadStartingWithOperation.getDocuments(clazz);
//    }
//
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
//
//    @SuppressWarnings("UnusedReturnValue")
//    private GetDocumentsCommand loadStartingWithInternal(String idPrefix, LoadStartingWithOperation operation, OutputStream stream,
//                                                         String matches, int start, int pageSize, String exclude, String startAfter) {
//        operation.withStartWith(idPrefix, matches, start, pageSize, exclude, startAfter);
//
//        GetDocumentsCommand command = operation.createRequest();
//        if (command != null) {
//            _requestExecutor.execute(command, sessionInfo);
//
//            if (stream != null) {
//                try {
//                    GetDocumentsResult result = command.getResult();
//                    JsonExtensions.getDefaultMapper().writeValue(stream, result);
//                } catch (IOException e) {
//                    throw new RuntimeException("Unable to serialize returned value into stream" + e.getMessage(), e);
//                }
//            } else {
//                operation.setResult(command.getResult());
//            }
//        }
//        return command;
//    }
//
//    @Override
//    public void loadIntoStream(Collection<String> ids, OutputStream output) {
//        if (ids == null) {
//            throw new IllegalArgumentException("Ids cannot be null");
//        }
//
//        loadInternal(ids.toArray(new String[0]), new LoadOperation(this), output);
//    }
//
//    @Override
//    public <T, U> void increment(T entity, String path, U valueToAdd) {
//        IMetadataDictionary metadata = getMetadataFor(entity);
//        String id = (String) metadata.get(Constants.Documents.Metadata.ID);
//        increment(id, path, valueToAdd);
//    }
//
//    private int _valsCount;
//    private int _customCount;
//
//    @Override
//    public <T, U> void increment(String id, String path, U valueToAdd) {
//        PatchRequest patchRequest = new PatchRequest();
//
//        String variable = "this." + path;
//        String value = "args.val_" + _valsCount;
//        patchRequest.setScript(variable + " = " + variable
//                + " ? " + variable + " + " + value
//                + " : " + value + ";");
//        patchRequest.setValues(Collections.singletonMap("val_" + _valsCount, valueToAdd));
//
//        _valsCount++;
//
//        if (!tryMergePatches(id, patchRequest)) {
//            defer(new PatchCommandData(id, null, patchRequest, null));
//        }
//    }
//
//    @Override
//    public <T, TU> void addOrIncrement(String id, T entity, String pathToObject, TU valToAdd) {
//        String variable = "this." + pathToObject;
//        String value = "args.val_" + _valsCount;
//
//        PatchRequest patchRequest = new PatchRequest();
//        patchRequest.setScript(variable + " = " + variable + " ? " + variable + " + " + value + " : " + value);
//        patchRequest.setValues(Collections.singletonMap("val_" + _valsCount, valToAdd));
//
//        String collectionName = _requestExecutor.getConventions().getCollectionName(entity);
//        String javaType = _requestExecutor.getConventions().getJavaClassName(entity.getClass());
//
//        MetadataAsDictionary metadataAsDictionary = new MetadataAsDictionary();
//        metadataAsDictionary.put(Constants.Documents.Metadata.COLLECTION, collectionName);
//        metadataAsDictionary.put(Constants.Documents.Metadata.RAVEN_JAVA_TYPE, javaType);
//
//        DocumentInfo documentInfo = new DocumentInfo();
//        documentInfo.setId(id);
//        documentInfo.setCollection(collectionName);
//        documentInfo.setMetadataInstance(metadataAsDictionary);
//
//        ObjectNode newInstance = getEntityToJson().convertEntityToJson(entity, documentInfo);
//
//        _valsCount++;
//
//        PatchCommandData patchCommandData = new PatchCommandData(id, null, patchRequest);
//        patchCommandData.setCreateIfMissing(newInstance);
//        defer(patchCommandData);
//    }
//
//    @Override
//    public <T, TU> void addOrPatchArray(String id, T entity, String pathToArray, Consumer<JavaScriptArray<TU>> arrayAdder) {
//        JavaScriptArray<TU> scriptArray = new JavaScriptArray<>(_customCount++, pathToArray);
//
//        arrayAdder.accept(scriptArray);
//
//        PatchRequest patchRequest = new PatchRequest();
//        patchRequest.setScript(scriptArray.getScript());
//        patchRequest.setValues(scriptArray.getParameters());
//
//        String collectionName = _requestExecutor.getConventions().getCollectionName(entity);
//        String javaType = _requestExecutor.getConventions().getJavaClassName(entity.getClass());
//
//        MetadataAsDictionary metadataAsDictionary = new MetadataAsDictionary();
//        metadataAsDictionary.put(Constants.Documents.Metadata.COLLECTION, collectionName);
//        metadataAsDictionary.put(Constants.Documents.Metadata.RAVEN_JAVA_TYPE, javaType);
//
//        DocumentInfo documentInfo = new DocumentInfo();
//        documentInfo.setId(id);
//        documentInfo.setCollection(collectionName);
//        documentInfo.setMetadataInstance(metadataAsDictionary);
//
//        ObjectNode newInstance = getEntityToJson().convertEntityToJson(entity, documentInfo);
//
//        _valsCount++;
//
//        PatchCommandData patchCommandData = new PatchCommandData(id, null, patchRequest);
//        patchCommandData.setCreateIfMissing(newInstance);
//        defer(patchCommandData);
//    }
//
//    @Override
//    public <T, TU> void addOrPatch(String id, T entity, String pathToObject, TU value) {
//        PatchRequest patchRequest = new PatchRequest();
//        patchRequest.setScript("this." + pathToObject + " = args.val_" + _valsCount);
//        patchRequest.setValues(Collections.singletonMap("val_" + _valsCount, value));
//
//        String collectionName = _requestExecutor.getConventions().getCollectionName(entity);
//        String javaType = _requestExecutor.getConventions().getJavaClassName(entity.getClass());
//
//        MetadataAsDictionary metadataAsDictionary = new MetadataAsDictionary();
//        metadataAsDictionary.put(Constants.Documents.Metadata.COLLECTION, collectionName);
//        metadataAsDictionary.put(Constants.Documents.Metadata.RAVEN_JAVA_TYPE, javaType);
//
//        DocumentInfo documentInfo = new DocumentInfo();
//        documentInfo.setId(id);
//        documentInfo.setCollection(collectionName);
//        documentInfo.setMetadataInstance(metadataAsDictionary);
//
//        ObjectNode newInstance = getEntityToJson().convertEntityToJson(entity, documentInfo);
//
//        _valsCount++;
//
//        PatchCommandData patchCommandData = new PatchCommandData(id, null, patchRequest);
//        patchCommandData.setCreateIfMissing(newInstance);
//        defer(patchCommandData);
//    }
//
//    @Override
//    public <T, U> void patch(T entity, String path, U value) {
//        IMetadataDictionary metadata = getMetadataFor(entity);
//        String id = (String) metadata.get(Constants.Documents.Metadata.ID);
//        patch(id, path, value);
//    }
//
//    @Override
//    public <T, U> void patch(String id, String path, U value) {
//        PatchRequest patchRequest = new PatchRequest();
//        patchRequest.setScript("this." + path + " = args.val_" + _valsCount + ";");
//        patchRequest.setValues(Collections.singletonMap("val_" + _valsCount, value));
//
//        _valsCount++;
//
//        if (!tryMergePatches(id, patchRequest)) {
//            defer(new PatchCommandData(id, null, patchRequest, null));
//        }
//    }
//
//    @Override
//    public <T, U> void patchArray(T entity, String pathToArray, Consumer<JavaScriptArray<U>> arrayAdder) {
//        IMetadataDictionary metadata = getMetadataFor(entity);
//        String id = (String) metadata.get(Constants.Documents.Metadata.ID);
//        patchArray(id, pathToArray, arrayAdder);
//    }
//
//    @Override
//    public <T, U> void patchArray(String id, String pathToArray, Consumer<JavaScriptArray<U>> arrayAdder) {
//        JavaScriptArray<U> scriptArray = new JavaScriptArray<>(_customCount++, pathToArray);
//
//        arrayAdder.accept(scriptArray);
//
//        PatchRequest patchRequest = new PatchRequest();
//        patchRequest.setScript(scriptArray.getScript());
//        patchRequest.setValues(scriptArray.getParameters());
//
//        if (!tryMergePatches(id, patchRequest)) {
//            defer(new PatchCommandData(id, null, patchRequest, null));
//        }
//    }
//
//    @Override
//    public <T, TKey, TValue> void patchObject(T entity, String pathToObject, Consumer<JavaScriptMap<TKey, TValue>> mapAdder) {
//        IMetadataDictionary metadata = getMetadataFor(entity);
//        String id = (String) metadata.get(Constants.Documents.Metadata.ID);
//        patchObject(id, pathToObject, mapAdder);
//    }
//
//    @Override
//    public <T, TKey, TValue> void patchObject(String id, String pathToObject, Consumer<JavaScriptMap<TKey, TValue>> mapAdder) {
//        JavaScriptMap<TKey, TValue> scriptMap = new JavaScriptMap<>(_customCount++, pathToObject);
//
//        mapAdder.accept(scriptMap);
//
//        PatchRequest patchRequest = new PatchRequest();
//        patchRequest.setScript(scriptMap.getScript());
//        patchRequest.setValues(scriptMap.getParameters());
//
//        if (!tryMergePatches(id, patchRequest)) {
//            defer(new PatchCommandData(id, null, patchRequest, null));
//        }
//    }
//
//    @SuppressWarnings("BooleanMethodIsAlwaysInverted")
//    private boolean tryMergePatches(String id, PatchRequest patchRequest) {
//        ICommandData command = deferredCommandsMap.get(IdTypeAndName.create(id, CommandType.PATCH, null));
//        if (command == null) {
//            return false;
//        }
//
//        deferredCommands.remove(command);
//        // We'll overwrite the deferredCommandsMap when calling Defer
//        // No need to call deferredCommandsMap.remove((id, CommandType.PATCH, null));
//
//        PatchCommandData oldPatch = (PatchCommandData) command;
//        String newScript = oldPatch.getPatch().getScript() + "\n" + patchRequest.getScript();
//        Map<String, Object> newVals = new HashMap<>(oldPatch.getPatch().getValues());
//
//        for (Map.Entry<String, Object> kvp : patchRequest.getValues().entrySet()) {
//            newVals.put(kvp.getKey(), kvp.getValue());
//        }
//
//        PatchRequest newPatchRequest = new PatchRequest();
//        newPatchRequest.setScript(newScript);
//        newPatchRequest.setValues(newVals);
//
//        defer(new PatchCommandData(id, null, newPatchRequest, null));
//
//        return true;
//    }

    /**
     * Query the specified index using Lucene syntax
     * @param string $className The result of the query
     * @param string|null|AbstractCommonApiForIndexes $indexName Name of the index (mutually exclusive with collectionName) or AbstractCommonApiForIndexes class name
     * @param string|null $collectionName Name of the collection (mutually exclusive with indexName)
     * @param bool $isMapReduce Whether we are querying a map/reduce index (modify how we treat identifier properties)
     */
    public function documentQuery(string $className, $indexName = null, ?string $collectionName = null, bool $isMapReduce = false): DocumentQueryInterface
    {
        if (class_exists($indexName) && is_a($indexName,  AbstractCommonApiForIndexes::class, true)) {
            try {
                $index = new $indexName();
                return  $this->_documentQuery($className, $index->getIndexName(), null, $index->isMapReduce());
            } catch (IllegalStateException $e) {
                throw new RuntimeException("Unable to query index: " . $indexName . '. ' . $e->getMessage(), $e->getCode());
            }
        }

        return $this->_documentQuery($className, $indexName, $collectionName, $isMapReduce);
    }

    protected function _documentQuery(string $className, ?string $indexName = null, ?string $collectionName = null, bool $isMapReduce = false): DocumentQueryInterface
    {
        list($indexName, $collectionName) = $this->processQueryParameters($className, $indexName, $collectionName, $this->getConventions());

        return new DocumentQuery($className, $this, $indexName, $collectionName, $isMapReduce);
    }


    public function getSession(): InMemoryDocumentSessionOperations
    {
        return $this;
    }

    public function rawQuery(string $className, string $query): RawDocumentQueryInterface
    {
        return new RawDocumentQuery($className, $this, $query);
    }

    /**
     *
     *
     * @param string $className
     * @param Query|null|string $collectionOrIndexName
     *
     * @return DocumentQueryInterface
     */
    public function query(string $className, $collectionOrIndexName = null): DocumentQueryInterface
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
//
//    @Override
//    public ISessionDocumentCounters countersFor(String documentId) {
//        return new SessionDocumentCounters(this, documentId);
//    }
//
//    @Override
//    public ISessionDocumentCounters countersFor(Object entity) {
//        return new SessionDocumentCounters(this, entity);
//    }
//
//    @Override
//    public <T> IGraphDocumentQuery<T> graphQuery(Class<T> clazz, String query) {
//        GraphDocumentQuery<T> graphQuery = new GraphDocumentQuery<T>(clazz, this, query);
//        return graphQuery;
//    }
//
//    @Override
//    public ISessionDocumentTimeSeries timeSeriesFor(String documentId, String name) {
//        return new SessionDocumentTimeSeries(this, documentId, name);
//    }
//
//    @Override
//    public ISessionDocumentTimeSeries timeSeriesFor(Object entity, String name) {
//        return new SessionDocumentTimeSeries(this, entity, name);
//    }
//
//    @Override
//    public <T> ISessionDocumentTypedTimeSeries<T> timeSeriesFor(Class<T> clazz, Object entity) {
//        return timeSeriesFor(clazz, entity, null);
//    }
//
//    @Override
//    public <T> ISessionDocumentTypedTimeSeries<T> timeSeriesFor(Class<T> clazz, Object entity, String name) {
//        String tsName = ObjectUtils.firstNonNull(name, TimeSeriesOperations.getTimeSeriesName(clazz, getConventions()));
//        return new SessionDocumentTypedTimeSeries<T>(clazz, this, entity, tsName);
//    }
//
//    @Override
//    public <T> ISessionDocumentTypedTimeSeries<T> timeSeriesFor(Class<T> clazz, String documentId) {
//        return timeSeriesFor(clazz, documentId, null);
//    }
//
//    @Override
//    public <T> ISessionDocumentTypedTimeSeries<T> timeSeriesFor(Class<T> clazz, String documentId, String name) {
//        String tsName = ObjectUtils.firstNonNull(name, TimeSeriesOperations.getTimeSeriesName(clazz, getConventions()));
//        return new SessionDocumentTypedTimeSeries<>(clazz, this, documentId, tsName);
//    }
//
//    @Override
//    public <T> ISessionDocumentRollupTypedTimeSeries<T> timeSeriesRollupFor(Class<T> clazz, Object entity, String policy) {
//        return timeSeriesRollupFor(clazz, entity, policy, null);
//    }
//
//    @Override
//    public <T> ISessionDocumentRollupTypedTimeSeries<T> timeSeriesRollupFor(Class<T> clazz, Object entity, String policy, String raw) {
//        String tsName = ObjectUtils.firstNonNull(raw, TimeSeriesOperations.getTimeSeriesName(clazz, getConventions()));
//        return new SessionDocumentRollupTypedTimeSeries<T>(clazz, this, entity, tsName + TimeSeriesConfiguration.TIME_SERIES_ROLLUP_SEPARATOR + policy);
//    }
//
//    @Override
//    public <T> ISessionDocumentRollupTypedTimeSeries<T> timeSeriesRollupFor(Class<T> clazz, String documentId, String policy) {
//        return timeSeriesRollupFor(clazz, documentId, policy, null);
//    }
//
//    @Override
//    public <T> ISessionDocumentRollupTypedTimeSeries<T> timeSeriesRollupFor(Class<T> clazz, String documentId, String policy, String raw) {
//        String tsName = ObjectUtils.firstNonNull(raw, TimeSeriesOperations.getTimeSeriesName(clazz, getConventions()));
//        return new SessionDocumentRollupTypedTimeSeries<T>(clazz, this, documentId, tsName + TimeSeriesConfiguration.TIME_SERIES_ROLLUP_SEPARATOR + policy);
//    }
//
//    @Override
//    public <T> ConditionalLoadResult<T> conditionalLoad(Class<T> clazz, String id, String changeVector) {
//        if (StringUtils.isEmpty(id)) {
//            throw new IllegalArgumentException("Id cannot be null");
//        }
//
//        if (advanced().isLoaded(id)) {
//            T entity = load(clazz, id);
//            if (entity == null) {
//                return ConditionalLoadResult.create(null, null);
//            }
//
//            String cv = advanced().getChangeVectorFor(entity);
//            return ConditionalLoadResult.create(entity, cv);
//        }
//
//        if (StringUtils.isEmpty(changeVector)) {
//            throw new IllegalArgumentException("The requested document with id '" + id + "' is not loaded into the session and could not conditional load when changeVector is null or empty.");
//        }
//
//        incrementRequestCount();
//
//        ConditionalGetDocumentsCommand cmd = new ConditionalGetDocumentsCommand(id, changeVector);
//        advanced().getRequestExecutor().execute(cmd);
//
//        switch (cmd.getStatusCode()) {
//            case HttpStatus.SC_NOT_MODIFIED:
//                return ConditionalLoadResult.create(null, changeVector); // value not changed
//            case HttpStatus.SC_NOT_FOUND:
//                registerMissing(id);;
//                return ConditionalLoadResult.create(null, null); // value is missing
//        }
//
//        DocumentInfo documentInfo = DocumentInfo.getNewDocumentInfo((ObjectNode) cmd.getResult().getResults().get(0));
//        T r = trackEntity(clazz, documentInfo);
//        return ConditionalLoadResult.create(r, cmd.getResult().getChangeVector());
//    }


}
