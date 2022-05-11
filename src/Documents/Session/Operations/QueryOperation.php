<?php

namespace RavenDB\Documents\Session\Operations;

use RavenDB\Constants\DocumentsMetadata;
use RavenDB\Constants\TimeSeries;
use RavenDB\Documents\Commands\QueryCommand;
use RavenDB\Documents\Queries\IndexQuery;
use RavenDB\Documents\Queries\QueryResult;
use RavenDB\Documents\Session\InMemoryDocumentSessionOperations;
use RavenDB\Documents\Session\Tokens\FieldsToFetchToken;
use RavenDB\Exceptions\Documents\Indexes\IndexDoesNotExistException;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Exceptions\TimeoutException;
use RavenDB\Primitives\CleanCloseable;
use RavenDB\Type\Duration;
use RavenDB\Utils\Stopwatch;
use RuntimeException;
use Throwable;

class QueryOperation
{
    private ?InMemoryDocumentSessionOperations $session = null;
    private ?string $indexName = null;
    private IndexQuery $indexQuery;
    private bool $metadataOnly = false;
    private bool $indexEntriesOnly = false;
    private bool $isProjectInto = false;
    private ?QueryResult $currentQueryResults = null;
    private ?FieldsToFetchToken $fieldsToFetch = null;
    private ?Stopwatch $sp = null;
    private bool $noTracking;

//    private static final Log logger = LogFactory.getLog(QueryOperation.class);
//    private static PropertyDescriptor[] _facetResultFields;
//
//    static {
//        try {
//            _facetResultFields = Arrays.stream(Introspector.getBeanInfo(FacetResult.class).getPropertyDescriptors())
//                    .filter(x -> !"class".equals(x.getName()))
//                    .toArray(PropertyDescriptor[]::new);
//        } catch (IntrospectionException e) {
//            // ignore
//        }
//    }

    public function __construct(
        ?InMemoryDocumentSessionOperations $session,
        ?string $indexName,
        IndexQuery $indexQuery,
        ?FieldsToFetchToken $fieldsToFetch,
        bool $disableEntitiesTracking,
        bool $metadataOnly,
        bool $indexEntriesOnly,
        bool $isProjectInto
    ) {
        $this->session = $session;
        $this->indexName = $indexName;
        $this->indexQuery = $indexQuery;
        $this->fieldsToFetch = $fieldsToFetch;
        $this->noTracking = $disableEntitiesTracking;
        $this->metadataOnly = $metadataOnly;
        $this->indexEntriesOnly = $indexEntriesOnly;
        $this->isProjectInto = $isProjectInto;

        $this->assertPageSizeSet();
    }

    public function createRequest(): QueryCommand
    {
        $this->session->incrementRequestCount();

        $this->logQuery();

        return new QueryCommand($this->session, $this->indexQuery, $this->metadataOnly, $this->indexEntriesOnly);
    }

    public function getCurrentQueryResults(): ?QueryResult
    {
        return $this->currentQueryResults;
    }

    public function setResult(?QueryResult $queryResult): void
    {
        $this->ensureIsAcceptableAndSaveResult($queryResult);
    }

    private function assertPageSizeSet(): void
    {
        if (!$this->session->getConventions()->isThrowIfQueryPageSizeIsNotSet()) {
            return;
        }

        if ($this->indexQuery->isPageSizeSet()) {
            return;
        }

        throw new IllegalStateException("Attempt to query without explicitly specifying a page size. " .
                "You can use .take() methods to set maximum number of results. By default the page size is set to Integer.MAX_VALUE and can cause severe performance degradation.");
    }

    private function startTiming(): void
    {
        $this->sp = Stopwatch::createStarted();
    }

    public function logQuery(): void
    {
        // @todo: uncomment this - logging
//        if (logger.isInfoEnabled()) {
//            logger.info("Executing query " + _indexQuery.getQuery() + " on index " + _indexName + " in " + _session.storeIdentifier());
//        }
    }

    public function enterQueryContext(): ?CleanCloseable
    {
        $this->startTiming();

        if (!$this->indexQuery->isWaitForNonStaleResults()) {
            return null;
        }

//        @todo: uncomment this if aggressive cashing is added
        return null;
//        return $this->session->getDocumentStore()->disableAggressiveCaching($this->session->getDatabaseName());
    }

//    @SuppressWarnings("unchecked")
//    public <T> T[] completeAsArray(Class<T> clazz) {
//        QueryResult queryResult = _currentQueryResults.createSnapshot();
//
//        T[] result = (T[]) Array.newInstance(clazz, queryResult.getResults().size());
//        completeInternal(clazz, queryResult, (idx, item) -> result[idx] = item);
//
//        return result;
//    }

    public function complete(string $className): array
    {
        $queryResult = $this->currentQueryResults->createSnapshot();

        return $this->completeInternal($className, $queryResult);
    }

    private function completeInternal(string $className, QueryResult $queryResult): array
    {
        if (!$this->noTracking) {
//            $this->session->registerIncludes($queryResult->getIncludes());
        }

        $resultItems = [];

        try {

            foreach ($queryResult->getResults() as $document) {

                $metadata = array_key_exists(DocumentsMetadata::KEY, $document) ? $document[DocumentsMetadata::KEY] : null;
                try {
                    $idNode = array_key_exists(DocumentsMetadata::ID, $metadata) ? $metadata[DocumentsMetadata::ID] : NULL;

                    $id = null;
                    if ($idNode != null && is_string($idNode)) {
                        $id = $idNode;
                    }

                    $resultItems[] = self::deserialize($className, $id, $document, $metadata, $this->fieldsToFetch, $this->noTracking, $this->session, $this->isProjectInto);
                } catch (Throwable $e) {
//                } catch (NullPointerException $e) {
//                    if (document.size() != _facetResultFields.length) {
//                        throw e;
//                    }
//
//                    for (PropertyDescriptor prop : _facetResultFields) {
//                        if (document.get(StringUtils.capitalize(prop.getName())) == null) {
//                            throw e;
//                        }
//                    }
//
                    throw new IllegalArgumentException("Raw query with aggregation by facet should be called by executeAggregation method." . $e->getMessage());
                }
            }
        } catch (Throwable $e) {
            throw new RuntimeException("Unable to read json: " . $e->getMessage(), 0, $e);
        }

//        if (!_noTracking) {
//            _session.registerMissingIncludes(queryResult.getResults(), queryResult.getIncludes(), queryResult.getIncludedPaths());
//
//            if (queryResult.getCounterIncludes() != null) {
//                _session.registerCounters(queryResult.getCounterIncludes(), queryResult.getIncludedCounterNames());
//            }
//            if (queryResult.getTimeSeriesIncludes() != null) {
//                _session.registerTimeSeries(queryResult.getTimeSeriesIncludes());
//            }
//            if (queryResult.getCompareExchangeValueIncludes() != null) {
//                _session.getClusterSession().registerCompareExchangeValues(queryResult.getCompareExchangeValueIncludes());
//            }
//        }

        return $resultItems;
    }

    public static function deserialize(string $className, ?string $id, array $document, array $metadata, ?FieldsToFetchToken $fieldsToFetch, bool $disableEntitiesTracking, InMemoryDocumentSessionOperations $session, bool $isProjectInto): object
    {
        if (array_key_exists('@projection', $metadata) && $metadata['@projection'] !== null && !boolval($metadata['@projection'])) {
            return $session->trackEntity($className, $id, $document, $metadata, $disableEntitiesTracking);
        }

        $singleField = $fieldsToFetch != null && $fieldsToFetch->projections != null && count($fieldsToFetch->projections) == 1;

        if ($singleField) { // we only select a single field
            $projectionField = $fieldsToFetch->projections[0];

            if ($fieldsToFetch->sourceAlias != null) {

                if (str_starts_with($projectionField, $fieldsToFetch->sourceAlias)) {
                    // remove source-alias from projection name
                    $projectionField = substr($projectionField, strlen($fieldsToFetch->sourceAlias) + 1);
                }

                if (str_starts_with($projectionField, "'")) {
                    $projectionField = substr($projectionField, 1, strlen($projectionField) - 1);
                }
            }

//            if (String.class.equals(clazz) || ClassUtils.isPrimitiveOrWrapper(clazz) || clazz.isEnum()) {
//                JsonNode jsonNode = document.get(projectionField);
//                if (jsonNode instanceof ValueNode) {
//                    return ObjectUtils.firstNonNull(session.getConventions().getEntityMapper().treeToValue(jsonNode, clazz), Defaults.defaultValue(clazz));
//                }
//            }
//
            $isTimeSeriesField = str_starts_with($fieldsToFetch->projections[0], TimeSeries::QUERY_FUNCTION);

//            if (!isProjectInto || isTimeSeriesField) {
//                JsonNode inner = document.get(projectionField);
//                if (inner == null) {
//                    return Defaults.defaultValue(clazz);
//                }
//
//                if (isTimeSeriesField || fieldsToFetch.fieldsToFetch != null && fieldsToFetch.fieldsToFetch[0].equals(fieldsToFetch.projections[0])) {
//                    if (inner instanceof ObjectNode) { //extraction from original type
//                        document = (ObjectNode) inner;
//                    }
//                }
//            }
        }
//
//        if (ObjectNode.class.equals(clazz)) {
//            return (T)document;
//        }
//
//        Reference<ObjectNode> documentRef = new Reference<>(document);
//        session.onBeforeConversionToEntityInvoke(id, clazz, documentRef);
//        document = documentRef.value;

        $result = $session->getConventions()->getEntityMapper()->denormalize($document, $className);

//        session.onAfterConversionToEntityInvoke(id, document, result);
//
        return $result;
    }

    public function isNoTracking(): bool
    {
        return $this->noTracking;
    }

    public function setNoTracking(bool $noTracking): void
    {
        $this->noTracking = $noTracking;
    }

    public function ensureIsAcceptableAndSaveResult(?QueryResult $result, ?Duration $duration = null): void
    {
        if ($duration == null) {
            if ($this->sp != null) {
                $this->sp->stop();
                $duration = $this->sp->elapsed();
            }
        }

        if ($result == null) {
            throw new IndexDoesNotExistException("Could not find index " . $this->indexName);
        }

        self::ensureIsAcceptable($result, $this->indexQuery->isWaitForNonStaleResults(), $duration, $this->session);

        $this->saveQueryResult($result);
    }

    private function saveQueryResult(QueryResult $result): void
    {
        $this->currentQueryResults = $result;

//        if (logger.isInfoEnabled()) {
//            String isStale = result.isStale() ? " stale " : " ";
//
//            StringBuilder parameters = new StringBuilder();
//            if (_indexQuery.getQueryParameters() != null && !_indexQuery.getQueryParameters().isEmpty()) {
//                parameters.append("(parameters: ");
//
//                boolean first = true;
//
//                for (Map.Entry<String, Object> parameter : _indexQuery.getQueryParameters().entrySet()) {
//                    if (!first) {
//                        parameters.append(", ");
//                    }
//
//                    parameters.append(parameter.getKey())
//                            .append(" = ")
//                            .append(parameter.getValue());
//
//                    first = false;
//                }
//
//                parameters.append(") ");
//            }
//
//            logger.info("Query " + _indexQuery.getQuery() + " " + parameters.toString() + "returned " + result.getResults().size() + isStale + "results (total index results: " + result.getTotalResults() + ")");
//        }
    }

    /**
     * @param QueryResult $result
     * @param bool $waitForNonStaleResults
     * @param Stopwatch|Duration|null $duration
     * @param InMemoryDocumentSessionOperations $session
     */
    public static function ensureIsAcceptable(QueryResult $result, bool $waitForNonStaleResults, $duration, InMemoryDocumentSessionOperations $session): void
    {
        if ($duration instanceof Stopwatch) {
            $duration->stop();
            $duration = $duration->elapsed();
        }

        if ($waitForNonStaleResults && $result->isStale()) {
            $elapsed = $duration == null ? "" : " " . $duration->toMillis() . " ms";
            $msg = "Waited" . $elapsed . " for the query to return non stale result.";
            throw new TimeoutException($msg);
        }
    }

    public function getIndexQuery(): IndexQuery
    {
        return $this->indexQuery;
    }
}
