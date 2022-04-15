<?php

namespace RavenDB\Documents\Session;

use RavenDB\Constants\DocumentsIndexingFields;
use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Queries\IndexQuery;
use RavenDB\Documents\Queries\ProjectionBehavior;
use RavenDB\Documents\Queries\QueryFieldUtil;
use RavenDB\Documents\Queries\QueryOperator;
use RavenDB\Documents\Queries\QueryResult;
use RavenDB\Documents\Queries\SearchOperator;
use RavenDB\Documents\Queries\Timings\QueryTimings;
use RavenDB\Documents\Session\Operations\QueryOperation;
use RavenDB\Documents\Session\Tokens\CloseSubclauseToken;
use RavenDB\Documents\Session\Tokens\CompareExchangeValueIncludesTokenArray;
use RavenDB\Documents\Session\Tokens\CounterIncludesTokenArray;
use RavenDB\Documents\Session\Tokens\DeclareTokenArray;
use RavenDB\Documents\Session\Tokens\DistinctToken;
use RavenDB\Documents\Session\Tokens\ExplanationToken;
use RavenDB\Documents\Session\Tokens\FieldsToFetchToken;
use RavenDB\Documents\Session\Tokens\FromToken;
use RavenDB\Documents\Session\Tokens\GraphQueryToken;
use RavenDB\Documents\Session\Tokens\HighlightingTokenArray;
use RavenDB\Documents\Session\Tokens\LoadTokenList;
use RavenDB\Documents\Session\Tokens\MoreLikeThisToken;
use RavenDB\Documents\Session\Tokens\NegateToken;
use RavenDB\Documents\Session\Tokens\OpenSubclauseToken;
use RavenDB\Documents\Session\Tokens\QueryOperatorToken;
use RavenDB\Documents\Session\Tokens\QueryToken;
use RavenDB\Documents\Session\Tokens\QueryTokenList;
use RavenDB\Documents\Session\Tokens\TimeSeriesIncludesTokenArray;
use RavenDB\Documents\Session\Tokens\TimingsToken;
use RavenDB\Documents\Session\Tokens\TrueToken;
use RavenDB\Documents\Session\Tokens\WhereOperator;
use RavenDB\Documents\Session\Tokens\WhereOptions;
use RavenDB\Documents\Session\Tokens\WhereToken;
use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Parameters;
use RavenDB\Primitives\CleanCloseable;
use RavenDB\Primitives\ClosureArray;
use RavenDB\Primitives\EventHelper;
use RavenDB\Type\Duration;
use RavenDB\Type\StringSet;
use RavenDB\Utils\StringBuilder;
use RavenDB\Utils\StringUtils;

abstract class AbstractDocumentQuery implements AbstractDocumentQueryInterface
{
    protected string $className;

//    private final Map<String, String> _aliasToGroupByFieldName = new HashMap<>();
//
    protected QueryOperator $defaultOperator;

    protected StringSet $rootTypes;

    /**
     * Whether to negate the next operation
     */
    protected bool $negate = false;

    private ?string $indexName = null;
    private ?string $collectionName = null;
    private int $currentClauseDepth = 0;

    protected string $queryRaw = '';

    public function getIndexName(): ?string
    {
        return $this->indexName;
    }

    public function getCollectionName(): ?string
    {
        return $this->collectionName;
    }

    protected ?Parameters $queryParameters = null;

    protected bool $isIntersect = false;

    protected bool $isGroupBy = false;

    protected InMemoryDocumentSessionOperations $theSession;

    protected ?int $pageSize = null;

    protected QueryTokenList $selectTokens;

    protected FromToken $fromToken;
    protected ?DeclareTokenArray $declareTokens = null;
    protected ?LoadTokenList $loadTokens = null;
    protected ?FieldsToFetchToken $fieldsToFetchToken = null;

    public bool $isProjectInto = false;

    protected QueryTokenList $whereTokens;

    protected QueryTokenList $groupByTokens;

    protected QueryTokenList $orderByTokens;

    protected QueryTokenList $withTokens;

    protected ?QueryToken $graphRawQuery = null;

    protected int $start = 0;

    private DocumentConventions $conventions;

    protected ?Duration $timeout = null;

    protected bool $theWaitForNonStaleResults = false;

    protected StringSet $documentIncludes;

    /**
     * Holds the query stats
     */
//    protected QueryStatistics queryStats = new QueryStatistics();

    protected bool $disableEntitiesTracking = false;

    protected bool $disableCaching = false;

//    protected ProjectionBehavior projectionBehavior;

    private string $parameterPrefix = "p";

    public function isDistinct(): bool
    {
        return ($this->selectTokens->isNotEmpty()) && ($this->selectTokens->offsetGet(0) instanceof DistinctToken);
    }

    public function getFieldsToFetchToken(): FieldsToFetchToken
    {
        return $this->fieldsToFetchToken;
    }

    public function setFieldsToFetchToken(FieldsToFetchToken $fieldsToFetchToken): void
    {
        $this->fieldsToFetchToken = $fieldsToFetchToken;
    }

    public function isProjectInto(): bool
    {
        return $this->isProjectInto;
    }

    public function setProjectInto(bool $projectInto): void {
        $this->isProjectInto = $projectInto;
    }

    /**
     * Gets the document convention from the query session
     */
    public function getConventions(): DocumentConventions
    {
        return $this->conventions;
    }

    /**
     * Gets the session associated with this document query
     * @return DocumentSessionInterface session
     */
    public function getSession(): DocumentSessionInterface
    {
        /** @var DocumentSessionInterface $session */
        $session = $this->theSession;
        return $session;
    }

    public function isDynamicMapReduce(): bool
    {
        return !empty($this->groupByTokens);
    }

    private bool $isInMoreLikeThis = false;

    private string $includesAlias;

    private function getDefaultTimeout(): Duration
    {
        return $this->conventions->getWaitForNonStaleResultsTimeout();
    }

    protected function __construct(
        string $className,
        ?InMemoryDocumentSessionOperations $session,
        ?string $indexName,
        ?string $collectionName,
        bool $isGroupBy,
        ?DeclareTokenArray $declareTokens,
        ?LoadTokenList $loadTokens,
        ?string $fromAlias = null,
        bool $isProjectInto = false
    ) {
        $this->queryParameters = new Parameters();

        $this->beforeQueryExecutedCallback = new ClosureArray();
        $this->afterQueryExecutedCallback = new ClosureArray();
        $this->afterStreamExecutedCallback = new ClosureArray();


        $this->selectTokens = new QueryTokenList();
        $this->whereTokens = new QueryTokenList();
        $this->groupByTokens = new QueryTokenList();
        $this->orderByTokens = new QueryTokenList();
        $this->withTokens = new QueryTokenList();

        $this->documentIncludes = new StringSet();
        $this->highlightingTokens = new HighlightingTokenArray();

        $this->rootTypes = new StringSet();

        $this->defaultOperator = QueryOperator::and();
        //
        //--

        $this->className = $className;
        $this->rootTypes->append($className);
        $this->isGroupBy = $isGroupBy;
        $this->indexName = $indexName;
        $this->collectionName = $collectionName;
        $this->fromToken = FromToken::create($indexName, $collectionName, $fromAlias);
        $this->declareTokens = $declareTokens;
        $this->loadTokens = $loadTokens;
        $this->theSession = $session;
//        _addAfterQueryExecutedListener(this::updateStatsHighlightingsAndExplanations);
        $this->conventions = $session == null ? new DocumentConventions() : $session->getConventions();
        $this->isProjectInto = $isProjectInto;
    }

    public function getQueryClass(): string
    {
        return $this->className;
    }

    public function getGraphRawQuery(): QueryToken
    {
        return $this->graphRawQuery;
    }

    public function _usingDefaultOperator(QueryOperator $operator) {
        if (!$this->whereTokens->isEmpty()) {
            throw new IllegalStateException("Default operator can only be set before any where clause is added.");
        }

        $this->defaultOperator = $operator;
    }

    /**
     * Instruct the query to wait for non stale result for the specified wait timeout.
     * This shouldn't be used outside of unit tests unless you are well aware of the implications
     * @param ?Duration $waitTimeout Wait timeout
     */
    public function _waitForNonStaleResults(?Duration $waitTimeout = null): void
    {
        //Graph queries may set this property multiple times
        if ($this->theWaitForNonStaleResults) {
            if ($this->timeout == null || $waitTimeout != null && $this->timeout->getSeconds() < $waitTimeout->getSeconds()) {
                $timeout = $waitTimeout;
            }
            return;
        }

        $this->theWaitForNonStaleResults = true;
        $$this->timeout = $waitTimeout ?? $this->getDefaultTimeout();
    }

//    protected LazyQueryOperation<T> getLazyQueryOperation() {
//        if (queryOperation == null) {
//            queryOperation = initializeQueryOperation();
//        }
//
//        return new LazyQueryOperation<>(clazz, theSession, queryOperation, afterQueryExecutedCallback);
//    }

    public function initializeQueryOperation(): QueryOperation
    {
        $beforeQueryExecutedEventArgs = new BeforeQueryEventArgs($this->theSession, new DocumentQueryCustomizationDelegate($this));
        $this->theSession->onBeforeQueryInvoke($beforeQueryExecutedEventArgs);

        $indexQuery = $this->getIndexQuery();

        return new QueryOperation(
            $this->theSession,
            $this->indexName,
            $indexQuery,
            $this->fieldsToFetchToken,
            $this->disableEntitiesTracking,
            false,
            false,
            $this->isProjectInto
        );
    }

    public function getIndexQuery(): IndexQuery
    {
        $serverVersion = null;
        if (($this->theSession != null) && ($this->theSession->getRequestExecutor() != null)) {
            $serverVersion = $this->theSession->getRequestExecutor()->getLastServerVersion();
        }

        $compatibilityMode = ($serverVersion != null) && version_compare($serverVersion, "4.2", "<");

        $query = $this->toString($compatibilityMode);
        $indexQuery = $this->generateIndexQuery($query);
        $this->invokeBeforeQueryExecuted($indexQuery);
        return $indexQuery;
    }

//    /**
//     * Gets the fields for projection
//     * @return list of projected fields
//     */
//    @Override
//    public List<String> getProjectionFields() {
//        return fieldsToFetchToken != null && fieldsToFetchToken.projections != null ? Arrays.asList(fieldsToFetchToken.projections) : Collections.emptyList();
//    }
//
//    /**
//     * Order the search results randomly
//     */
//    @Override
//    public void _randomOrdering() {
//        assertNoRawQuery();
//
//        _noCaching();
//        orderByTokens.add(OrderByToken.random);
//    }
//
//    /**
//     * Order the search results randomly using the specified seed
//     * this is useful if you want to have repeatable random queries
//     * @param seed Seed to use
//     */
//    @Override
//    public void _randomOrdering(String seed) {
//        assertNoRawQuery();
//
//        if (StringUtils.isBlank(seed)) {
//            _randomOrdering();
//            return;
//        }
//
//        _noCaching();
//        orderByTokens.add(OrderByToken.createRandom(seed));
//    }
//
//    //TBD 4.1 public void _customSortUsing(String typeName)
//    //TBD 4.1 public void _customSortUsing(String typeName, boolean descending)


    protected function _projection(ProjectionBehavior $projectionBehavior): void
    {
        $this->projectionBehavior = $projectionBehavior;
    }

//    @SuppressWarnings("unused")
//    protected void addGroupByAlias(String fieldName, String projectedName) {
//        _aliasToGroupByFieldName.put(projectedName, fieldName);
//    }

    private function assertNoRawQuery(): void
    {
        if ($this->queryRaw != null) {
            throw new IllegalStateException("RawQuery was called, cannot modify this query by calling on operations that would modify the query (such as Where, Select, OrderBy, GroupBy, etc)");
        }
    }

    public function _graphQuery(string $query): void
    {
        $this->graphRawQuery = new GraphQueryToken($query);
    }

    public function _addParameter(string $name, $value): void
    {
        $name = StringUtils::stripStart($name, "$");
        if ($this->queryParameters->offsetExists($name)) {
            throw new IllegalStateException("The parameter " . name . " was already added");
        }

        $this->queryParameters->offsetSet($name, $value);
    }

//    @Override
//    public void _groupBy(String fieldName, String... fieldNames) {
//        GroupBy[] mapping = Arrays.stream(fieldNames)
//                .map(GroupBy::field)
//                .toArray(GroupBy[]::new);
//
//        _groupBy(GroupBy.field(fieldName), mapping);
//    }
//
//    @Override
//    public void _groupBy(GroupBy field, GroupBy... fields) {
//        if (!fromToken.isDynamic()) {
//            throw new IllegalStateException("groupBy only works with dynamic queries");
//        }
//
//        assertNoRawQuery();
//        isGroupBy = true;
//
//        String fieldName = ensureValidFieldName(field.getField(), false);
//
//        groupByTokens.add(GroupByToken.create(fieldName, field.getMethod()));
//
//        if (fields == null || fields.length <= 0) {
//            return;
//        }
//
//        for (GroupBy item : fields) {
//            fieldName = ensureValidFieldName(item.getField(), false);
//            groupByTokens.add(GroupByToken.create(fieldName, item.getMethod()));
//        }
//    }
//
//    @Override
//    public void _groupByKey(String fieldName) {
//        _groupByKey(fieldName, null);
//    }
//
//    @SuppressWarnings("UnnecessaryLocalVariable")
//    @Override
//    public void _groupByKey(String fieldName, String projectedName) {
//        assertNoRawQuery();
//        isGroupBy = true;
//
//        if (projectedName != null && _aliasToGroupByFieldName.containsKey(projectedName)) {
//            String aliasedFieldName = _aliasToGroupByFieldName.get(projectedName);
//            if (fieldName == null || fieldName.equalsIgnoreCase(projectedName)) {
//                fieldName = aliasedFieldName;
//            }
//        } else if (fieldName != null && _aliasToGroupByFieldName.containsValue(fieldName)) {
//            String aliasedFieldName = _aliasToGroupByFieldName.get(fieldName);
//            fieldName = aliasedFieldName;
//        }
//
//        selectTokens.add(GroupByKeyToken.create(fieldName, projectedName));
//    }
//
//    @Override
//    public void _groupBySum(String fieldName) {
//        _groupBySum(fieldName, null);
//    }
//
//    @Override
//    public void _groupBySum(String fieldName, String projectedName) {
//        assertNoRawQuery();
//        isGroupBy = true;
//
//        fieldName = ensureValidFieldName(fieldName, false);
//        selectTokens.add(GroupBySumToken.create(fieldName, projectedName));
//    }
//
//    @Override
//    public void _groupByCount() {
//        _groupByCount(null);
//    }
//
//    @Override
//    public void _groupByCount(String projectedName) {
//        assertNoRawQuery();
//        isGroupBy = true;
//
//        selectTokens.add(GroupByCountToken.create(projectedName));
//    }


    public function _whereTrue(): void
    {
        $tokens = $this->getCurrentWhereTokens();
        $this->appendOperatorIfNeeded($tokens);
        $this->negateIfNeeded($tokens, null);

        $tokens->append(TrueToken::instance());
    }
//
//
//    public MoreLikeThisScope _moreLikeThis() {
//        appendOperatorIfNeeded(whereTokens);
//
//        MoreLikeThisToken token = new MoreLikeThisToken();
//        whereTokens.add(token);
//
//        _isInMoreLikeThis = true;
//        return new MoreLikeThisScope(token, this::addQueryParameter, () -> _isInMoreLikeThis = false);
//    }
//
//    /**
//     * Includes the specified path in the query, loading the document specified in that path
//     * @param path Path to include
//     */
//    @Override
//    public void _include(String path) {
//        documentIncludes.add(path);
//    }
//
//    //TBD expr public void Include(Expression<Func<T, object>> path)
//
//    public void _include(IncludeBuilderBase includes) {
//        if (includes == null) {
//            return;
//        }
//
//        if (includes.documentsToInclude != null) {
//            documentIncludes.addAll(includes.documentsToInclude);
//        }
//
//        _includeCounters(includes.alias, includes.countersToIncludeBySourcePath);
//        if (includes.timeSeriesToIncludeBySourceAlias != null) {
//            _includeTimeSeries(includes.alias, includes.timeSeriesToIncludeBySourceAlias);
//        }
//
//        if (includes.compareExchangeValuesToInclude != null) {
//            compareExchangeValueIncludesTokens = new ArrayList<>();
//
//            for (String compareExchangeValue : includes.compareExchangeValuesToInclude) {
//                compareExchangeValueIncludesTokens.add(CompareExchangeValueIncludesToken.create(compareExchangeValue));
//            }
//        }
//    }

    public function _take(int $count): void
    {
        $this->pageSize = $count;
    }

    public function _skip(int $count): void
    {
        $this->start = $count;
    }

    /**
     * Filter the results from the index using the specified where clause.
     * @param string $fieldName Field name
     * @param string $whereClause Where clause
     * @param bool $exact Use exact matcher
     */
    public function _whereLucene(string $fieldName, string $whereClause, bool $exact): void
    {
        $fieldName = $this->ensureValidFieldName($fieldName, false);

        $tokens = $this->getCurrentWhereTokens();
        $this->appendOperatorIfNeeded($tokens);
        $this->negateIfNeeded($tokens, $fieldName);

        $options = $exact ? new WhereOptions($exact) : null;
        $whereToken = WhereToken::create(WhereOperator::lucene(), $fieldName, $this->addQueryParameter($whereClause), $options);
        $tokens->append($whereToken);
    }

//    /**
//     * Simplified method for opening a new clause within the query
//     */
//    @Override
//    public void _openSubclause() {
//        _currentClauseDepth++;
//
//        List<QueryToken> tokens = getCurrentWhereTokens();
//        appendOperatorIfNeeded(tokens);
//        negateIfNeeded(tokens, null);
//
//        tokens.add(OpenSubclauseToken.create());
//    }
//
//    /**
//     * Simplified method for closing a clause within the query
//     */
//    @Override
//    public void _closeSubclause() {
//        _currentClauseDepth--;
//
//        List<QueryToken> tokens = getCurrentWhereTokens();
//        tokens.add(CloseSubclauseToken.create());
//    }

    protected function _whereEquals(string $fieldName, $value, bool $exact = false): void
    {
        $whereParams = new WhereParams();

        $whereParams->setFieldName($fieldName);
        $whereParams->setValue($value);
        $whereParams->setExact($exact);

        $this->_whereEqualsWithParams($whereParams);
    }

    protected function _whereEqualsWithParams(WhereParams $whereParams): void
    {
        if ($this->negate) {
            $this->negate = false;
            $this->_whereNotEqualsInternal($whereParams);
            return;
        }

        $whereParams->setFieldName($this->ensureValidFieldName($whereParams->getFieldName(), $whereParams->isNestedPath()));

        $tokens = $this->getCurrentWhereTokens();
        $this->appendOperatorIfNeeded($tokens);

        if ($this->ifValueIsMethod(WhereOperator::equals(), $whereParams, $tokens)) {
            return;
        }

        $transformToEqualValue = $this->transformValue($whereParams);
        $addQueryParameter = $this->addQueryParameter($transformToEqualValue);
        $whereToken = WhereToken::create(WhereOperator::equals(), $whereParams->getFieldName(), $addQueryParameter, new WhereOptions($whereParams->isExact()));
        $tokens->append($whereToken);
    }

    private function ifValueIsMethod(WhereOperator $op, WhereParams $whereParams, QueryTokenList $tokens): bool
    {
//        if (whereParams.getValue() instanceof MethodCall) {
//            MethodCall mc = (MethodCall) whereParams.getValue();
//
//            String[] args = new String[mc.args.length];
//            for (int i = 0; i < mc.args.length; i++) {
//                args[i] = addQueryParameter(mc.args[i]);
//            }
//
//            WhereToken token;
//            Class<? extends MethodCall> type = mc.getClass();
//            if (CmpXchg.class.equals(type)) {
//                token = WhereToken.create(op, whereParams.getFieldName(), null, new WhereToken.WhereOptions(WhereToken.MethodsType.CMP_X_CHG, args, mc.accessPath, whereParams.isExact()));
//            } else {
//                throw new IllegalArgumentException("Unknown method " + type);
//            }
//
//            tokens.add(token);
//            return true;
//        }
//
        return false;
    }

//    public void _whereNotEquals(String fieldName, Object value) {
//        _whereNotEquals(fieldName, value, false);
//    }
//
//    public void _whereNotEquals(String fieldName, Object value, boolean exact) {
//        WhereParams params = new WhereParams();
//        params.setFieldName(fieldName);
//        params.setValue(value);
//        params.setExact(exact);
//
//        _whereNotEquals(params);
//    }
//
//    @Override
//    public void _whereNotEquals(String fieldName, MethodCall method) {
//        _whereNotEquals(fieldName, (Object) method);
//    }
//
//    @Override
//    public void _whereNotEquals(String fieldName, MethodCall method, boolean exact) {
//        _whereNotEquals(fieldName, (Object) method, exact);
//    }
//
    public function _whereNotEqualsInternal(WhereParams $whereParams): void
    {
        if ($this->negate) {
            $this->negate = false;
            // @todo: chech why we don't have this method!?
//            $this->_whereEqualsInternal($whereParams);
            return;
        }

        /** @var object $transformToEqualValue */
        $transformToEqualValue = $this->transformValue($whereParams);

        $tokens = $this->getCurrentWhereTokens();
        $this->appendOperatorIfNeeded($tokens);

        $whereParams->setFieldName($this->ensureValidFieldName($whereParams->getFieldName(), $whereParams->isNestedPath()));

        if ($this->ifValueIsMethod(WhereOperator::notEquals(), $whereParams, $tokens)) {
            return;
        }

        $whereToken = WhereToken::create(WhereOperator::notEquals(), $whereParams->getFieldName(), $this->addQueryParameter($transformToEqualValue), new WhereOptions($whereParams->isExact()));
        $tokens->append($whereToken);
    }


    public function _negateNext(): void
    {
        $this->negate = !$this->negate;
    }

//    /**
//     * Check that the field has one of the specified value
//     * @param fieldName Field name to use
//     * @param values Values to find
//     */
//    @Override
//    public void _whereIn(String fieldName, Collection< ? > values) {
//        _whereIn(fieldName, values, false);
//    }
//
//    /**
//     * Check that the field has one of the specified value
//     * @param fieldName Field name to use
//     * @param values Values to find
//     * @param exact Use exact matcher
//     */
//    @Override
//    public void _whereIn(String fieldName, Collection< ? > values, boolean exact) {
//        fieldName = ensureValidFieldName(fieldName, false);
//
//        List<QueryToken> tokens = getCurrentWhereTokens();
//        appendOperatorIfNeeded(tokens);
//        negateIfNeeded(tokens, fieldName);
//
//        WhereToken whereToken = WhereToken.create(WhereOperator.IN, fieldName, addQueryParameter(transformCollection(fieldName, unpackCollection(values))));
//        tokens.add(whereToken);
//    }

    public function _whereStartsWith(string $fieldName, $value, bool $exact = false): void
    {
        $whereParams = new WhereParams();
        $whereParams->setFieldName($fieldName);
        $whereParams->setValue($value);
        $whereParams->setAllowWildcards(true);

        $transformToEqualValue = $this->transformValue($whereParams);

        $tokens = $this->getCurrentWhereTokens();
        $this->appendOperatorIfNeeded($tokens);

        $whereParams->setFieldName($this->ensureValidFieldName($whereParams->getFieldName(), $whereParams->isNestedPath()));
        $this->negateIfNeeded($tokens, $whereParams->getFieldName());

        $whereToken = WhereToken::create(WhereOperator::startsWith(), $whereParams->getFieldName(), $this->addQueryParameter($transformToEqualValue), new WhereOptions($exact));
        $tokens->append($whereToken);
    }

    /**
     * Matches fields which ends with the specified value.
     * @param string $fieldName Field name to use
     * @param mixed $value Values to find
     * @param bool $exact
     */
    public function _whereEndsWith(string $fieldName, $value, bool $exact = false): void
    {
        $whereParams = new WhereParams();
        $whereParams->setFieldName($fieldName);
        $whereParams->setValue($value);
        $whereParams->setAllowWildcards(true);

        $transformToEqualValue = $this->transformValue($whereParams);

        $tokens = $this->getCurrentWhereTokens();
        $this->appendOperatorIfNeeded($tokens);

        $whereParams->setFieldName($this->ensureValidFieldName($whereParams->getFieldName(), $whereParams->isNestedPath()));
        $this->negateIfNeeded($tokens, $whereParams->getFieldName());

        $whereToken = WhereToken::create(WhereOperator::endsWith(), $whereParams->getFieldName(), $this->addQueryParameter($transformToEqualValue), new WhereOptions($exact));
        $tokens->append($whereToken);
    }

    /**
     * Matches fields where the value is between the specified start and end, inclusive
     * @param string $fieldName Field name to use
     * @param mixed $start Range start
     * @param mixed $end Range end
     * @param bool $exact Use exact matcher
     */
    public function _whereBetween(string $fieldName, $start, $end, bool $exact = false): void
    {
        $fieldName = $this->ensureValidFieldName($fieldName, false);

        $tokens = $this->getCurrentWhereTokens();
        $this->appendOperatorIfNeeded($tokens);
        $this->negateIfNeeded($tokens, $fieldName);

        $startParams = new WhereParams();
        $startParams->setValue($start);
        $startParams->setFieldName($fieldName);

        $endParams = new WhereParams();
        $endParams->setValue($end);
        $endParams->setFieldName($fieldName);

        $fromParameterName = $this->addQueryParameter($start == null ? "*" : $this->transformValue($startParams, true));
        $toParameterName = $this->addQueryParameter($end == null ? "NULL" : $this->transformValue($endParams, true));

        $whereToken = WhereToken::create(WhereOperator::between(), $fieldName, null, new WhereOptions($exact, $fromParameterName, $toParameterName));
        $tokens->append($whereToken);
    }

//    public void _whereGreaterThan(String fieldName, Object value) {
//        _whereGreaterThan(fieldName, value, false);
//    }
//
//    /**
//     * Matches fields where the value is greater than the specified value
//     * @param fieldName Field name to use
//     * @param value Value to compare
//     * @param exact Use exact matcher
//     */
//    public void _whereGreaterThan(String fieldName, Object value, boolean exact) {
//        fieldName = ensureValidFieldName(fieldName, false);
//
//        List<QueryToken> tokens = getCurrentWhereTokens();
//        appendOperatorIfNeeded(tokens);
//        negateIfNeeded(tokens, fieldName);
//        WhereParams whereParams = new WhereParams();
//        whereParams.setValue(value);
//        whereParams.setFieldName(fieldName);
//
//        String parameter = addQueryParameter(value == null ? "*" : transformValue(whereParams, true));
//        WhereToken whereToken = WhereToken.create(WhereOperator.GREATER_THAN, fieldName, parameter, new WhereToken.WhereOptions(exact));
//        tokens.add(whereToken);
//    }
//
//    public void _whereGreaterThanOrEqual(String fieldName, Object value) {
//        _whereGreaterThanOrEqual(fieldName, value, false);
//    }
//
//    /**
//     * Matches fields where the value is greater than or equal to the specified value
//     * @param fieldName Field name to use
//     * @param value Value to compare
//     * @param exact Use exact matcher
//     */
//    public void _whereGreaterThanOrEqual(String fieldName, Object value, boolean exact) {
//        fieldName = ensureValidFieldName(fieldName, false);
//
//        List<QueryToken> tokens = getCurrentWhereTokens();
//        appendOperatorIfNeeded(tokens);
//        negateIfNeeded(tokens, fieldName);
//        WhereParams whereParams = new WhereParams();
//        whereParams.setValue(value);
//        whereParams.setFieldName(fieldName);
//
//        String parameter = addQueryParameter(value == null ? "*" : transformValue(whereParams, true));
//        WhereToken whereToken = WhereToken.create(WhereOperator.GREATER_THAN_OR_EQUAL, fieldName, parameter, new WhereToken.WhereOptions(exact));
//        tokens.add(whereToken);
//    }
//
//    public void _whereLessThan(String fieldName, Object value) {
//        _whereLessThan(fieldName, value, false);
//    }
//
//    public void _whereLessThan(String fieldName, Object value, boolean exact) {
//        fieldName = ensureValidFieldName(fieldName, false);
//
//        List<QueryToken> tokens = getCurrentWhereTokens();
//        appendOperatorIfNeeded(tokens);
//        negateIfNeeded(tokens, fieldName);
//
//        WhereParams whereParams = new WhereParams();
//        whereParams.setValue(value);
//        whereParams.setFieldName(fieldName);
//
//        String parameter = addQueryParameter(value == null ? "NULL" : transformValue(whereParams, true));
//        WhereToken whereToken = WhereToken.create(WhereOperator.LESS_THAN, fieldName, parameter, new WhereToken.WhereOptions(exact));
//        tokens.add(whereToken);
//    }
//
//    public void _whereLessThanOrEqual(String fieldName, Object value) {
//        _whereLessThanOrEqual(fieldName, value, false);
//    }
//
//    public void _whereLessThanOrEqual(String fieldName, Object value, boolean exact) {
//        fieldName = ensureValidFieldName(fieldName, false);
//
//        List<QueryToken> tokens = getCurrentWhereTokens();
//        appendOperatorIfNeeded(tokens);
//        negateIfNeeded(tokens, fieldName);
//
//        WhereParams whereParams = new WhereParams();
//        whereParams.setValue(value);
//        whereParams.setFieldName(fieldName);
//
//        String parameter = addQueryParameter(value == null ? "NULL" : transformValue(whereParams, true));
//        WhereToken whereToken = WhereToken.create(WhereOperator.LESS_THAN_OR_EQUAL, fieldName, parameter, new WhereToken.WhereOptions(exact));
//        tokens.add(whereToken);
//    }
//
//    /**
//     * Matches fields where Regex.IsMatch(filedName, pattern)
//     * @param fieldName Field name to use
//     * @param pattern Regexp pattern
//     */
//    @Override
//    public void _whereRegex(String fieldName, String pattern) {
//        fieldName = ensureValidFieldName(fieldName, false);
//
//        List<QueryToken> tokens = getCurrentWhereTokens();
//        appendOperatorIfNeeded(tokens);
//        negateIfNeeded(tokens, fieldName);
//
//        WhereParams whereParams = new WhereParams();
//        whereParams.setValue(pattern);
//        whereParams.setFieldName(fieldName);
//
//        String parameter = addQueryParameter(transformValue(whereParams));
//
//        WhereToken whereToken = WhereToken.create(WhereOperator.REGEX, fieldName, parameter);
//        tokens.add(whereToken);
//    }
//

    public function _andAlso(bool $wrapPreviousQueryClauses = false): void
    {
        $tokens = $this->getCurrentWhereTokens();
        if ($tokens->isEmpty()) {
            return;
        }

        if (end($tokens) instanceof QueryOperatorToken) {
            throw new IllegalStateException("Cannot add AND, previous token was already an operator token.");
        }

        if ($wrapPreviousQueryClauses) {
            $tokens->prepend(OpenSubclauseToken::create());
            $tokens->append(CloseSubclauseToken::create());
            $tokens->append(QueryOperatorToken::and());
        } else {
            $tokens->append(QueryOperatorToken::and());
        }
    }

    /**
     * Add an OR to the query
     */
    public function _orElse(): void
    {
        $tokens = $this->getCurrentWhereTokens();
        if ($tokens->isEmpty()) {
            return;
        }

        if ($tokens->last() instanceof QueryOperatorToken) {
            throw new IllegalStateException("Cannot add OR, previous token was already an operator token.");
        }

        $tokens->append(QueryOperatorToken::or());
    }

//    /**
//     * Specifies a boost weight to the last where clause.
//     * The higher the boost factor, the more relevant the term will be.
//     * <p>
//     * boosting factor where 1.0 is default, less than 1.0 is lower weight, greater than 1.0 is higher weight
//     * <p>
//     * http://lucene.apache.org/java/2_4_0/queryparsersyntax.html#Boosting%20a%20Term
//     *
//     * @param boost Boost value
//     */
//    @Override
//    public void _boost(double boost) {
//        if (boost == 1.0) {
//            return;
//        }
//
//        if (boost < 0.0) {
//            throw new IllegalArgumentException("Boost factor must be a non-negative number");
//        }
//
//        List<QueryToken> tokens = getCurrentWhereTokens();
//
//        QueryToken last = tokens.isEmpty() ? null : tokens.get(tokens.size() - 1);
//
//        if (last instanceof WhereToken) {
//            WhereToken whereToken = (WhereToken) last;
//            whereToken.getOptions().setBoost(boost);
//        } else if (last instanceof CloseSubclauseToken) {
//            CloseSubclauseToken close = (CloseSubclauseToken) last;
//
//            String parameter = addQueryParameter(boost);
//
//            int index = tokens.indexOf(last);
//
//            while (last != null && index > 0) {
//                index--;
//                last = tokens.get(index); // find the previous option
//
//                if (last instanceof OpenSubclauseToken) {
//                    OpenSubclauseToken open = (OpenSubclauseToken) last;
//
//                    open.setBoostParameterName(parameter);
//                    close.setBoostParameterName(parameter);
//                    return;
//                }
//            }
//        } else {
//            throw new IllegalStateException("Cannot apply boost");
//        }
//    }
//
//    /**
//     * Specifies a fuzziness factor to the single word term in the last where clause
//     * <p>
//     * 0.0 to 1.0 where 1.0 means closer match
//     * <p>
//     * http://lucene.apache.org/java/2_4_0/queryparsersyntax.html#Fuzzy%20Searches
//     * @param fuzzy Fuzzy value
//     */
//    @Override
//    public void _fuzzy(double fuzzy) {
//        List<QueryToken> tokens = getCurrentWhereTokens();
//        if (tokens.isEmpty()) {
//            throw new IllegalStateException("Fuzzy can only be used right after where clause");
//        }
//
//        QueryToken whereToken = tokens.get(tokens.size() - 1);
//        if (!(whereToken instanceof WhereToken)) {
//            throw new IllegalStateException("Fuzzy can only be used right after where clause");
//        }
//
//        if (((WhereToken) whereToken).getWhereOperator() != WhereOperator.EQUALS) {
//            throw new IllegalStateException("Fuzzy can only be used right after where clause with equals operator");
//        }
//
//        if (fuzzy < 0.0 || fuzzy > 1.0) {
//            throw new IllegalArgumentException("Fuzzy distance must be between 0.0 and 1.0");
//        }
//
//        ((WhereToken) whereToken).getOptions().setFuzzy(fuzzy);
//    }
//
//    /**
//     * Specifies a proximity distance for the phrase in the last search clause
//     * <p>
//     * http://lucene.apache.org/java/2_4_0/queryparsersyntax.html#Proximity%20Searches
//     * @param proximity Proximity value
//     */
//    @Override
//    public void _proximity(int proximity) {
//        List<QueryToken> tokens = getCurrentWhereTokens();
//        if (tokens.isEmpty()) {
//            throw new IllegalStateException("Proximity can only be used right after search clause");
//        }
//
//        QueryToken whereToken = tokens.get(tokens.size() - 1);
//        if (!(whereToken instanceof WhereToken)) {
//            throw new IllegalStateException("Proximity can only be used right after search clause");
//        }
//
//        if (((WhereToken) whereToken).getWhereOperator() != WhereOperator.SEARCH) {
//            throw new IllegalStateException("Proximity can only be used right after search clause");
//        }
//
//        if (proximity < 1) {
//            throw new IllegalArgumentException("Proximity distance must be a positive number");
//        }
//
//        ((WhereToken) whereToken).getOptions().setProximity(proximity);
//    }
//
//    public void _orderBy(String field, String sorterName) {
//        if (StringUtils.isBlank(sorterName)) {
//            throw new IllegalArgumentException("SorterName cannot be null or whitespace.");
//        }
//
//        assertNoRawQuery();
//        String f = ensureValidFieldName(field, false);
//        orderByTokens.add(OrderByToken.createAscending(f, sorterName));
//    }
//
//    /**
//     * Order the results by the specified fields
//     * The fields are the names of the fields to sort, defaulting to sorting by ascending.
//     * You can prefix a field name with '-' to indicate sorting by descending or '+' to sort by ascending
//     * @param field field to use in order
//     */
//    public void _orderBy(String field) {
//        _orderBy(field, OrderingType.STRING);
//    }
//
//    /**
//     * Order the results by the specified fields
//     * The fields are the names of the fields to sort, defaulting to sorting by ascending.
//     * You can prefix a field name with '-' to indicate sorting by descending or '+' to sort by ascending
//     * @param field field to use in order
//     * @param ordering Ordering type
//     */
//    public void _orderBy(String field, OrderingType ordering) {
//        assertNoRawQuery();
//        String f = ensureValidFieldName(field, false);
//        orderByTokens.add(OrderByToken.createAscending(f, ordering));
//    }
//
//    public void _orderByDescending(String field, String sorterName) {
//        if (StringUtils.isBlank(sorterName)) {
//            throw new IllegalArgumentException("SorterName cannot be null or whitespace.");
//        }
//
//        assertNoRawQuery();
//        String f = ensureValidFieldName(field, false);
//        orderByTokens.add(OrderByToken.createDescending(f, sorterName));
//    }
//
//    /**
//     * Order the results by the specified fields
//     * The fields are the names of the fields to sort, defaulting to sorting by descending.
//     * You can prefix a field name with '-' to indicate sorting by descending or '+' to sort by ascending
//     * @param field Field to use
//     */
//    public void _orderByDescending(String field) {
//        _orderByDescending(field, OrderingType.STRING);
//    }
//
//    /**
//     * Order the results by the specified fields
//     * The fields are the names of the fields to sort, defaulting to sorting by descending.
//     * You can prefix a field name with '-' to indicate sorting by descending or '+' to sort by ascending
//     * @param field Field to use
//     * @param ordering Ordering type
//     */
//    public void _orderByDescending(String field, OrderingType ordering) {
//        assertNoRawQuery();
//        String f = ensureValidFieldName(field, false);
//        orderByTokens.add(OrderByToken.createDescending(f, ordering));
//    }
//
//    public void _orderByScore() {
//        assertNoRawQuery();
//
//        orderByTokens.add(OrderByToken.scoreAscending);
//    }
//
//    public void _orderByScoreDescending() {
//        assertNoRawQuery();
//        orderByTokens.add(OrderByToken.scoreDescending);
//    }

    /**
     * Provide statistics about the query, such as total count of matching records
     * @param QueryStatistics $stats Output parameter for query statistics
     */
    public function _statistics(QueryStatistics &$stats): void
    {
        $stats = $queryStats;
    }

    /**
     * Called externally to raise the after query executed callback
     * @param QueryResult $result Query result
     */
    public function invokeAfterQueryExecuted(QueryResult $result): void
    {
        EventHelper::invoke($this->afterQueryExecutedCallback, $result);
    }

    public function invokeBeforeQueryExecuted(IndexQuery $query): void
    {
        EventHelper::invoke($this->beforeQueryExecutedCallback, $query);
    }

//    public function invokeAfterStreamExecuted(ObjectNode $result): void {
//        EventHelper::invoke($this->afterStreamExecutedCallback, $result);
//    }

    /**
     * Generates the index query.
     * @param string $query Query
     *
     * @return IndexQuery Index query
     */
    protected function generateIndexQuery(string $query): IndexQuery
    {
        $indexQuery = new IndexQuery();
        $indexQuery->setQuery($query);
        $indexQuery->setStart($this->start);
        $indexQuery->setWaitForNonStaleResults($this->theWaitForNonStaleResults);
        $indexQuery->setWaitForNonStaleResultsTimeout($this->timeout);
        $indexQuery->setQueryParameters($this->queryParameters);
        $indexQuery->setDisableCaching($this->disableCaching);
        // @todo: implement this
//        $indexQuery->setProjectionBehavior($this->projectionBehavior);

        if ($this->pageSize != null) {
            $indexQuery->setPageSize($this->pageSize);
        }
        return $indexQuery;
    }

    /**
     * Perform a search for documents which fields that match the searchTerms.
     * If there is more than a single term, each of them will be checked independently.
     * @param string $fieldName Field name
     * @param string $searchTerms Search terms
     * @param ?SearchOperator $operator Search operator
     */
    public function _search(string $fieldName, string $searchTerms, ?SearchOperator $operator = null): void
    {
        if ($operator == null) {
            $operator = SearchOperator::or();
        }

        $tokens = $this->getCurrentWhereTokens();
        $this->appendOperatorIfNeeded($tokens);

        $fieldName = $this->ensureValidFieldName($fieldName, false);
        $this->negateIfNeeded($tokens, $fieldName);

        $whereToken = WhereToken::create(WhereOperator::search(), $fieldName, $this->addQueryParameter($searchTerms), new WhereOptions($operator));
        $tokens->append($whereToken);
    }

    public function toString(bool $compatibilityMode = false): string
    {
        if ($this->queryRaw != null) {
            return $this->queryRaw;
        }

        if ($this->currentClauseDepth != 0) {
            throw new IllegalStateException("A clause was not closed correctly within this query, current clause depth = " . $this->currentClauseDepth);
        }

        $queryText = new StringBuilder();

        $this->buildDeclare($queryText);
        if ($this->graphRawQuery != null) {
            $this->buildWith($queryText);
            $this->buildGraphQuery($queryText);
        } else {
            $this->buildFrom($queryText);
        }
        $this->buildGroupBy($queryText);
        $this->buildWhere($queryText);
        $this->buildOrderBy($queryText);

        $this->buildLoad($queryText);
        $this->buildSelect($queryText);
        $this->buildInclude($queryText);

        if (!$compatibilityMode) {
            $this->buildPagination($queryText);
        }

        return $queryText->__toString();
    }

    private function buildGraphQuery(StringBuilder $queryText): void
    {
        $this->graphRawQuery->writeTo($queryText);
    }

    private function buildWith(StringBuilder $queryText): void
    {
        foreach ($this->withTokens as $with) {
            $with->writeTo($queryText);
            $queryText->append(PHP_EOL);
        }
    }

    private function buildPagination(StringBuilder $queryText): void
    {
        if ($this->start > 0 || $this->pageSize != null) {
            $queryText
                    ->append(" limit $")
                    ->append($this->addQueryParameter($this->start))
                    ->append(", $")
                    ->append($this->addQueryParameter($this->pageSize));
        }
    }

    private function buildInclude(StringBuilder $queryText): void
    {
        if ($this->documentIncludes->isEmpty() &&
                $this->highlightingTokens->isEmpty() &&
                $this->explanationToken == null &&
                $this->queryTimings == null &&
                $this->counterIncludesTokens == null &&
                $this->timeSeriesIncludesTokens == null &&
                $this->compareExchangeValueIncludesTokens == null) {
            return;
        }

        $queryText->append(" include ");
        $firstRef = true;
        foreach ($this->documentIncludes as $include) {
            if (!$firstRef) {
                $queryText->append(",");
            }
            $firstRef = false;

            $escapedIncludeRef = null;

            if (IncludesUtil::requiresQuotes($include, $escapedIncludeRef)) {
                $queryText
                        ->append("'")
                        ->append($escapedIncludeRef)
                        ->append("'");
            } else {
                $queryText->append($include);
            }
        }

        $this->writeIncludeTokens($this->counterIncludesTokens, $firstRef, $queryText);
        $this->writeIncludeTokens($this->timeSeriesIncludesTokens, $firstRef, $queryText);
        $this->writeIncludeTokens($this->compareExchangeValueIncludesTokens, $firstRef, $queryText);
        $this->writeIncludeTokens($this->highlightingTokens, $firstRef, $queryText);

        if ($this->explanationToken != null) {
            if (!$firstRef) {
                $queryText->append(",");
            }

            $firstRef = false;
            $this->explanationToken->writeTo($queryText);
        }

        if ($this->queryTimings != null) {
            if (!$firstRef) {
                $queryText->append(",");
            }
            $firstRef = false;

            TimingsToken::instance()->writeTo($queryText);
        }
    }

    protected function writeIncludeTokens(?QueryTokenList $tokens, bool &$firstRef, StringBuilder $queryText): void
    {
        if ($tokens == null) {
            return;
        }

        foreach($tokens as $token) {
            if (!$firstRef) {
                $queryText->append(",");
            }
            $firstRef = false;

            $token->writeTo($queryText);
        }
    }

//    @Override
//    public void _intersect() {
//        List<QueryToken> tokens = getCurrentWhereTokens();
//        if (tokens.size() > 0) {
//            QueryToken last = tokens.get(tokens.size() - 1);
//            if (last instanceof WhereToken || last instanceof CloseSubclauseToken) {
//                isIntersect = true;
//
//                tokens.add(IntersectMarkerToken.INSTANCE);
//                return;
//            }
//        }
//
//        throw new IllegalStateException("Cannot add INTERSECT at this point.");
//    }

    public function _whereExists(string $fieldName): void {
        $fieldName = $this->ensureValidFieldName($fieldName, false);

        $tokens = $this->getCurrentWhereTokens();
        $this->appendOperatorIfNeeded($tokens);
        $this->negateIfNeeded($tokens, null);

        $tokens->append(WhereToken::create(WhereOperator::exists(), $fieldName, null));
    }
//
//    @Override
//    public void _containsAny(String fieldName, Collection< ? > values) {
//        fieldName = ensureValidFieldName(fieldName, false);
//
//        List<QueryToken> tokens = getCurrentWhereTokens();
//        appendOperatorIfNeeded(tokens);
//        negateIfNeeded(tokens, fieldName);
//
//        Collection< ? > array = transformCollection(fieldName, unpackCollection(values));
//        WhereToken whereToken = WhereToken.create(WhereOperator.IN, fieldName, addQueryParameter(array), new WhereToken.WhereOptions(false));
//        tokens.add(whereToken);
//    }
//
//    @Override
//    public void _containsAll(String fieldName, Collection< ? > values) {
//        fieldName = ensureValidFieldName(fieldName, false);
//
//        List<QueryToken> tokens = getCurrentWhereTokens();
//        appendOperatorIfNeeded(tokens);
//        negateIfNeeded(tokens, fieldName);
//
//        Collection< ? > array = transformCollection(fieldName, unpackCollection(values));
//
//        if (array.isEmpty()) {
//            tokens.add(TrueToken.INSTANCE);
//            return;
//        }
//
//        WhereToken whereToken = WhereToken.create(WhereOperator.ALL_IN, fieldName, addQueryParameter(array));
//        tokens.add(whereToken);
//    }
//
//    @Override
//    public void _addRootType(Class clazz) {
//        rootTypes.add(clazz);
//    }
//
//    //TBD expr public string GetMemberQueryPathForOrderBy(Expression expression)
//    //TBD expr public string GetMemberQueryPath(Expression expression)
//
//
//    @Override
//    public void _distinct() {
//        if (isDistinct()) {
//            throw new IllegalStateException("The is already a distinct query");
//        }
//
//        if (selectTokens.isEmpty()) {
//            selectTokens.add(DistinctToken.INSTANCE);
//        } else {
//            selectTokens.add(0, DistinctToken.INSTANCE);
//        }
//    }

    private function updateStatsHighlightingsAndExplanations(QueryResult $queryResult): void
    {
//        $this->queryStats->updateQueryStats($queryResult);
//        $this->queryHighlightings->update($queryResult);
//        if ($this->explanations != null) {
//            $this->explanations->update($queryResult);
//        }
//        if ($this->queryTimings != null) {
//            $this->queryTimings->update($queryResult);
//        }
    }

    private function buildSelect(StringBuilder $writer): void
    {
        if ($this->selectTokens->isEmpty()) {
            return;
        }

        $writer->append(" select ");
        if ($this->selectTokens->count() == 1 && ($this->selectTokens->offsetGet(0) instanceof DistinctToken)) {
            $this->selectTokens->offsetGet(0)->writeTo($writer);
            $writer->append(" *");

            return;
        }

        $prevToken = null;
        foreach ($this->selectTokens as $currentToken) {
            if (($prevToken != null) && !is_a($prevToken,  DistinctToken::class)) {
                $writer->append(",");
            }

            DocumentQueryHelper::addSpaceIfNeeded($prevToken, $currentToken, $writer);
            $currentToken->writeTo($writer);
        }
    }

    private function buildFrom(StringBuilder $writer): void
    {
        $this->fromToken->writeTo($writer);
    }

    private function buildDeclare(StringBuilder $writer): void
    {
        if ($this->declareTokens == null) {
            return;
        }

        foreach ($this->declareTokens as $token) {
            $token->writeTo($writer);
        }
    }

    private function buildLoad(StringBuilder $writer): void
    {
        if (($this->loadTokens == null) || ($this->loadTokens->isEmpty())) {
            return;
        }

        $writer->append(" load ");

        foreach ($this->loadTokens as $key => $loadToken) {
            if ($key !== array_key_first($this->loadTokens->getArrayCopy())) {
                $writer->append(", ");
            }
            $loadToken->writeTo($writer);
        }
    }

    private function buildWhere(StringBuilder $writer): void
    {
        if ($this->whereTokens->isEmpty()) {
            return;
        }

        $writer->append(" where ");

        if ($this->isIntersect) {
            $writer->append("intersect(");
        }

        $prevToken = null;
        foreach ($this->whereTokens as $currentToken) {
            DocumentQueryHelper::addSpaceIfNeeded($prevToken, $currentToken, $writer);
            $currentToken->writeTo($writer);
            $prevToken = $currentToken;
        }

        if ($this->isIntersect) {
            $writer->append(") ");
        }
    }

    private function buildGroupBy(StringBuilder $writer): void
    {
        if ($this->groupByTokens->isEmpty()) {
            return;
        }

        $writer->append(" group by ");

        $isFirst = true;

        foreach ($this->groupByTokens as $token) {
            if (!$isFirst) {
                $writer->append(", ");
            }
            $token->writeTo($writer);
            $isFirst = false;
        }
    }

    private function buildOrderBy(StringBuilder $writer): void
    {
        if ($this->orderByTokens->isEmpty()) {
            return;
        }

        $writer->append(" order by ");

        $isFirst = true;

        foreach ($this->orderByTokens as $token) {
            if (!$isFirst) {
                $writer->append(", ");
            }

            $token->writeTo($writer);
            $isFirst = false;
        }
    }

    private function appendOperatorIfNeeded(QueryTokenList $tokens): void
    {
        $this->assertNoRawQuery();

        if ($tokens->isEmpty()) {
            return;
        }

        $lastToken = $tokens->last();
        if (!($lastToken instanceof WhereToken) && !($lastToken instanceof CloseSubclauseToken)) {
            return;
        }

        /** @var ?WhereToken $lastWhere */
        $lastWhere = null;

        foreach (array_reverse($tokens->getArrayCopy()) as $token) {
            if ($token instanceof WhereToken) {
                $lastWhere = $token;
                break;
            }
        }

        /** QueryOperatorToken */
        $token = $this->defaultOperator->isAnd() ? QueryOperatorToken::and() : QueryOperatorToken::or() ;

        if ($lastWhere != null && $lastWhere->getOptions()->getSearchOperator() != null) {
            $token = QueryOperatorToken::or(); // default to OR operator after search if AND was not specified explicitly
        }

        $tokens->append($token);
    }

//    private Collection< ? > transformCollection(String fieldName, Collection< ? > values) {
//        List<Object> result = new ArrayList<>();
//        for (Object value : values) {
//            if (value instanceof Collection) {
//                result.addAll(transformCollection(fieldName, (Collection< ? >) value));
//            } else {
//                WhereParams nestedWhereParams = new WhereParams();
//                nestedWhereParams.setAllowWildcards(true);
//                nestedWhereParams.setFieldName(fieldName);
//                nestedWhereParams.setValue(value);
//
//                result.add(transformValue(nestedWhereParams));
//            }
//        }
//        return result;
//    }

    private function negateIfNeeded(QueryTokenList $tokens, ?string $fieldName = null): void
    {
        if (!$this->negate) {
            return;
        }

        $this->negate = false;

        if ($tokens->isEmpty() || $tokens->last() instanceof OpenSubclauseToken) {
            if ($fieldName != null) {
                $this->_whereExists($fieldName);
            } else {
                $this->_whereTrue();
            }
            $this->_andAlso();
        }

        $tokens->append(NegateToken::instance());
    }

//    private static Collection< ? > unpackCollection(Collection< ? > items) {
//        List<Object> results = new ArrayList<>();
//
//        for (Object item : items) {
//            if (item instanceof Collection) {
//                results.addAll(unpackCollection((Collection< ? >) item));
//            } else {
//                results.add(item);
//            }
//        }
//
//        return results;
//    }

    private function ensureValidFieldName(string $fieldName, bool $isNestedPath): string
    {
        if ($this->theSession == null || $this->theSession->getConventions() == null || $isNestedPath || $this->isGroupBy) {
            return QueryFieldUtil::escapeIfNecessary($fieldName, $isNestedPath);
        }

        foreach ($this->rootTypes as $rootType) {
            $identityProperty = $this->theSession->getConventions()->getIdentityProperty($rootType);
            if ($identityProperty != null && strcmp($identityProperty, $fieldName) == 0) {
                return DocumentsIndexingFields::DOCUMENT_ID_FIELD_NAME;
            }
        }

        return QueryFieldUtil::escapeIfNecessary($fieldName);
    }

    private function transformValue(WhereParams $whereParams, bool $forRange = false): ?string
    {
        if ($whereParams->getValue() === null) {
            return null;
        }

        if ($whereParams->getValue() === "") {
            return "";
        }

//        Reference<Object> objValueReference = new Reference<>();
//        if (_conventions.tryConvertValueToObjectForQuery(whereParams.getFieldName(), whereParams.getValue(), forRange, objValueReference)) {
//            return objValueReference.value;
//        }
//
//        Class< ? > clazz = whereParams.getValue().getClass();
//        if (Date.class.equals(clazz)) {
//            return whereParams.getValue();
//        }
//
//        if (String.class.equals(clazz)) {
//            return whereParams.getValue();
//        }
//
//        if (Integer.class.equals(clazz)) {
//            return whereParams.getValue();
//        }
//
//        if (Long.class.equals(clazz)) {
//            return whereParams.getValue();
//        }
//
//        if (Float.class.equals(clazz)) {
//            return whereParams.getValue();
//        }
//
//        if (Double.class.equals(clazz)) {
//            return whereParams.getValue();
//        }
//
//        if (Duration.class.equals(clazz)) {
//            return ((Duration) whereParams.getValue()).toNanos() / 100;
//        }

        if (is_bool($whereParams->getValue())) {
            return $whereParams->getValue() ? 'true' : 'false';
        }

//        if (clazz.isEnum()) {
//            return whereParams.getValue();
//        }

        return $whereParams->getValue();
    }

    private function addQueryParameter($value): string
    {
        $parameterName = $this->getParameterPrefix() . $this->queryParameters->count();
        $this->queryParameters->offsetSet($parameterName, $value);
        return $parameterName;
    }

    private function getCurrentWhereTokens(): QueryTokenList
    {
        if (!$this->isInMoreLikeThis) {
            return $this->whereTokens;
        }

        if (empty($this->whereTokens)) {
            throw new IllegalStateException("Cannot get MoreLikeThisToken because there are no where token specified.");
        }

        /** @var QueryToken $lastToken */
        $lastToken = $this->whereTokens->last();

        if ($lastToken instanceof MoreLikeThisToken) {
            /** @var MoreLikeThisToken $moreLikeThisToken */
            $moreLikeThisToken = $lastToken;
            return $moreLikeThisToken->whereTokens;
        } else {
            throw new IllegalStateException("Last token is not MoreLikeThisToken");
        }
    }

//    protected void updateFieldsToFetchToken(FieldsToFetchToken fieldsToFetch) {
//        this.fieldsToFetchToken = fieldsToFetch;
//
//        if (selectTokens.isEmpty()) {
//            selectTokens.add(fieldsToFetch);
//        } else {
//            Optional<QueryToken> fetchToken = selectTokens.stream()
//                    .filter(x -> x instanceof FieldsToFetchToken)
//                    .findFirst();
//
//            if (fetchToken.isPresent()) {
//                int idx = selectTokens.indexOf(fetchToken.get());
//                selectTokens.set(idx, fieldsToFetch);
//            } else {
//                selectTokens.add(fieldsToFetch);
//            }
//        }
//    }
//
//    public void addFromAliasToWhereTokens(String fromAlias) {
//        if (StringUtils.isEmpty(fromAlias)) {
//            throw new IllegalArgumentException("Alias cannot be null or empty");
//        }
//
//        List<QueryToken> tokens = getCurrentWhereTokens();
//
//        for (QueryToken token : tokens) {
//            if (token instanceof WhereToken) {
//                ((WhereToken) token).addAlias(fromAlias);
//            }
//        }
//    }
//
//    public String addAliasToIncludesTokens(String fromAlias) {
//        if (_includesAlias == null) {
//            return fromAlias;
//        }
//
//        if (fromAlias == null) {
//            fromAlias = _includesAlias;
//            addFromAliasToWhereTokens(fromAlias);
//        }
//
//        if (counterIncludesTokens != null) {
//            for (CounterIncludesToken counterIncludesToken : counterIncludesTokens) {
//                counterIncludesToken.addAliasToPath(fromAlias);
//            }
//        }
//
//        if (timeSeriesIncludesTokens != null) {
//            for (TimeSeriesIncludesToken token : timeSeriesIncludesTokens) {
//                token.addAliasToPath(fromAlias);
//            }
//        }
//
//        return fromAlias;
//    }
//
//    @SuppressWarnings("unused")
//    protected static <T> void getSourceAliasIfExists(Class< T > clazz, QueryData queryData, String[] fields, Reference<String> sourceAlias) {
//        sourceAlias.value = null;
//
//        if (fields.length != 1 || fields[0] == null) {
//            return;
//        }
//
//        int indexOf = fields[0].indexOf(".");
//        if (indexOf == -1) {
//            return;
//        }
//
//        String possibleAlias = fields[0].substring(0, indexOf);
//        if (queryData.getFromAlias() != null && queryData.getFromAlias().equals(possibleAlias)) {
//            sourceAlias.value = possibleAlias;
//            return;
//        }
//
//        if (queryData.getLoadTokens() == null || queryData.getLoadTokens().size() == 0) {
//            return;
//        }
//
//        if (queryData.getLoadTokens().stream().noneMatch(x -> x.alias.equals(possibleAlias))) {
//            return;
//        }
//
//        sourceAlias.value = possibleAlias;
//    }
//
//    protected QueryData createTimeSeriesQueryData(Consumer<ITimeSeriesQueryBuilder> timeSeriesQuery) {
//        TimeSeriesQueryBuilder builder = new TimeSeriesQueryBuilder();
//        timeSeriesQuery.accept(builder);
//
//        String[] fields = new String[] { Constants.TimeSeries.SELECT_FIELD_NAME + "(" + builder.getQueryText() + ")" };
//        String[] projections = new String[] { Constants.TimeSeries.QUERY_FUNCTION } ;
//        return new QueryData(fields, projections);
//    }

    protected ClosureArray $beforeQueryExecutedCallback;

    protected ClosureArray $afterQueryExecutedCallback;

    protected ClosureArray $afterStreamExecutedCallback;

    protected ?QueryOperation $queryOperation = null;

    public function getQueryOperation(): ?QueryOperation
    {
        return $this->queryOperation;
    }

//    public void _addBeforeQueryExecutedListener(Consumer<IndexQuery> action) {
//        beforeQueryExecutedCallback.add(action);
//    }
//
//    public void _removeBeforeQueryExecutedListener(Consumer<IndexQuery> action) {
//        beforeQueryExecutedCallback.remove(action);
//    }
//
//    public void _addAfterQueryExecutedListener(Consumer<QueryResult> action) {
//        afterQueryExecutedCallback.add(action);
//    }
//
//    public void _removeAfterQueryExecutedListener(Consumer<QueryResult> action) {
//        afterQueryExecutedCallback.remove(action);
//    }
//
//    public void _addAfterStreamExecutedListener(Consumer<ObjectNode> action) {
//        afterStreamExecutedCallback.add(action);
//    }
//
//    public void _removeAfterStreamExecutedListener(Consumer<ObjectNode> action) {
//        afterStreamExecutedCallback.remove(action);
//    }

    public function _noTracking(): void
    {
        $this->disableEntitiesTracking = true;
    }

    public function _noCaching(): void
    {
        $this->disableCaching = true;
    }

    protected ?QueryTimings $queryTimings = null;

    public function _includeTimings(QueryTimings &$timingsReference): void {
        if ($this->queryTimings != null) {
            $this->timingsReference = $queryTimings;
            return;
        }

        $queryTimings = $timingsReference = new QueryTimings();
    }

    protected HighlightingTokenArray $highlightingTokens;
//
//    protected QueryHighlightings queryHighlightings = new QueryHighlightings();
//
//    public void _highlight(String fieldName, int fragmentLength, int fragmentCount, HighlightingOptions options, Reference<Highlightings> highlightingsReference) {
//        highlightingsReference.value = queryHighlightings.add(fieldName);
//
//        String optionsParameterName = options != null ? addQueryParameter(JsonExtensions.getDefaultMapper().valueToTree(options)) : null;
//        highlightingTokens.add(HighlightingToken.create(fieldName, fragmentLength, fragmentCount, optionsParameterName));
//    }
//
//    protected void _withinRadiusOf(String fieldName, double radius, double latitude, double longitude, SpatialUnits radiusUnits, double distErrorPercent) {
//        fieldName = ensureValidFieldName(fieldName, false);
//
//        List<QueryToken> tokens = getCurrentWhereTokens();
//        appendOperatorIfNeeded(tokens);
//        negateIfNeeded(tokens, fieldName);
//
//        WhereToken whereToken = WhereToken.create(WhereOperator.SPATIAL_WITHIN, fieldName, null, new WhereToken.WhereOptions(ShapeToken.circle(addQueryParameter(radius), addQueryParameter(latitude), addQueryParameter(longitude), radiusUnits), distErrorPercent));
//        tokens.add(whereToken);
//    }
//
//    protected void _spatial(String fieldName, String shapeWkt, SpatialRelation relation, SpatialUnits units, double distErrorPercent) {
//        fieldName = ensureValidFieldName(fieldName, false);
//
//        List<QueryToken> tokens = getCurrentWhereTokens();
//        appendOperatorIfNeeded(tokens);
//        negateIfNeeded(tokens, fieldName);
//
//        ShapeToken wktToken = ShapeToken.wkt(addQueryParameter(shapeWkt), units);
//
//        WhereOperator whereOperator;
//        switch (relation) {
//            case WITHIN:
//                whereOperator = WhereOperator.SPATIAL_WITHIN;
//                break;
//            case CONTAINS:
//                whereOperator = WhereOperator.SPATIAL_CONTAINS;
//                break;
//            case DISJOINT:
//                whereOperator = WhereOperator.SPATIAL_DISJOINT;
//                break;
//            case INTERSECTS:
//                whereOperator = WhereOperator.SPATIAL_INTERSECTS;
//                break;
//            default:
//                throw new IllegalArgumentException();
//        }
//
//        tokens.add(WhereToken.create(whereOperator, fieldName, null, new WhereToken.WhereOptions(wktToken, distErrorPercent)));
//    }
//
//    @Override
//    public void _spatial(DynamicSpatialField dynamicField, SpatialCriteria criteria) {
//        assertIsDynamicQuery(dynamicField, "spatial");
//
//        List<QueryToken> tokens = getCurrentWhereTokens();
//        appendOperatorIfNeeded(tokens);
//        negateIfNeeded(tokens, null);
//
//        tokens.add(criteria.toQueryToken(dynamicField.toField(this::ensureValidFieldName), this::addQueryParameter));
//    }
//
//    @Override
//    public void _spatial(String fieldName, SpatialCriteria criteria) {
//        fieldName = ensureValidFieldName(fieldName, false);
//
//        List<QueryToken> tokens = getCurrentWhereTokens();
//        appendOperatorIfNeeded(tokens);
//        negateIfNeeded(tokens, fieldName);
//
//        tokens.add(criteria.toQueryToken(fieldName, this::addQueryParameter));
//    }
//
//    @Override
//    public void _orderByDistance(DynamicSpatialField field, double latitude, double longitude) {
//        if (field == null) {
//            throw new IllegalArgumentException("Field cannot be null");
//        }
//        assertIsDynamicQuery(field, "orderByDistance");
//
//        _orderByDistance("'" + field.toField(this::ensureValidFieldName) + "'", latitude, longitude, field.getRoundFactor());
//    }
//
//    @Override
//    public void _orderByDistance(String fieldName, double latitude, double longitude) {
//        _orderByDistance(fieldName, latitude, longitude, 0);
//    }
//
//    @Override
//    public void _orderByDistance(String fieldName, double latitude, double longitude, double roundFactor) {
//        String roundFactorParameterName = roundFactor == 0 ? null : addQueryParameter(roundFactor);
//        orderByTokens.add(OrderByToken.createDistanceAscending(fieldName, addQueryParameter(latitude), addQueryParameter(longitude), roundFactorParameterName));
//    }
//
//    @Override
//    public void _orderByDistance(DynamicSpatialField field, String shapeWkt) {
//        if (field == null) {
//            throw new IllegalArgumentException("Field cannot be null");
//        }
//        assertIsDynamicQuery(field, "orderByDistance");
//
//        _orderByDistance("'" + field.toField(this::ensureValidFieldName) + "'", shapeWkt, field.getRoundFactor());
//    }
//
//    @Override
//    public void _orderByDistance(String fieldName, String shapeWkt) {
//        _orderByDistance(fieldName, shapeWkt, 0);
//    }
//
//    @Override
//    public void _orderByDistance(String fieldName, String shapeWkt, double roundFactor) {
//        String roundFactorParameterName = roundFactor == 0 ? null : addQueryParameter(roundFactor);
//        orderByTokens.add(OrderByToken.createDistanceAscending(fieldName, addQueryParameter(shapeWkt), roundFactorParameterName));
//    }
//
//    @Override
//    public void _orderByDistanceDescending(DynamicSpatialField field, double latitude, double longitude) {
//        if (field == null) {
//            throw new IllegalArgumentException("Field cannot be null");
//        }
//        assertIsDynamicQuery(field, "orderByDistanceDescending");
//        _orderByDistanceDescending("'" + field.toField(this::ensureValidFieldName) + "'", latitude, longitude, field.getRoundFactor());
//    }
//
//    @Override
//    public void _orderByDistanceDescending(String fieldName, double latitude, double longitude) {
//        _orderByDistanceDescending(fieldName, latitude, longitude, 0);
//    }
//
//    @Override
//    public void _orderByDistanceDescending(String fieldName, double latitude, double longitude, double roundFactor) {
//        String roundFactorParameterName = roundFactor == 0 ? null : addQueryParameter(roundFactor);
//        orderByTokens.add(OrderByToken.createDistanceDescending(fieldName, addQueryParameter(latitude), addQueryParameter(longitude), roundFactorParameterName));
//    }
//
//    @Override
//    public void _orderByDistanceDescending(DynamicSpatialField field, String shapeWkt) {
//        if (field == null) {
//            throw new IllegalArgumentException("Field cannot be null");
//        }
//        assertIsDynamicQuery(field, "orderByDistanceDescending");
//        _orderByDistanceDescending("'" + field.toField(this::ensureValidFieldName) + "'", shapeWkt, field.getRoundFactor());
//    }
//
//    @Override
//    public void _orderByDistanceDescending(String fieldName, String shapeWkt) {
//        _orderByDistanceDescending(fieldName, shapeWkt, 0);
//    }
//
//    @Override
//    public void _orderByDistanceDescending(String fieldName, String shapeWkt, double roundFactor) {
//        String factorParamName = roundFactor == 0 ? null : addQueryParameter(roundFactor);
//        orderByTokens.add(OrderByToken.createDistanceDescending(fieldName, addQueryParameter(shapeWkt), factorParamName));
//    }
//
//    private void assertIsDynamicQuery(DynamicSpatialField dynamicField, String methodName) {
//        if (!fromToken.isDynamic()) {
//            throw new IllegalStateException("Cannot execute query method '" + methodName + "'. Field '"
//                    + dynamicField.toField(this::ensureValidFieldName) + "' cannot be used when static index '" + fromToken.getIndexName()
//                    + "' is queried. Dynamic spatial fields can only be used with dynamic queries, " +
//                    "for static index queries please use valid spatial fields defined in index definition.");
//        }
//    }

    protected function initSync(): void
    {
        if ($this->queryOperation != null) {
            return;
        }

        $this->queryOperation = $this->initializeQueryOperation();
        $this->executeActualQuery();
    }

    private function executeActualQuery(): void
    {
        /** @var ?CleanCloseable $context */
        $context = $this->queryOperation->enterQueryContext();
        try {
            /** QueryCommand */
            $command = $this->queryOperation->createRequest();
            $this->theSession->getRequestExecutor()->execute($command, $this->theSession->getSessionInfo());
            /** @var QueryResult $queryResult */
            $queryResult = $command->getResult();
            $this->queryOperation->setResult($queryResult);
        } finally {
            if($context) {
                $context->close();
            }
        }

        $this->invokeAfterQueryExecuted($this->queryOperation->getCurrentQueryResults());
    }

//    @Override
//    public Iterator<T> iterator() {
//        return executeQueryOperation(null).iterator();
//    }

    public function toList(): array
    {
        return $this->executeQueryOperation(null);
    }

    public function toArray(): array
    {
        return $this->executeQueryOperationAsArray(null);
    }

//    public QueryResult getQueryResult() {
//        initSync();
//
//        return queryOperation.getCurrentQueryResults().createSnapshot();
//    }
//
//    public T first() {
//        Collection<T> result = executeQueryOperation(1);
//        if (result.isEmpty()) {
//            throw new IllegalStateException("Expected at least one result");
//        }
//        return result.stream().findFirst().get();
//    }
//
//    public T firstOrDefault() {
//        Collection<T> result = executeQueryOperation(1);
//        return result.stream().findFirst().orElseGet(() -> Defaults.defaultValue(clazz));
//    }
//
//    public T single() {
//        Collection<T> result = executeQueryOperation(2);
//        if (result.size() != 1) {
//            throw new IllegalStateException("Expected single result, got: " + result.size());
//        }
//        return result.stream().findFirst().get();
//    }
//
//    public T singleOrDefault() {
//        Collection<T> result = executeQueryOperation(2);
//        if (result.size() > 1) {
//            throw new IllegalStateException("Expected single result, got: " + result.size());
//        }
//        if (result.isEmpty()) {
//            return Defaults.defaultValue(clazz);
//        }
//        return result.stream().findFirst().get();
//    }
//
//    public int count() {
//        _take(0);
//        QueryResult queryResult = getQueryResult();
//        return queryResult.getTotalResults();
//    }
//
//    public long longCount() {
//        _take(0);
//        QueryResult queryResult = getQueryResult();
//        return queryResult.getLongTotalResults();
//    }
//
//    public boolean any() {
//        if (isDistinct()) {
//            // for distinct it is cheaper to do count 1
//            return executeQueryOperation(1).iterator().hasNext();
//        }
//
//        _take(0);
//        QueryResult queryResult = getQueryResult();
//        return queryResult.getTotalResults() > 0;
//    }

    private function executeQueryOperation(?int $take = null): array
    {
        $this->executeQueryOperationInternal($take);

        return $this->queryOperation->complete($this->className);
    }

    private function executeQueryOperationAsArray(?int $take): array
    {
        $this->executeQueryOperationInternal($take);

        return [];//$this->queryOperation->completeAsArray($this->className);
    }

    private function executeQueryOperationInternal(?int $take): void {
        if ($take != null && ($this->pageSize == null || $this->pageSize > $take)) {
            $this->take($take);
        }

        $this->initSync();
    }

//    public void _aggregateBy(FacetBase facet) {
//        for (QueryToken token : selectTokens) {
//            if (token instanceof FacetToken) {
//                continue;
//            }
//
//            throw new IllegalStateException("Aggregation query can select only facets while it got " + token.getClass().getSimpleName() + " token");
//        }
//
//        selectTokens.add(FacetToken.create(facet, this::addQueryParameter));
//    }
//
//    public void _aggregateUsing(String facetSetupDocumentId) {
//        selectTokens.add(FacetToken.create(facetSetupDocumentId));
//    }
//
//    public Lazy<List<T>> lazily() {
//        return lazily(null);
//    }
//
//    @SuppressWarnings("unchecked")
//    public Lazy<List<T>> lazily(Consumer<List<T>> onEval) {
//        LazyQueryOperation<T> lazyQueryOperation = getLazyQueryOperation();
//
//        return ((DocumentSession)theSession).addLazyOperation((Class<List<T>>) (Class< ? >)List.class, lazyQueryOperation, onEval);
//    }
//
//    public Lazy<Integer> countLazily() {
//        if (queryOperation == null) {
//            _take(0);
//            queryOperation = initializeQueryOperation();
//        }
//
//        LazyQueryOperation<T> lazyQueryOperation = new LazyQueryOperation<>(clazz, theSession, queryOperation, afterQueryExecutedCallback);
//        return ((DocumentSession)theSession).addLazyCountOperation(lazyQueryOperation);
//    }
//
//    @Override
//    public void _suggestUsing(SuggestionBase suggestion) {
//        if (suggestion == null) {
//            throw new IllegalArgumentException("suggestion cannot be null");
//        }
//
//        assertCanSuggest(suggestion);
//
//        SuggestToken token;
//
//        if (suggestion instanceof SuggestionWithTerm) {
//            SuggestionWithTerm term = (SuggestionWithTerm) suggestion;
//            token = SuggestToken.create(term.getField(), term.getDisplayField(), addQueryParameter(term.getTerm()), getOptionsParameterName(term.getOptions()));
//        } else if (suggestion instanceof SuggestionWithTerms) {
//            SuggestionWithTerms terms = (SuggestionWithTerms) suggestion;
//            token = SuggestToken.create(terms.getField(), terms.getDisplayField(), addQueryParameter(terms.getTerms()), getOptionsParameterName(terms.getOptions()));
//        } else {
//            throw new UnsupportedOperationException("Unknown type of suggestion: " + suggestion.getClass());
//        }
//
//        selectTokens.add(token);
//    }
//
//    private String getOptionsParameterName(SuggestionOptions options) {
//        String optionsParameterName = null;
//        if (options != null && options != SuggestionOptions.defaultOptions) {
//            optionsParameterName = addQueryParameter(options);
//        }
//
//        return optionsParameterName;
//    }
//
//    private void assertCanSuggest(SuggestionBase suggestion) {
//        if (!whereTokens.isEmpty()) {
//            throw new IllegalStateException("Cannot add suggest when WHERE statements are present.");
//        }
//
//        if (!selectTokens.isEmpty()) {
//            QueryToken lastToken = selectTokens.get(selectTokens.size() - 1);
//            if (lastToken instanceof SuggestToken) {
//                SuggestToken st = (SuggestToken) lastToken;
//                if (st.getFieldName().equals(suggestion.getField())) {
//                    throw new IllegalStateException("Cannot add suggest for the same field again.");
//                }
//            } else {
//                throw new IllegalStateException("Cannot add suggest when SELECT statements are present.");
//            }
//        }
//
//        if (!orderByTokens.isEmpty()) {
//            throw new IllegalStateException("Cannot add suggest when ORDER BY statements are present.");
//        }
//    }
//
//    protected Explanations explanations;
//
    protected ?ExplanationToken $explanationToken = null;
//
//    public void _includeExplanations(ExplanationOptions options, Reference<Explanations> explanationsReference) {
//        if (explanationToken != null) {
//            throw new IllegalStateException("Duplicate IncludeExplanations method calls are forbidden.");
//        }
//
//        String optionsParameterName = options != null ? addQueryParameter(options) : null;
//        explanationToken = ExplanationToken.create(optionsParameterName);
//        this.explanations = explanationsReference.value = new Explanations();
//    }

    protected ?TimeSeriesIncludesTokenArray $timeSeriesIncludesTokens = null;

    protected ?CounterIncludesTokenArray $counterIncludesTokens = null;

    protected ?CompareExchangeValueIncludesTokenArray $compareExchangeValueIncludesTokens = null;

//    protected void _includeCounters(String alias, Map<String, Tuple<Boolean, Set<String>>> counterToIncludeByDocId) {
//        if (counterToIncludeByDocId == null || counterToIncludeByDocId.isEmpty()) {
//            return;
//        }
//
//        counterIncludesTokens = new ArrayList<>();
//        _includesAlias = alias;
//
//        for (Map.Entry<String, Tuple<Boolean, Set<String>>> kvp : counterToIncludeByDocId.entrySet()) {
//            if (kvp.getValue().first) {
//                counterIncludesTokens.add(CounterIncludesToken.all(kvp.getKey()));
//                continue;
//            }
//
//            if (kvp.getValue().second == null || kvp.getValue().second.isEmpty()) {
//                continue;
//            }
//
//            for (String name : kvp.getValue().second) {
//                counterIncludesTokens.add(CounterIncludesToken.create(kvp.getKey(), name));
//            }
//        }
//    }
//
//    private void _includeTimeSeries(String alias, Map<String, Set<AbstractTimeSeriesRange>> timeSeriesToInclude) {
//        if (timeSeriesToInclude == null || timeSeriesToInclude.isEmpty()) {
//            return;
//        }
//
//        timeSeriesIncludesTokens = new ArrayList<>();
//        if (_includesAlias == null) {
//            _includesAlias = alias;
//        }
//
//        for (Map.Entry<String, Set<AbstractTimeSeriesRange>> kvp : timeSeriesToInclude.entrySet()) {
//            for (AbstractTimeSeriesRange range : kvp.getValue()) {
//                timeSeriesIncludesTokens.add(TimeSeriesIncludesToken.create(kvp.getKey(), range));
//            }
//        }
//    }

    public function getParameterPrefix(): string
    {
        return $this->parameterPrefix;
    }

    public function setParameterPrefix(string $parameterPrefix): void {
        $this->$parameterPrefix = $parameterPrefix;
    }
}
