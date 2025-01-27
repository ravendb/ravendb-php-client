<?php

namespace RavenDB\Documents\Session;

use Closure;
use DateTime;
use RavenDB\Constants\DocumentsIndexingFields;
use RavenDB\Constants\PhpClient;
use RavenDB\Constants\TimeSeries;
use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Indexes\Spatial\SpatialRelation;
use RavenDB\Documents\Indexes\Spatial\SpatialUnits;
use RavenDB\Documents\Lazy;
use RavenDB\Documents\Operations\TimeSeries\AbstractTimeSeriesRange;
use RavenDB\Documents\Queries\Explanation\ExplanationOptions;
use RavenDB\Documents\Queries\Explanation\Explanations;
use RavenDB\Documents\Queries\Facets\FacetBase;
use RavenDB\Documents\Queries\GroupBy;
use RavenDB\Documents\Queries\Highlighting\HighlightingOptions;
use RavenDB\Documents\Queries\Highlighting\Highlightings;
use RavenDB\Documents\Queries\Highlighting\QueryHighlightings;
use RavenDB\Documents\Queries\IndexQuery;
use RavenDB\Documents\Queries\MoreLikeThis\MoreLikeThisScope;
use RavenDB\Documents\Queries\ProjectionBehavior;
use RavenDB\Documents\Queries\QueryData;
use RavenDB\Documents\Queries\QueryFieldUtil;
use RavenDB\Documents\Queries\QueryOperator;
use RavenDB\Documents\Queries\QueryResult;
use RavenDB\Documents\Queries\SearchOperator;
use RavenDB\Documents\Queries\Spatial\DynamicSpatialField;
use RavenDB\Documents\Queries\Spatial\SpatialCriteria;
use RavenDB\Documents\Queries\Suggestions\SuggestionBase;
use RavenDB\Documents\Queries\Suggestions\SuggestionOptions;
use RavenDB\Documents\Queries\Suggestions\SuggestionWithTerm;
use RavenDB\Documents\Queries\Suggestions\SuggestionWithTerms;
use RavenDB\Documents\Queries\TimeSeries\TimeSeriesQueryBuilder;
use RavenDB\Documents\Queries\Timings\QueryTimings;
use RavenDB\Documents\Session\Loaders\IncludeBuilderBase;
use RavenDB\Documents\Session\Operations\Lazy\LazyQueryOperation;
use RavenDB\Documents\Session\Operations\QueryOperation;
use RavenDB\Documents\Session\Tokens\CloseSubclauseToken;
use RavenDB\Documents\Session\Tokens\CompareExchangeValueIncludesToken;
use RavenDB\Documents\Session\Tokens\CompareExchangeValueIncludesTokenArray;
use RavenDB\Documents\Session\Tokens\CounterIncludesToken;
use RavenDB\Documents\Session\Tokens\CounterIncludesTokenArray;
use RavenDB\Documents\Session\Tokens\DeclareTokenArray;
use RavenDB\Documents\Session\Tokens\DistinctToken;
use RavenDB\Documents\Session\Tokens\ExplanationToken;
use RavenDB\Documents\Session\Tokens\FacetToken;
use RavenDB\Documents\Session\Tokens\FieldsToFetchToken;
use RavenDB\Documents\Session\Tokens\FromToken;
use RavenDB\Documents\Session\Tokens\GraphQueryToken;
use RavenDB\Documents\Session\Tokens\GroupByCountToken;
use RavenDB\Documents\Session\Tokens\GroupByKeyToken;
use RavenDB\Documents\Session\Tokens\GroupBySumToken;
use RavenDB\Documents\Session\Tokens\GroupByToken;
use RavenDB\Documents\Session\Tokens\HighlightingToken;
use RavenDB\Documents\Session\Tokens\HighlightingTokenArray;
use RavenDB\Documents\Session\Tokens\IntersectMarkerToken;
use RavenDB\Documents\Session\Tokens\LoadToken;
use RavenDB\Documents\Session\Tokens\LoadTokenList;
use RavenDB\Documents\Session\Tokens\MethodsType;
use RavenDB\Documents\Session\Tokens\MoreLikeThisToken;
use RavenDB\Documents\Session\Tokens\NegateToken;
use RavenDB\Documents\Session\Tokens\OpenSubclauseToken;
use RavenDB\Documents\Session\Tokens\OrderByToken;
use RavenDB\Documents\Session\Tokens\QueryOperatorToken;
use RavenDB\Documents\Session\Tokens\QueryToken;
use RavenDB\Documents\Session\Tokens\QueryTokenList;
use RavenDB\Documents\Session\Tokens\RevisionIncludesToken;
use RavenDB\Documents\Session\Tokens\RevisionIncludesTokenArray;
use RavenDB\Documents\Session\Tokens\ShapeToken;
use RavenDB\Documents\Session\Tokens\SuggestToken;
use RavenDB\Documents\Session\Tokens\TimeSeriesIncludesToken;
use RavenDB\Documents\Session\Tokens\TimeSeriesIncludesTokenArray;
use RavenDB\Documents\Session\Tokens\TimingsToken;
use RavenDB\Documents\Session\Tokens\TrueToken;
use RavenDB\Documents\Session\Tokens\WhereOperator;
use RavenDB\Documents\Session\Tokens\WhereOptions;
use RavenDB\Documents\Session\Tokens\WhereToken;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Exceptions\InvalidQueryException;
use RavenDB\Exceptions\UnsupportedOperationException;
use RavenDB\Extensions\JsonExtensions;
use RavenDB\Parameters;
use RavenDB\Primitives\CleanCloseable;
use RavenDB\Primitives\ClosureArray;
use RavenDB\Primitives\EventHelper;
use RavenDB\Type\Collection;
use RavenDB\Type\Duration;
use RavenDB\Type\Stack;
use RavenDB\Type\StringArray;
use RavenDB\Type\StringSet;
use RavenDB\Type\TypedList;
use RavenDB\Utils\DefaultsUtils;
use RavenDB\Utils\StringBuilder;
use RavenDB\Utils\StringUtils;
use function PHPUnit\Framework\isEmpty;

abstract class AbstractDocumentQuery implements AbstractDocumentQueryInterface
{
    protected ?string $className = null;

    private StringArray $aliasToGroupByFieldName;

    protected QueryOperator $defaultOperator;

    protected StringSet $rootTypes;

    /**
     * Whether to negate the next operation
     */
    protected bool $negate = false;

    /**
     *  Whether to negate the next operation in Filter
     */
    protected bool $negateFilter = false;

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

    protected ?Stack $filterModeStack = null;

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

    protected QueryTokenList $filterTokens;

    protected ?QueryToken $graphRawQuery = null;

    protected int $start = 0;

    private DocumentConventions $conventions;

    protected ?int $filterLimit = null;

    protected ?Duration $timeout = null;

    protected bool $theWaitForNonStaleResults = false;

    protected StringSet $documentIncludes;

    /**
     * Holds the query stats
     */
    protected QueryStatistics $queryStats;

    protected bool $disableEntitiesTracking = false;

    protected bool $disableCaching = false;

    protected ?ProjectionBehavior $projectionBehavior = null;

    private string $parameterPrefix = "p";

    protected function isFilterActive(): bool
    {
        return !$this->filterModeStack->isEmpty() && $this->filterModeStack->peek();
    }

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

    public function setProjectInto(bool $projectInto): void
    {
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

    private ?string $includesAlias = null;

    private function getDefaultTimeout(): ?Duration
    {
        return $this->conventions->getWaitForNonStaleResultsTimeout();
    }

    protected function __construct(
        ?string                            $className,
        ?InMemoryDocumentSessionOperations $session,
        ?string                            $indexName,
        ?string                            $collectionName,
        bool                               $isGroupBy,
        ?DeclareTokenArray                 $declareTokens,
        ?LoadTokenList                     $loadTokens,
        ?string                            $fromAlias = null,
        bool                               $isProjectInto = false
    )
    {
        $this->filterModeStack = new Stack();
        $this->queryParameters = new Parameters();
        $this->aliasToGroupByFieldName = new StringArray();

        $this->beforeQueryExecutedCallback = new ClosureArray();
        $this->afterQueryExecutedCallback = new ClosureArray();
        $this->afterStreamExecutedCallback = new ClosureArray();


        $this->selectTokens = new QueryTokenList();
        $this->whereTokens = new QueryTokenList();
        $this->groupByTokens = new QueryTokenList();
        $this->orderByTokens = new QueryTokenList();
        $this->withTokens = new QueryTokenList();
        $this->filterTokens = new QueryTokenList();

        $this->documentIncludes = new StringSet();
        $this->highlightingTokens = new HighlightingTokenArray();

        $this->rootTypes = new StringSet();

        $this->defaultOperator = QueryOperator::and();

        $this->queryStats = new QueryStatistics();

        $this->queryHighlightings = new QueryHighlightings();
        //
        //--

        $this->className = $className;
        if ($className) {
            $this->rootTypes->append($className);
        }
        $this->isGroupBy = $isGroupBy;
        $this->indexName = $indexName;
        $this->collectionName = $collectionName;
        $this->fromToken = FromToken::create($indexName, $collectionName, $fromAlias);
        $this->declareTokens = $declareTokens;
        $this->loadTokens = $loadTokens;
        $this->theSession = $session;
        $this->_addAfterQueryExecutedListener(Closure::fromCallable([$this, 'updateStatsHighlightingsAndExplanations']));
        $this->conventions = $session == null ? new DocumentConventions() : $session->getConventions();
        $this->isProjectInto = $isProjectInto;
    }

    public function getQueryClass(): ?string
    {
        return $this->className;
    }

    public function getGraphRawQuery(): QueryToken
    {
        return $this->graphRawQuery;
    }

    public function _usingDefaultOperator(QueryOperator $operator)
    {
        if (!$this->getCurrentWhereTokens()->isEmpty()) {
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
        $this->timeout = $waitTimeout ?? $this->getDefaultTimeout();
    }

    protected function getLazyQueryOperation(): LazyQueryOperation
    {
        if ($this->queryOperation == null) {
            $this->queryOperation = $this->initializeQueryOperation();
        }

        return new LazyQueryOperation($this->className, $this->theSession, $this->queryOperation, $this->afterQueryExecutedCallback);
    }

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

    /**
     * Order the search results randomly using the specified seed
     * this is useful if you want to have repeatable random queries
     * @param ?string $seed Seed to use
     */
    public function _randomOrdering(?string $seed = null): void
    {
        $this->assertNoRawQuery();

        $this->_noCaching();

        $token = StringUtils::isBlank($seed) ? OrderByToken::random() : OrderByToken::createRandom($seed);

        $this->orderByTokens->append($token);
    }

//    //TBD 4.1 public void _customSortUsing(String typeName)
//    //TBD 4.1 public void _customSortUsing(String typeName, boolean descending)


    public function _projection(ProjectionBehavior $projectionBehavior): void
    {
        $this->projectionBehavior = $projectionBehavior;
    }

    protected function addGroupByAlias(string $fieldName, ?string $projectedName = null): void
    {
        $this->aliasToGroupByFieldName[$projectedName] = $fieldName;
    }

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

    /**
     * @param string|GroupBy $fieldName
     * @param string|GroupBy ...$fieldNames
     */
    public function _groupBy($fieldName, ...$fieldNames): void
    {
        $field = is_string($fieldName) ? GroupBy::field($fieldName) : $fieldName;

        $mapping = [];

        foreach ($fieldNames as $fn) {
            $mapping[] = is_string($fn) ? GroupBy::field($fn) : $fn;
        }

        $this->_groupByField($field, ...$mapping);
    }

    public function _groupByField(GroupBy $field, GroupBy ...$fields): void
    {
        if (!$this->fromToken->isDynamic()) {
            throw new IllegalStateException("groupBy only works with dynamic queries");
        }

        $this->assertNoRawQuery();
        $this->isGroupBy = true;

        $fieldName = $this->ensureValidFieldName($field->getField(), false);

        $this->groupByTokens->append(GroupByToken::create($fieldName, $field->getMethod()));

        if ($fields == null || count($fields) <= 0) {
            return;
        }

        foreach ($fields as $item) {
            $fieldName = $this->ensureValidFieldName($item->getField(), false);
            $this->groupByTokens->append(GroupByToken::create($fieldName, $item->getMethod()));
        }
    }

    public function _groupByKey(?string $fieldName = null, ?string $projectedName = null): void
    {
        $this->assertNoRawQuery();
        $this->isGroupBy = true;

        if ($projectedName != null && $this->aliasToGroupByFieldName->offsetExists($projectedName)) {
            $aliasedFieldName = $this->aliasToGroupByFieldName->offsetGet($projectedName);
            if ($fieldName == null || strcasecmp($fieldName, $projectedName) == 0) {
                $fieldName = $aliasedFieldName;
            }
        } else if ($fieldName != null && $this->aliasToGroupByFieldName->hasValue($fieldName)) {
            $aliasedFieldName = $this->aliasToGroupByFieldName->offsetGet($fieldName);
            $fieldName = $aliasedFieldName;
        }

        $this->selectTokens->append(GroupByKeyToken::create($fieldName, $projectedName));
    }

    public function _groupBySum(?string $fieldName = null, ?string $projectedName = null): void
    {
        $this->assertNoRawQuery();
        $this->isGroupBy = true;

        $fieldName = $this->ensureValidFieldName($fieldName, false);
        $this->selectTokens->append(GroupBySumToken::create($fieldName, $projectedName));
    }

    public function _groupByCount(?string $projectedName = null): void
    {
        $this->assertNoRawQuery();
        $this->isGroupBy = true;

        $this->selectTokens->append(GroupByCountToken::create($projectedName));
    }

    public function _whereTrue(): void
    {
        $tokens = $this->getCurrentWhereTokens();
        $this->appendOperatorIfNeeded($tokens);
        $this->negateIfNeeded($tokens, null);

        $tokens->append(TrueToken::instance());
    }

    public function _moreLikeThis(): MoreLikeThisScope
    {
        $this->appendOperatorIfNeeded($this->whereTokens);

        $token = new MoreLikeThisToken();
        $this->whereTokens->append($token);

        $this->isInMoreLikeThis = true;
        $isInMoreLikeThis = & $this->isInMoreLikeThis;
        return new MoreLikeThisScope($token, Closure::fromCallable([$this, 'addQueryParameter']), function() use ($isInMoreLikeThis) { return $isInMoreLikeThis = false; } );
    }

    /**
     * @param string|IncludeBuilderBase|null $includes
     */
    public function _include($includes): void
    {
        if (is_string($includes)) {
            $this->_includeWithString($includes);
            return;
        }

        $this->_includeWithIncludeBuilder($includes);
    }


    /**
     * Includes the specified path in the query, loading the document specified in that path
     * @param ?string $path Path to include
     */
    protected function _includeWithString(?string $path): void
    {
        $this->documentIncludes->append($path);
    }

    //TBD expr public void Include(Expression<Func<T, object>> path)

    protected function _includeWithIncludeBuilder(?IncludeBuilderBase $includes)
    {
        if ($includes == null) {
            return;
        }

        if ($includes->documentsToInclude != null) {
            foreach ($includes->documentsToInclude as $document) {
                $this->documentIncludes->append($document);
            }
        }


        $this->_includeCounters($includes->alias, $includes->countersToIncludeBySourcePath);
        if ($includes->timeSeriesToIncludeBySourceAlias != null) {
            $this->_includeTimeSeries($includes->alias, $includes->timeSeriesToIncludeBySourceAlias);
        }

        if ($includes->revisionsToIncludeByDateTime != null) {
            $this->_includeRevisionsByDateTime($includes->revisionsToIncludeByDateTime);
        }

        if ($includes->revisionsToIncludeByChangeVector != null) {
            $this->_includeRevisionsByChangeVector($includes->revisionsToIncludeByChangeVector);
        }

        if ($includes->compareExchangeValuesToInclude != null) {
            $this->compareExchangeValueIncludesTokens = new CompareExchangeValueIncludesTokenArray();

            foreach ($includes->compareExchangeValuesToInclude as $compareExchangeValue) {
                $this->compareExchangeValueIncludesTokens->append(CompareExchangeValueIncludesToken::create($compareExchangeValue));
            }
        }
    }

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

    /**
     * Simplified method for opening a new clause within the query
     */
    public function _openSubclause(): void
    {
        $this->currentClauseDepth++;

        $tokens = $this->getCurrentWhereTokens();
        $this->appendOperatorIfNeeded($tokens);
        $this->negateIfNeeded($tokens, null);

        $tokens->append(OpenSubclauseToken::create());
    }

    /**
     * Simplified method for closing a clause within the query
     */
    public function _closeSubclause(): void
    {
        $this->currentClauseDepth--;

        $tokens = $this->getCurrentWhereTokens();
        $tokens->append(CloseSubclauseToken::create());
    }

    public function _whereEquals(string $fieldName, $value, bool $exact = false): void
    {
        $whereParams = new WhereParams();

        $whereParams->setFieldName($fieldName);
        $whereParams->setValue($value);
        $whereParams->setExact($exact);

        $this->_whereEqualsWithParams($whereParams);
    }

    public function _whereEqualsWithParams(WhereParams $whereParams): void
    {
        if ($this->negate) {
            $this->negate = false;
            $this->_whereNotEqualsWithParams($whereParams);
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
        if ($whereParams->getValue() instanceof MethodCall) {
            /** @var MethodCall $mc */
            $mc = $whereParams->getValue();

            $args = new StringArray();
            foreach ($mc->args as $arg) {
                $args[] = $this->addQueryParameter($arg);
            }

            $type = get_class($mc);
            if ($type == CmpXchg::class) {
                $token = WhereToken::create($op, $whereParams->getFieldName(), null, new WhereOptions(MethodsType::cmpXChg(), $args, $mc->accessPath, $whereParams->isExact()));
            } else {
                throw new IllegalArgumentException("Unknown method " . $type);
            }

            $tokens->append($token);
            return true;
        }

        return false;
    }

    public function _whereNotEquals(string $fieldName, $value, bool $exact = false): void
    {
        $whereParams = new WhereParams();

        $whereParams->setFieldName($fieldName);
        $whereParams->setValue($value);
        $whereParams->setExact($exact);

        $this->_whereNotEqualsWithParams($whereParams);
    }

    public function _whereNotEqualsWithParams(WhereParams $whereParams): void
    {
        if ($this->negate) {
            $this->negate = false;
            $this->_whereEqualsWithParams($whereParams);
            return;
        }

        $whereParams->setFieldName($this->ensureValidFieldName($whereParams->getFieldName(), $whereParams->isNestedPath()));

        $tokens = $this->getCurrentWhereTokens();
        $this->appendOperatorIfNeeded($tokens);

        if ($this->ifValueIsMethod(WhereOperator::notEquals(), $whereParams, $tokens)) {
            return;
        }

        $transformToEqualValue = $this->transformValue($whereParams);
        $addQueryParameter = $this->addQueryParameter($transformToEqualValue);
        $whereToken = WhereToken::create(WhereOperator::notEquals(), $whereParams->getFieldName(), $addQueryParameter, new WhereOptions($whereParams->isExact()));
        $tokens->append($whereToken);
    }

    public function _negateNext(): void
    {
        $this->negate = !$this->negate;
    }


    /**
     * Check that the field has one of the specified value
     * @param string $fieldName Field name to use
     * @param Collection $values Values to find
     * @param bool $exact Use exact matcher
     */
    public function _whereIn(string $fieldName, Collection $values, bool $exact = false): void
    {
        $this->assertMethodIsCurrentlySupported("whereIn");

        $fieldName = $this->ensureValidFieldName($fieldName, false);

        $tokens = $this->getCurrentWhereTokens();
        $this->appendOperatorIfNeeded($tokens);
        $this->negateIfNeeded($tokens, $fieldName);

        $whereToken = WhereToken::create(WhereOperator::in(), $fieldName, $this->addQueryParameter($this->transformCollection($fieldName, self::unpackCollection($values))));
        $tokens->append($whereToken);
    }

    public function _whereStartsWith(string $fieldName, $value, bool $exact = false): void
    {
        $this->assertMethodIsCurrentlySupported("whereStartsWith");

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
        $this->assertMethodIsCurrentlySupported("whereEndsWith");

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
    public function _whereBetween(string $fieldName, mixed $start, mixed $end, bool $exact = false): void
    {
        $this->assertMethodIsCurrentlySupported("whereBetween");

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

    /**
     * Matches fields where the value is greater than the specified value
     * @param string $fieldName Field name to use
     * @param mixed $value Value to compare
     * @param bool $exact Use exact matcher
     */
    public function _whereGreaterThan(string $fieldName, $value, bool $exact = false): void
    {
        $fieldName = $this->ensureValidFieldName($fieldName, false);

        $tokens = $this->getCurrentWhereTokens();
        $this->appendOperatorIfNeeded($tokens);
        $this->negateIfNeeded($tokens, $fieldName);
        $whereParams = new WhereParams();
        $whereParams->setValue($value);
        $whereParams->setFieldName($fieldName);

        $parameter = $this->addQueryParameter($value == null ? "*" : $this->transformValue($whereParams, true));

        $whereToken = WhereToken::create(WhereOperator::greaterThan(), $fieldName, $parameter, new WhereOptions($exact));
        $tokens->append($whereToken);
    }


    /**
     * Matches fields where the value is greater than or equal to the specified value
     * @param string $fieldName Field name
     * @param mixed $value Value to use
     * @param bool $exact Use exact matcher
     */
    public function _whereGreaterThanOrEqual(string $fieldName, $value, bool $exact = false): void
    {
        $fieldName = $this->ensureValidFieldName($fieldName, false);

        $tokens = $this->getCurrentWhereTokens();
        $this->appendOperatorIfNeeded($tokens);
        $this->negateIfNeeded($tokens, $fieldName);
        $whereParams = new WhereParams();
        $whereParams->setValue($value);
        $whereParams->setFieldName($fieldName);

        $parameter = $this->addQueryParameter($value == null ? "*" : $this->transformValue($whereParams, true));
        $whereToken = WhereToken::create(WhereOperator::greaterThanOrEqual(), $fieldName, $parameter, new WhereOptions($exact));
        $tokens->append($whereToken);
    }

    public function _whereLessThan(string $fieldName, $value, bool $exact = false): void
    {
        $fieldName = $this->ensureValidFieldName($fieldName, false);

        $tokens = $this->getCurrentWhereTokens();
        $this->appendOperatorIfNeeded($tokens);
        $this->negateIfNeeded($tokens, $fieldName);

        $whereParams = new WhereParams();
        $whereParams->setValue($value);
        $whereParams->setFieldName($fieldName);

        $parameter = $this->addQueryParameter($value == null ? "NULL" : $this->transformValue($whereParams, true));
        $whereToken = WhereToken::create(WhereOperator::lessThan(), $fieldName, $parameter, new WhereOptions($exact));
        $tokens->append($whereToken);
    }

    public function _whereLessThanOrEqual(string $fieldName, $value, bool $exact = false): void
    {
        $fieldName = $this->ensureValidFieldName($fieldName, false);

        $tokens = $this->getCurrentWhereTokens();
        $this->appendOperatorIfNeeded($tokens);
        $this->negateIfNeeded($tokens, $fieldName);

        $whereParams = new WhereParams();
        $whereParams->setValue($value);
        $whereParams->setFieldName($fieldName);

        $parameter = $this->addQueryParameter($value == null ? "NULL" : $this->transformValue($whereParams, true));
        $whereToken = WhereToken::create(WhereOperator::lessThanOrEqual(), $fieldName, $parameter, new WhereOptions($exact));
        $tokens->append($whereToken);
    }

    /**
     * Matches fields where Regex.IsMatch(filedName, pattern)
     * @param ?string $fieldName Field name to use
     * @param ?string $pattern Regexp pattern
     */

    public function _whereRegex(?string $fieldName, ?string $pattern): void
    {
        $this->assertMethodIsCurrentlySupported("whereRegex");

        $fieldName = $this->ensureValidFieldName($fieldName, false);

        $tokens = $this->getCurrentWhereTokens();
        $this->appendOperatorIfNeeded($tokens);
        $this->negateIfNeeded($tokens, $fieldName);

        $whereParams = new WhereParams();
        $whereParams->setValue($pattern);
        $whereParams->setFieldName($fieldName);

        $parameter = $this->addQueryParameter($this->transformValue($whereParams));

        $whereToken = WhereToken::create(WhereOperator::regex(), $fieldName, $parameter);
        $tokens->append($whereToken);
    }

    public function _andAlso(bool $wrapPreviousQueryClauses = false): void
    {
        $tokens = $this->getCurrentWhereTokens();
        if ($tokens->isEmpty()) {
            return;
        }

        if ($tokens->last() instanceof QueryOperatorToken) {
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

    protected function setFilterMode(bool $on): CleanCloseable
    {
        return new FilterModeScope($this->filterModeStack, $on);
    }

    /**
     * Specifies a boost weight to the last where clause.
     * The higher the boost factor, the more relevant the term will be.
     * <p>
     * boosting factor where 1.0 is default, less than 1.0 is lower weight, greater than 1.0 is higher weight
     * <p>
     * http://lucene.apache.org/java/2_4_0/queryparsersyntax.html#Boosting%20a%20Term
     *
     * @param float $boost Boost value
     */
    public function _boost(float $boost): void
    {
        $this->assertMethodIsCurrentlySupported("boost");

        if ($boost == 1.0) {
            return;
        }

        if ($boost < 0.0) {
            throw new IllegalArgumentException("Boost factor must be a non-negative number");
        }

        $tokens = $this->getCurrentWhereTokens();

        $last = $tokens->isEmpty() ? null : $tokens->last();

        if ($last instanceof WhereToken) {
            $whereToken = $last;

            $whereOptions = $whereToken->getOptions();
            $whereOptions->setBoost($boost);
            $whereToken->setOptions($whereOptions);
        } else if ($last instanceof CloseSubclauseToken) {
            $close = $last;

            $parameter = $this->addQueryParameter($boost);
            foreach (array_reverse($tokens->getArrayCopy()) as $token) {
                $last = $token; // find the previous option

                if ($last instanceof OpenSubclauseToken) {
                    $open = $last;

                    $open->setBoostParameterName($parameter);
                    $close->setBoostParameterName($parameter);
                    return;
                }
            }
        } else {
            throw new IllegalStateException("Cannot apply boost");
        }
    }

    /**
     * Specifies a fuzziness factor to the single word term in the last where clause
     * <p>
     * 0.0 to 1.0 where 1.0 means closer match
     * <p>
     * http://lucene.apache.org/java/2_4_0/queryparsersyntax.html#Fuzzy%20Searches
     * @param float $fuzzy Fuzzy value
     */
    public function _fuzzy(float $fuzzy): void
    {
        $this->assertMethodIsCurrentlySupported("fuzzy");

        $tokens = $this->getCurrentWhereTokens();
        if ($tokens->isEmpty()) {
            throw new IllegalStateException('Fuzzy can only be used right after where clause');
        }

        $whereToken = $tokens->last();
        if (!($whereToken instanceof WhereToken)) {
            throw new IllegalStateException('Fuzzy can only be used right after where clause');
        }

        if (!$whereToken->getWhereOperator()->isEquals()) {
            throw new IllegalStateException('Fuzzy can only be used right after where clause with equals operator');
        }

        if ($fuzzy < 0.0 || $fuzzy > 1.0) {
            throw new IllegalArgumentException('Fuzzy distance must be between 0.0 and 1.0');
        }

        $whereToken->getOptions()->setFuzzy($fuzzy);
    }

    /**
     * Specifies a proximity distance for the phrase in the last search clause
     * <p>
     * http://lucene.apache.org/java/2_4_0/queryparsersyntax.html#Proximity%20Searches
     * @param int $proximity Proximity value
     */
    public function _proximity(int $proximity): void
    {
        $this->assertMethodIsCurrentlySupported("proximity");

        $tokens = $this->getCurrentWhereTokens();
        if ($tokens->isEmpty()) {
            throw new IllegalStateException("Proximity can only be used right after search clause");
        }

        $whereToken = $tokens->last();
        if (!($whereToken instanceof WhereToken)) {
            throw new IllegalStateException('Proximity can only be used right after search clause');
        }

        if (!$whereToken->getWhereOperator()->isSearch()) {
            throw new IllegalStateException('Proximity can only be used right after search clause');
        }

        if ($proximity < 1) {
            throw new IllegalArgumentException('Proximity distance must be a positive number');
        }

        $whereToken->getOptions()->setProximity($proximity);
    }

    /**
     * Order the results by the specified fields
     * The fields are the names of the fields to sort, defaulting to sorting by ascending.
     * You can prefix a field name with '-' to indicate sorting by descending or '+' to sort by ascending
     *
     * @param string $field
     * @param OrderingType|string|null $sorterNameOrOrdering
     */
    public function _orderBy(string $field, OrderingType|string|null $sorterNameOrOrdering = null): void
    {
        $this->assertNoRawQuery();
        $f = $this->ensureValidFieldName($field, false);
        $this->orderByTokens->append(OrderByToken::createAscending($f, $sorterNameOrOrdering ?? OrderingType::string()));
    }

    /**
     * Order the results by the specified fields
     * The fields are the names of the fields to sort, defaulting to sorting by descending.
     * You can prefix a field name with '-' to indicate sorting by descending or '+' to sort by ascending
     * @param string $field Field to use
     * @param string|OrderingType|null $sorterNameOrOrdering Sorter to use
     */
    public function _orderByDescending(string $field, OrderingType|string|null $sorterNameOrOrdering = null): void
    {
        $this->assertNoRawQuery();
        $f = $this->ensureValidFieldName($field, false);
        $this->orderByTokens->append(OrderByToken::createDescending($f, $sorterNameOrOrdering ?? OrderingType::string()));
    }

    public function _orderByScore(): void
    {
        $this->assertNoRawQuery();

        $this->orderByTokens->append(OrderByToken::scoreAscending());
    }

    public function _orderByScoreDescending(): void
    {
        $this->assertNoRawQuery();
        $this->orderByTokens->append(OrderByToken::scoreDescending());
    }

    public function &getStats(): QueryStatistics
    {
        return $this->queryStats;
    }

    /**
     * Provide statistics about the query, such as total count of matching records
     * @param QueryStatistics $stats Output parameter for query statistics
     */
    public function _statistics(QueryStatistics &$stats): void
    {
        $stats = $this->getStats();
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

    public function invokeAfterStreamExecuted($result): void
    {
        EventHelper::invoke($this->afterStreamExecutedCallback, $result);
    }

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
        $indexQuery->setProjectionBehavior($this->projectionBehavior);

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
        $this->assertMethodIsCurrentlySupported("search");

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
        if ($this->start > 0 || $this->pageSize !== null) {
            $queryText
                ->append(" limit $")
                ->append($this->addQueryParameter($this->start))
                ->append(", $")
                ->append($this->addQueryParameter($this->pageSize));
        }
        if (!$this->filterTokens->isEmpty() && $this->filterLimit !== null) {
            $queryText
                ->append(' filter_limit $')
                ->append($this->addQueryParameter($this->filterLimit));
        }
    }

    private function buildInclude(StringBuilder $queryText): void
    {
        if ($this->documentIncludes->isEmpty() &&
            $this->highlightingTokens->isEmpty() &&
            $this->explanationToken == null &&
            $this->queryTimings == null &&
            $this->counterIncludesTokens == null &&
            $this->revisionsIncludesTokens == null &&
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
        $this->writeIncludeTokens($this->revisionsIncludesTokens, $firstRef, $queryText);
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

        foreach ($tokens as $token) {
            if (!$firstRef) {
                $queryText->append(",");
            }
            $firstRef = false;

            $token->writeTo($queryText);
        }
    }

    public function _intersect(): void
    {
        $tokens = $this->getCurrentWhereTokens();
        if (count($tokens)) {
            $last = $tokens->last();
            if ($last instanceof WhereToken || $last instanceof CloseSubclauseToken) {
                $this->isIntersect = true;

                $tokens->append(IntersectMarkerToken::getInstance());
                return;
            }
        }

        throw new IllegalStateException("Cannot add INTERSECT at this point.");
    }

    public function _whereExists(string $fieldName): void
    {
        $fieldName = $this->ensureValidFieldName($fieldName, false);

        $tokens = $this->getCurrentWhereTokens();
        $this->appendOperatorIfNeeded($tokens);
        $this->negateIfNeeded($tokens, null);

        $tokens->append(WhereToken::create(WhereOperator::exists(), $fieldName, null));
    }

    public function _containsAny(?string $fieldName, Collection $values): void
    {
        $this->assertMethodIsCurrentlySupported("containsAny");

        $fieldName = $this->ensureValidFieldName($fieldName, false);

        $tokens = $this->getCurrentWhereTokens();
        $this->appendOperatorIfNeeded($tokens);
        $this->negateIfNeeded($tokens, $fieldName);

        $array = $this->transformCollection($fieldName, $this->unpackCollection($values));
        $whereToken = WhereToken::create(WhereOperator::in(), $fieldName, $this->addQueryParameter($array), new WhereOptions(false));
        $tokens->append($whereToken);
    }

    public function _containsAll(?string $fieldName, Collection $values): void
    {
        $this->assertMethodIsCurrentlySupported("containsAll");

        $fieldName = $this->ensureValidFieldName($fieldName, false);

        $tokens = $this->getCurrentWhereTokens();
        $this->appendOperatorIfNeeded($tokens);
        $this->negateIfNeeded($tokens, $fieldName);

        $array = $this->transformCollection($fieldName, $this->unpackCollection($values));

        if (empty($array)) {
            $tokens->append(TrueToken::instance());
            return;
        }

        $whereToken = WhereToken::create(WhereOperator::allIn(), $fieldName, $this->addQueryParameter($array));
        $tokens->append($whereToken);
    }

//    public void _addRootType(Class clazz) {
//        rootTypes.add(clazz);
//    }

    //TBD expr public string GetMemberQueryPathForOrderBy(Expression expression)
    //TBD expr public string GetMemberQueryPath(Expression expression)

    public function _distinct(): void
    {
        if ($this->isDistinct()) {
            throw new IllegalStateException("The is already a distinct query");
        }

        if ($this->selectTokens->isEmpty()) {
            $this->selectTokens->append(DistinctToken::getInstance());
        } else {
            $this->selectTokens->prepend(DistinctToken::getInstance());
        }
    }

    private function updateStatsHighlightingsAndExplanations(QueryResult $queryResult): void
    {
        $this->queryStats->updateQueryStats($queryResult);
        $this->queryHighlightings->update($queryResult);
        if ($this->explanations != null) {
            $this->explanations->update($queryResult);
        }
        if ($this->queryTimings != null) {
            $this->queryTimings->update($queryResult);
        }
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
            if (($prevToken != null) && !is_a($prevToken, DistinctToken::class)) {
                $writer->append(",");
            }

            DocumentQueryHelper::addSpaceIfNeeded($prevToken, $currentToken, $writer);
            $currentToken->writeTo($writer);
            $prevToken = $currentToken;
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

    private function buildFilter(StringBuilder $writer): void
    {
        if ($this->filterTokes->isEmpty()) {
            return;
        }

        $writer
            ->append(' filter ');

        for ($i=0; $i < $this->filterTokens->count(); $i++) {
            DocumentQueryHelper::addSpaceIfNeeded(
                $i > 0 ? $this->filterTokens[$i-1] : null,
                $this->filterTokens[$i],
                $writer
            );
            $this->filterTokens[$i]->writeTo($writer);
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
        $token = $this->defaultOperator->isAnd() ? QueryOperatorToken::and() : QueryOperatorToken::or();

        if ($lastWhere != null && $lastWhere->getOptions()->getSearchOperator() != null) {
            $token = QueryOperatorToken::or(); // default to OR operator after search if AND was not specified explicitly
        }

        $tokens->append($token);
    }

    private function transformCollection(string $fieldName, Collection $values): Collection
    {
        $result = new Collection();
        foreach ($values as $value) {
            if ($value instanceof Collection) {
                $collectionItems = $this->transformCollection($fieldName, $value);
                foreach ($collectionItems as $item) {
                    $result->append($item);
                }
            } else {
                $nestedWhereParams = new WhereParams();
                $nestedWhereParams->setAllowWildcards(true);
                $nestedWhereParams->setFieldName($fieldName);
                $nestedWhereParams->setValue($value);

                $result->append(self::transformValue($nestedWhereParams));
            }
        }
        return $result;
    }

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

    private static function unpackCollection(Collection $items): Collection
    {
        $results = new Collection();

        foreach ($items as $item) {
            if ($item instanceof Collection) {
                $subCollections = self::unpackCollection($item);
                foreach ($subCollections as $collection) {
                    $results->append($collection);
                }
            } else {
                $results->append($item);
            }
        }

        return $results;
    }

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

    /**
     * @param WhereParams $whereParams
     * @param bool $forRange
     * @return mixed
     */
    private function transformValue(WhereParams $whereParams, bool $forRange = false)
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

        if (is_object($whereParams->getValue())) {
            $object = $whereParams->getValue();
            if ($object instanceof Duration) {
                return $object->toNanos() / 100;
            }
        }

        return $whereParams->getValue();
    }

    private function addQueryParameter($value): string
    {
        $parameterName = $this->getParameterPrefix() . $this->queryParameters->count();
        $this->queryParameters->offsetSet($parameterName, $value);
        return $parameterName;
    }

    private function assertMethodIsCurrentlySupported(?string $methodName): void
    {
        if (!$this->isFilterActive()) {
            return;
        }

        throw new InvalidQueryException($methodName . " is currently unsupported for 'filter'. If you want to use "
            . $methodName . " in where method you have to put it before 'filter'");
    }
    private function getCurrentWhereTokens(): QueryTokenList
    {
        if ($this->isFilterActive()) {
            return $this->filterTokens;
        }

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

    private function getCurrentFilterTokens(): QueryTokenList
    {
        return $this->filterTokens;
    }

    protected function updateFieldsToFetchToken(FieldsToFetchToken $fieldsToFetch): void
    {
        $this->fieldsToFetchToken = $fieldsToFetch;

        if ($this->selectTokens->isEmpty()) {
            $this->selectTokens->append($fieldsToFetch);
        } else {
            $fetchToken = null;
            $idx = null;
            foreach ($this->selectTokens as $index => $token) {
                if ($token instanceof FieldsToFetchToken) {
                    $fetchToken = $token;
                    $idx = $index;
                    break;
                }
            }

            if ($fetchToken) {
                $this->selectTokens->offsetSet($idx, $fieldsToFetch);
            } else {
                $this->selectTokens->append($fieldsToFetch);
            }
        }
    }

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

    /**
     * @param string|null $className
     * @param QueryData $queryData
     * @param StringArray|array $fields
     * @param string|null $sourceAlias
     */
    protected static function getSourceAliasIfExists(?string $className, QueryData $queryData, $fields, ?string &$sourceAlias): void
    {
        if (is_array($fields)) {
            $fields = StringArray::fromArray($fields);
        }

        $sourceAlias = null;

        if ($fields->count() != 1 || $fields[0] == null) {
            return;
        }

        $indexOf = strpos($fields[0], '.');
        if ($indexOf == -1) {
            return;
        }

        $possibleAlias = substr($fields[0], 0, $indexOf);
        if ($queryData->getFromAlias() != null && strcmp($queryData->getFromAlias(), $possibleAlias) == 0) {
            $sourceAlias = $possibleAlias;
            return;
        }

        if ($queryData->getLoadTokens() == null || $queryData->getLoadTokens()->isEmpty()) {
            return;
        }

        $noneMatch = true;
        /** @var LoadToken $token */
        foreach ($queryData->getLoadTokens() as $token) {
            if ($token->alias == $possibleAlias) {
                $noneMatch = false;
            }
        }
        if ($noneMatch) {
            return;
        }

        $sourceAlias = $possibleAlias;
    }

    protected function createTimeSeriesQueryData(Closure $timeSeriesQuery): QueryData
    {
        $builder = new TimeSeriesQueryBuilder();
        $timeSeriesQuery($builder);

        $fields = [ TimeSeries::SELECT_FIELD_NAME . "(" . $builder->getQueryText() . ")" ];
        $projections = [ TimeSeries::QUERY_FUNCTION ];
        return new QueryData($fields, $projections);
    }

    public function _addFilterLimit(int $filterLimit): void
    {
        if ($filterLimit <= 0) {
            throw new IllegalArgumentException("filter_limit need to be positive and bigger than 0.");
        }

        if ($filterLimit != PhpClient::INT_MAX_VALUE) {
            $this->filterLimit = $filterLimit;
        }
    }

    protected ClosureArray $beforeQueryExecutedCallback;

    protected ClosureArray $afterQueryExecutedCallback;

    protected ClosureArray $afterStreamExecutedCallback;

    protected ?QueryOperation $queryOperation = null;

    public function getQueryOperation(): ?QueryOperation
    {
        return $this->queryOperation;
    }

    /**
     * @param Closure<IndexQuery> $action
     */
    public function _addBeforeQueryExecutedListener(Closure $action): void
    {
        $this->beforeQueryExecutedCallback->append($action);
    }

    /**
     * @param Closure<IndexQuery> $action
     */
    public function _removeBeforeQueryExecutedListener(Closure $action): void
    {
        $this->beforeQueryExecutedCallback->removeValue($action);
    }

    /**
     * @param Closure<IndexQuery> $action
     */
    public function _addAfterQueryExecutedListener(Closure $action): void
    {
        $this->afterQueryExecutedCallback->append($action);
    }

    public function _removeAfterQueryExecutedListener(Closure $action): void
    {
        $this->afterQueryExecutedCallback->removeValue($action);
    }

    /**
     * @param Closure<mixed> $action
     */
    public function _addAfterStreamExecutedListener(Closure $action)
    {
        $this->afterStreamExecutedCallback->append($action);
    }

    /**
     * @param Closure<mixed> $action
     */
    public function _removeAfterStreamExecutedListener(Closure $action)
    {
        $this->afterStreamExecutedCallback->removeValue($action);
    }

    public function _noTracking(): void
    {
        $this->disableEntitiesTracking = true;
    }

    public function _noCaching(): void
    {
        $this->disableCaching = true;
    }

    protected ?QueryTimings $queryTimings = null;

    public function _includeTimings(QueryTimings &$timingsReference): void
    {
        if ($this->queryTimings != null) {
            $timingsReference = $this->queryTimings;
            return;
        }

        $this->queryTimings = $timingsReference;
    }

    protected HighlightingTokenArray $highlightingTokens;

    protected ?QueryHighlightings $queryHighlightings = null;

    public function _highlight(?string $fieldName, int $fragmentLength, int $fragmentCount, ?HighlightingOptions $options, Highlightings &$highlightingsReference): void
    {
        $highlightingsReference = $this->queryHighlightings->add($fieldName);

        $optionsParameterName = $options != null ? $this->addQueryParameter(JsonExtensions::getDefaultMapper()->normalize($options)) : null;
        $this->highlightingTokens->append(HighlightingToken::create($fieldName, $fragmentLength, $fragmentCount, $optionsParameterName));
    }

    protected function _withinRadiusOf(?string $fieldName, float $radius, float $latitude, float $longitude, ?SpatialUnits $radiusUnits, float $distErrorPercent): void
    {
        $fieldName = $this->ensureValidFieldName($fieldName, false);

        $tokens = $this->getCurrentWhereTokens();
        $this->appendOperatorIfNeeded($tokens);
        $this->negateIfNeeded($tokens, $fieldName);

        $whereToken = WhereToken::create(WhereOperator::spatialWithin(), $fieldName, null, new WhereOptions(ShapeToken::circle($this->addQueryParameter($radius), $this->addQueryParameter($latitude), $this->addQueryParameter($longitude), $radiusUnits), $distErrorPercent));
        $tokens->append($whereToken);
    }

    protected function _spatialWithShape(?string $fieldName, ?string $shapeWkt, ?SpatialRelation $relation, ?SpatialUnits $units, float $distErrorPercent): void
    {
        $fieldName = $this->ensureValidFieldName($fieldName, false);

        $tokens = $this->getCurrentWhereTokens();
        $this->appendOperatorIfNeeded($tokens);
        $this->negateIfNeeded($tokens, $fieldName);

        $wktToken = ShapeToken::wkt($this->addQueryParameter($shapeWkt), $units);

        $whereOperator = null;
        switch ($relation->getValue()) {
            case SpatialRelation::WITHIN:
                $whereOperator = WhereOperator::spatialWithin();
                break;
            case SpatialRelation::CONTAINS:
                $whereOperator = WhereOperator::spatialContains();
                break;
            case SpatialRelation::DISJOINT:
                $whereOperator = WhereOperator::spatialDisjoint();
                break;
            case SpatialRelation::INTERSECTS:
                $whereOperator = WhereOperator::spatialIntersect();
                break;
            default:
                throw new IllegalArgumentException();
        }

        $tokens->append(WhereToken::create($whereOperator, $fieldName, null, new WhereOptions($wktToken, $distErrorPercent)));
    }

    public function _spatialWithDynamicField(?DynamicSpatialField $dynamicField, ?SpatialCriteria $criteria): void
    {
        $this->assertIsDynamicQuery($dynamicField, "spatial");

        $tokens = $this->getCurrentWhereTokens();
        $this->appendOperatorIfNeeded($tokens);
        $this->negateIfNeeded($tokens, null);

        $tokens->append($criteria->toQueryToken($dynamicField->toField(Closure::fromCallable([$this, 'ensureValidFieldName'])), Closure::fromCallable([$this, 'addQueryParameter'])));
    }

    public function _spatialWithFieldName(?string $fieldName, ?SpatialCriteria $criteria): void
    {
        $fieldName = $this->ensureValidFieldName($fieldName, false);

        $tokens = $this->getCurrentWhereTokens();
        $this->appendOperatorIfNeeded($tokens);
        $this->negateIfNeeded($tokens, $fieldName);

        $tokens->append($criteria->toQueryToken($fieldName, Closure::fromCallable([$this, 'addQueryParameter'])));
    }

    public function _orderByDistanceDSFFromPoint(?DynamicSpatialField $field, float $latitude, float $longitude): void
    {
        if ($field == null) {
            throw new IllegalArgumentException("Field cannot be null");
        }
        $this->assertIsDynamicQuery($field, "orderByDistance");

        $this->_orderByDistanceFromPoint("'" . $field->toField(Closure::fromCallable([$this, 'ensureValidFieldName'])) . "'", $latitude, $longitude, $field->getRoundFactor() ?? 0);
    }

    public function _orderByDistanceFromPoint(?string $fieldName, float $latitude, float $longitude, float $roundFactor = 0): void
    {
        $roundFactorParameterName = $roundFactor == 0 ? null : $this->addQueryParameter($roundFactor);
        $this->orderByTokens->append(OrderByToken::createDistanceAscendingFromPoint($fieldName, $this->addQueryParameter($latitude), $this->addQueryParameter($longitude), $roundFactorParameterName));
    }

    public function _orderByDistanceDSFFromWkt(?DynamicSpatialField $field, string $shapeWkt): void
    {
        if ($field == null) {
            throw new IllegalArgumentException("Field cannot be null");
        }
        $this->assertIsDynamicQuery($field, "orderByDistance");

        $this->_orderByDistanceFromWkt("'" . $field->toField(Closure::fromCallable([$this, 'ensureValidFieldName'])) . "'", $shapeWkt, $field->getRoundFactor());
    }

    public function _orderByDistanceFromWkt(string $fieldName, string $shapeWkt, float $roundFactor = 0): void
    {
        $roundFactorParameterName = $roundFactor == 0 ? null : $this->addQueryParameter($roundFactor);
        $this->orderByTokens->append(OrderByToken::createDistanceAscendingFromWkt($fieldName, $this->addQueryParameter($shapeWkt), $roundFactorParameterName));
    }

    public function _orderByDistanceDescendingDSFFromPoint(?DynamicSpatialField $field, float $latitude, float $longitude): void
    {
        if ($field == null) {
            throw new IllegalArgumentException("Field cannot be null");
        }
        $this->assertIsDynamicQuery($field, "orderByDistanceDescending");
        $this->_orderByDistanceDescendingFromPoint("'" . $field->toField(Closure::fromCallable([$this, 'ensureValidFieldName'])) . "'", $latitude, $longitude, $field->getRoundFactor() ?? 0);
    }

    public function _orderByDistanceDescendingFromPoint(string $fieldName, float $latitude, float $longitude, float $roundFactor = 0): void
    {
        $roundFactorParameterName = $roundFactor == 0 ? null : $this->addQueryParameter($roundFactor);
        $this->orderByTokens->append(OrderByToken::createDistanceDescendingFromPoint($fieldName, $this->addQueryParameter($latitude), $this->addQueryParameter($longitude), $roundFactorParameterName));
    }

    public function _orderByDistanceDescendingDSFFromWkt(?DynamicSpatialField $field, string $shapeWkt): void
    {
        if ($field == null) {
            throw new IllegalArgumentException("Field cannot be null");
        }
        $this->assertIsDynamicQuery($field, "orderByDistanceDescending");
        $this->_orderByDistanceDescendingFromWkt("'" . $field->toField(Closure::fromCallable([$this, 'ensureValidFieldName'])) . "'", $shapeWkt, $field->getRoundFactor());
    }

    public function _orderByDistanceDescendingFromWkt(string $fieldName, string $shapeWkt, float $roundFactor = 0): void
    {
        $factorParamName = $roundFactor == 0 ? null : $this->addQueryParameter($roundFactor);
        $this->orderByTokens->append(OrderByToken::createDistanceDescendingFromWkt($fieldName, $this->addQueryParameter($shapeWkt), $factorParamName));
    }

    private function assertIsDynamicQuery(?DynamicSpatialField $dynamicField, ?string $methodName): void
    {
        if (!$this->fromToken->isDynamic()) {
            throw new IllegalStateException("Cannot execute query method '" . $methodName . "'. Field '"
                    . $dynamicField->toField(Closure::fromCallable([$this, 'ensureValidFieldName'])) . "' cannot be used when static index '" . $this->fromToken->getIndexName()
                    . "' is queried. Dynamic spatial fields can only be used with dynamic queries, " .
                    "for static index queries please use valid spatial fields defined in index definition.");
        }
    }

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
            if ($context) {
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

    public function getQueryResult(): QueryResult
    {
        $this->initSync();

        return $this->queryOperation->getCurrentQueryResults()->createSnapshot();
    }

    /**
     * @return mixed
     */
    public function first(): mixed
    {
        $result = $this->executeQueryOperation(1);
        if (empty($result)) {
            throw new IllegalStateException("Expected at least one result");
        }
        return $result[array_key_first($result)];
    }

    /**
     * @return mixed
     */
    public function firstOrDefault(): mixed
    {
        $result = $this->executeQueryOperation(1);

        $firstKey = array_key_first($result);

        if ($firstKey === null) {
            return DefaultsUtils::defaultValue($this->className);
        }

        return $result[$firstKey];
    }

    /**
     * @return mixed
     */
    public function single(): mixed
    {
        $result = $this->executeQueryOperation(2);
        if (count($result) != 1) {
            throw new IllegalStateException("Expected single result, got: " . count($result));
        }
        return $result[array_key_first($result)];
    }

    /**
     * @return mixed
     */
    public function singleOrDefault(): mixed
    {
        $result = $this->executeQueryOperation(2);
        if (count($result) > 1) {
            throw new IllegalStateException("Expected single result, got: " . count($result));
        }
        if (empty($result)) {
            return DefaultsUtils::defaultValue($this->className);
        }
        return $result[array_key_first($result)];
    }

    public function count(): int
    {
        $this->_take(0);
        $queryResult = $this->getQueryResult();
        return $queryResult->getTotalResults();
    }

    public function longCount(): int
    {
        $this->_take(0);
        $queryResult = $this->getQueryResult();
        return $queryResult->getLongTotalResults();
    }

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

        return $this->queryOperation->completeAsArray($this->className);
    }

    private function executeQueryOperationInternal(?int $take): void
    {
        if ($take != null && ($this->pageSize == null || $this->pageSize > $take)) {
            $this->take($take);
        }

        $this->initSync();
    }

    public function _aggregateBy(FacetBase $facet): void
    {
        foreach ($this->selectTokens as $token) {
            if ($token instanceof FacetToken) {
                continue;
            }

            $reflection = new \ReflectionClass($token);
            throw new IllegalStateException("Aggregation query can select only facets while it got " . $reflection->getShortName() . " token");
        }

        $this->selectTokens->append(FacetToken::create($facet, Closure::fromCallable([$this, 'addQueryParameter'])));
    }

    public function _aggregateUsing(?string $facetSetupDocumentId): void
    {
        $this->selectTokens->append(FacetToken::create($facetSetupDocumentId));
    }

    function lazily(?Closure $onEval = null): Lazy
    {
        $lazyQueryOperation = $this->getLazyQueryOperation();

        return $this->theSession->addLazyOperation(null, $lazyQueryOperation, $onEval);
    }

    public function countLazily(): Lazy
    {
        if ($this->queryOperation == null) {
            $this->_take(0);
            $this->queryOperation = $this->initializeQueryOperation();
        }

        $lazyQueryOperation = new LazyQueryOperation($this->className, $this->theSession, $this->queryOperation, $this->afterQueryExecutedCallback);
        return $this->theSession->addLazyCountOperation($lazyQueryOperation);
    }


    public function _suggestUsing(?SuggestionBase $suggestion): void
    {
        if ($suggestion == null) {
            throw new IllegalArgumentException("suggestion cannot be null");
        }

        $this->assertCanSuggest($suggestion);

        $token = null;

        if ($suggestion instanceof SuggestionWithTerm) {
            /** @var SuggestionWithTerm $term */
            $term = $suggestion;
            $token = SuggestToken::create($term->getField(), $term->getDisplayField(), $this->addQueryParameter($term->getTerm()), $this->getOptionsParameterName($term->getOptions()));
        } else if ($suggestion instanceof SuggestionWithTerms) {
            /** @var SuggestionWithTerms $terms */
            $terms = $suggestion;
            $token = SuggestToken::create($terms->getField(), $terms->getDisplayField(), $this->addQueryParameter($terms->getTerms()), $this->getOptionsParameterName($terms->getOptions()));
        } else {
            throw new UnsupportedOperationException("Unknown type of suggestion: " . get_class($suggestion));
        }

        $this->selectTokens->append($token);
    }

    private function getOptionsParameterName(?SuggestionOptions $options): ?string
    {
        $optionsParameterName = null;
        if ($options != null && $options !== SuggestionOptions::defaultOptions()) {
            $optionsParameterName = $this->addQueryParameter($options);
        }

        return $optionsParameterName;
    }

    private function assertCanSuggest(?SuggestionBase $suggestion): void
    {
        if (!$this->whereTokens->isEmpty()) {
            throw new IllegalStateException("Cannot add suggest when WHERE statements are present.");
        }

        if (!$this->selectTokens->isEmpty()) {
            $lastToken = $this->selectTokens->last();
            if ($lastToken instanceof SuggestToken) {
                $st = $lastToken;
                if ($st->getFieldName() == $suggestion->getField()) {
                    throw new IllegalStateException("Cannot add suggest for the same field again.");
                }
            } else {
                throw new IllegalStateException("Cannot add suggest when SELECT statements are present.");
            }
        }

        if (!$this->orderByTokens->isEmpty()) {
            throw new IllegalStateException("Cannot add suggest when ORDER BY statements are present.");
        }
    }

    protected ?Explanations $explanations = null;

    protected ?ExplanationToken $explanationToken = null;

    public function _includeExplanations(?ExplanationOptions $options, Explanations &$explanationsReference): void
    {
        if ($this->explanationToken != null) {
            throw new IllegalStateException("Duplicate IncludeExplanations method calls are forbidden.");
        }

        $optionsParameterName = $options != null ? $this->addQueryParameter($options) : null;
        $this->explanationToken = ExplanationToken::create($optionsParameterName);
        $this->explanations = $explanationsReference;
    }

    protected ?TimeSeriesIncludesTokenArray $timeSeriesIncludesTokens = null;

    protected ?CounterIncludesTokenArray $counterIncludesTokens = null;

    protected ?CompareExchangeValueIncludesTokenArray $compareExchangeValueIncludesTokens = null;

    protected ?RevisionIncludesTokenArray $revisionsIncludesTokens = null;

    protected function _includeCounters(?string $alias, array $counterToIncludeByDocId): void
    {
        if ($counterToIncludeByDocId == null || empty($counterToIncludeByDocId)) {
            return;
        }

        $this->counterIncludesTokens = new CounterIncludesTokenArray();
        $this->includesAlias = $alias;

        foreach ($counterToIncludeByDocId as $key => $value) {
            if ($value[0]) {
                $this->counterIncludesTokens[] = CounterIncludesToken::all($key);
                continue;
            }

            if ($value[1] == null || empty($value[1])) {
                continue;
            }

            foreach ($value[1] as $name) {
                $this->counterIncludesTokens[] = CounterIncludesToken::create($key, $name);
            }
        }
    }

    private function _includeTimeSeries(?string $alias, ?array $timeSeriesToInclude): void
    {
        if (empty($timeSeriesToInclude)) {
            return;
        }

        $this->timeSeriesIncludesTokens = new TimeSeriesIncludesTokenArray();
        if ($this->includesAlias == null) {
            $this->includesAlias = $alias;
        }

        foreach ($timeSeriesToInclude as $key => $value) {
            /** @var AbstractTimeSeriesRange $range */
            foreach ($value as $range) {
                $this->timeSeriesIncludesTokens[] = TimeSeriesIncludesToken::create($key, $range);
            }
        }
    }

    public function _includeRevisionsByDateTime(DateTime $dateTime): void
    {
        if ($this->revisionsIncludesTokens == null) {
            $this->revisionsIncludesTokens = new RevisionIncludesTokenArray();
        }

        $this->revisionsIncludesTokens[] = RevisionIncludesToken::createFromDateTime($dateTime);
    }

    public function _includeRevisionsByChangeVector(StringArray $revisionsToIncludeByChangeVector): void
    {
        if ($this->revisionsIncludesTokens == null) {
            $this->revisionsIncludesTokens = new RevisionIncludesTokenArray();
        }

        foreach ($revisionsToIncludeByChangeVector as $changeVector) {
            $this->revisionsIncludesTokens[] = RevisionIncludesToken::createFromChangeVector($changeVector);
        }
    }

    public function getParameterPrefix(): string
    {
        return $this->parameterPrefix;
    }

    public function setParameterPrefix(string $parameterPrefix): void
    {
        $this->$parameterPrefix = $parameterPrefix;
    }
}
