<?php

namespace RavenDB\Documents\Session;

use Closure;
use InvalidArgumentException;
use RavenDB\Constants\DocumentsIndexingFields;
use RavenDB\Documents\Queries\Explanation\ExplanationOptions;
use RavenDB\Documents\Queries\Explanation\Explanations;
use RavenDB\Documents\Queries\Facets\AggregationDocumentQuery;
use RavenDB\Documents\Queries\Facets\AggregationDocumentQueryInterface;
use RavenDB\Documents\Queries\Facets\FacetBase;
use RavenDB\Documents\Queries\Facets\FacetBaseArray;
use RavenDB\Documents\Queries\Facets\FacetBuilder;
use RavenDB\Documents\Queries\GroupBy;
use RavenDB\Documents\Queries\Highlighting\HighlightingOptions;
use RavenDB\Documents\Queries\Highlighting\Highlightings;
use RavenDB\Documents\Queries\ProjectionBehavior;
use RavenDB\Documents\Queries\QueryData;
use RavenDB\Documents\Queries\SearchOperator;
use RavenDB\Documents\Queries\Timings\QueryTimings;
use RavenDB\Documents\Session\Loaders\QueryIncludeBuilder;
use RavenDB\Documents\Session\Tokens\DeclareTokenArray;
use RavenDB\Documents\Session\Tokens\FieldsToFetchToken;
use RavenDB\Documents\Session\Tokens\LoadTokenList;
use RavenDB\Documents\Session\Tokens\QueryTokenList;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Parameters;
use RavenDB\Type\Collection;
use RavenDB\Type\Duration;
use RavenDB\Type\StringArray;
use RavenDB\Type\StringSet;
use ReflectionClass;
use ReflectionProperty;

class DocumentQuery extends AbstractDocumentQuery
    implements DocumentQueryInterface, AbstractDocumentQueryImplInterface
{
    public function __construct(
        ?string                           $className,
        InMemoryDocumentSessionOperations $session,
        ?string                           $indexName,
        ?string                           $collectionName,
        bool                              $isGroupBy,
        ?DeclareTokenArray                $declareTokens = null,
        ?LoadTokenList                    $loadTokens = null,
        ?string                           $fromAlias = null,
        bool                              $isProjectInto = false
    )
    {
        parent::__construct($className, $session, $indexName, $collectionName, $isGroupBy, $declareTokens, $loadTokens, $fromAlias, $isProjectInto);
    }

    /**
     * selectFields(string $projectionClass, ?ProjectionBehavior $projectionBehavior = null): DocumentQueryInterface
     *
     * selectFields(string $field, string $projectionClass = null, ProjectionBehavior $projectionBehavior = null): DocumentQueryInterface
     * selectFields(string $field, ProjectionBehavior $projectionBehavior = null): DocumentQueryInterface
     *
     * selectFields(array $fields, ?string $projectionClass = null, ProjectionBehavior $projectionBehavior = null): DocumentQueryInterface
     * selectFields(StringArray $fields, ?string $projectionClass = null, ProjectionBehavior $projectionBehavior = null): DocumentQueryInterface
     *
     * selectFields(QueryData $queryData, ?string $projectionClass = null): DocumentQueryInterface
     *
     * @param mixed ...$params
     *
     * @return DocumentQueryInterface
     */
    public function selectFields(...$params): DocumentQueryInterface
    {
        if (!count($params)) {
            throw new IllegalArgumentException('You must set select fields params.');
        }

        $fields = null;
        $projectionClass = null;
        $projectionBehavior = null;
        $firstParam = $params[0];

        if (is_string($firstParam)) {
            if (count($params) > 1 && ($params[1] instanceof ProjectionBehavior)) {
                $projectionBehavior = $params[1];
            }

            if (class_exists($firstParam)) { // give string is projectionClass
                $projectionClass = $firstParam;
                return $this->_selectFieldsByClass($projectionClass, $projectionBehavior ?? ProjectionBehavior::default());
            }

            if (count($params) > 1 && !($params[1] instanceof ProjectionBehavior) && class_exists($params[1])) {
                $projectionClass = $params[1];

                if (count($params) > 2 && ($params[2] instanceof ProjectionBehavior)) {
                    $projectionBehavior = $params[2];
                }
            }
            $fields = [$firstParam];
        }

        if (is_array($firstParam)) {
            $firstParam = StringArray::fromArray($firstParam);
        }
        if ($firstParam instanceof StringArray) {
            $fields = $firstParam->getArrayCopy();
            $projectionClass = count($params) > 1 && is_string($params[1]) ? $params[1] : null;
            if (count($params) > 2 && ($params[2] instanceof ProjectionBehavior)) {
                $projectionBehavior = $params[2];
            }
        }

        if ($fields != null) {
            if (!$projectionBehavior) {
                $projectionBehavior = ProjectionBehavior::default();
            }

            $stringArray = StringArray::fromArray($fields);
            $queryData = new QueryData($stringArray, $stringArray);
            $queryData->setProjectInto(true);
            $queryData->setProjectionBehavior($projectionBehavior);

            return $this->_selectFieldsByQueryData($queryData, $projectionClass);
        }

        if ($firstParam instanceof QueryData) {
            if (count($params) > 1) {
                $projectionClass = $params[1];
            }

            return $this->_selectFieldsByQueryData($firstParam, $projectionClass);
        }

        throw new IllegalArgumentException('Illegal arguments.');
    }

    private function _selectFieldsByClass(string $projectionClass, ProjectionBehavior $projectionBehavior): DocumentQueryInterface
    {
        $ref = new ReflectionClass(new $projectionClass());
        $allProperties = $ref->getProperties(ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PRIVATE | ReflectionProperty::IS_PROTECTED);

        // !!! extract only getter and setter properties that use default conventions getProperty - setProperty
        $projections = array_filter(
            array_map(function ($item) {
                return $item->name;
            }, $allProperties),
            function ($item) use ($projectionClass) {
                return method_exists($projectionClass, 'set' . ucfirst($item));
            });

        $fields = array_filter(
            array_map(function ($item) {
                return $item->name;
            }, $allProperties),
            function ($item) use ($projectionClass) {
                return method_exists($projectionClass, 'set' . ucfirst($item));
            });

        $queryData = new QueryData($fields, $projections);
        $queryData->setProjectInto(true);
        $queryData->setProjectionBehavior($projectionBehavior);

        return $this->_selectFieldsByQueryData($queryData, $projectionClass);
    }

    private function _selectFieldsByQueryData(QueryData $queryData, ?string $projectionClass = null): DocumentQueryInterface
    {
        if ((count($queryData->getFields()) > 1) && $projectionClass == null) {
            throw new IllegalArgumentException('You must define projectionClass if you select more than one field.');
        }

        if ($projectionClass) {
            if (!class_exists($projectionClass)) {
                throw new IllegalArgumentException('Class ' . $projectionClass . ' does not exists.');
            }
        }

        $queryData->setProjectInto(true);
        return $this->createDocumentQueryInternal($queryData, $projectionClass);
    }

//    @Override
//    public <TTimeSeries> IDocumentQuery<TTimeSeries> selectTimeSeries(Class<TTimeSeries> clazz, Consumer<ITimeSeriesQueryBuilder> timeSeriesQuery) {
//        QueryData queryData = createTimeSeriesQueryData(timeSeriesQuery);
//        return selectFields(clazz, queryData);
//    }
//
    public function distinct(): DocumentQueryInterface
    {
        $this->_distinct();
        return $this;
    }

    public function orderByScore(): DocumentQueryInterface
    {
        $this->_orderByScore();
        return $this;
    }

//    @Override
//    public IDocumentQuery<T> orderByScoreDescending() {
//        _orderByScoreDescending();
//        return this;
//    }

    public function includeExplanations(?ExplanationOptions $options, Explanations &$explanations): DocumentQueryInterface
    {
        $this->_includeExplanations($options, $explanations);
        return $this;
    }

    public function timings(QueryTimings &$timings): DocumentQueryInterface
    {
        $this->_includeTimings($timings);
        return $this;
    }

    public function waitForNonStaleResults(?Duration $waitTimeout = null): DocumentQueryInterface
    {
        $this->_waitForNonStaleResults($waitTimeout);
        return $this;
    }

    public function addParameter(string $name, $value): DocumentQueryInterface
    {
        $this->_addParameter($name, $value);
        return $this;
    }

    public function addOrder(?string $fieldName, bool $descending, ?OrderingType $ordering = null): DocumentQueryInterface
    {
        if ($ordering == null) {
            $ordering = OrderingType::string();
        }

        if ($descending) {
            $this->orderByDescending($fieldName, $ordering);
        } else {
            $this->orderBy($fieldName, $ordering);
        }
        return $this;
    }

//    //TBD expr public IDocumentQuery<T> AddOrder<TValue>(Expression<Func<T, TValue>> propertySelector, bool descending, OrderingType ordering)

    public function addAfterQueryExecutedListener(Closure $action): DocumentQueryInterface
    {
        $this->_addAfterQueryExecutedListener($action);
        return $this;
    }

    public function removeAfterQueryExecutedListener(Closure $action): DocumentQueryInterface
    {
        $this->_removeAfterQueryExecutedListener($action);
        return $this;
    }

    public function addAfterStreamExecutedListener(Closure $action): DocumentQueryInterface
    {
        $this->_addAfterStreamExecutedListener($action);
        return $this;
    }

    public function removeAfterStreamExecutedListener(Closure $action): DocumentQueryInterface
    {
        $this->_removeAfterStreamExecutedListener($action);
        return $this;
    }

    public function openSubclause(): DocumentQueryInterface
    {
        $this->_openSubclause();
        return $this;
    }

    public function closeSubclause(): DocumentQueryInterface
    {
        $this->_closeSubclause();
        return $this;
    }

    public function negateNext(): DocumentQueryInterface
    {
        $this->_negateNext();
        return $this;
    }

    public function search(string $fieldName, string $searchTerms, ?SearchOperator $operator = null): DocumentQueryInterface
    {
        $this->_search($fieldName, $searchTerms, $operator);
        return $this;
    }

    //TBD expr public IDocumentQuery<T> Search<TValue>(Expression<Func<T, TValue>> propertySelector, string searchTerms, SearchOperator @operator)

    public function intersect(): DocumentQueryInterface
    {
        $this->_intersect();
        return $this;
    }

    public function containsAny(?string $fieldName, Collection $values): DocumentQueryInterface
    {
        $this->_containsAny($fieldName, $values);
        return $this;
    }

    //TBD expr public IDocumentQuery<T> ContainsAny<TValue>(Expression<Func<T, TValue>> propertySelector, IEnumerable<TValue> values)

    public function containsAll(?string $fieldName, Collection $values): DocumentQueryInterface
    {
        $this->_containsAll($fieldName, $values);
        return $this;
    }

    //TBD expr public IDocumentQuery<T> ContainsAll<TValue>(Expression<Func<T, TValue>> propertySelector, IEnumerable<TValue> values)

    public function statistics(QueryStatistics &$stats): DocumentQueryInterface
    {
        $this->_statistics($stats);
        return $this;
    }

//    @Override
//    public IDocumentQuery<T> usingDefaultOperator(QueryOperator queryOperator) {
//        _usingDefaultOperator(queryOperator);
//        return this;
//    }

    public function noTracking(): DocumentQueryInterface
    {
        $this->_noTracking();
        return $this;
    }

    public function noCaching(): DocumentQueryInterface
    {
        $this->_noCaching();
        return $this;
    }

    /**
     * @param Callable|string $includes
     * @return DocumentQueryInterface
     */
    public function include($includes): DocumentQueryInterface
    {
        if (is_string($includes)) {
            return $this->includeWithString($includes);
        }

        if (is_callable($includes)) {
            return $this->includeWithCallable($includes);
        }

        throw new InvalidArgumentException('Invalid argument.');
    }


    public function includeWithString(?string $path): DocumentQueryInterface
    {
        $this->_includeWithString($path);
        return $this;
    }

    protected function includeWithCallable(callable $includes): DocumentQueryInterface
    {
        $includeBuilder = new QueryIncludeBuilder($this->getConventions());
        $includes($includeBuilder);
        $this->_includeWithIncludeBuilder($includeBuilder);
        return $this;
    }

    //TBD expr IDocumentQuery<T> IDocumentQueryBase<T, IDocumentQuery<T>>.Include(Expression<Func<T, object>> path)

    public function not(): DocumentQueryInterface
    {
        $this->negateNext();
        return $this;
    }

    public function take(int $count): DocumentQueryInterface
    {
        $this->_take($count);
        return $this;
    }

    public function skip(int $count): DocumentQueryInterface
    {
        $this->_skip($count);
        return $this;
    }

    public function whereLucene(string $fieldName, string $whereClause, bool $exact = false): DocumentQueryInterface
    {
        $this->_whereLucene($fieldName, $whereClause, $exact);
        return $this;
    }

    /**
     * @param string $fieldName
     * @param mixed|MethodCall $value
     * @param bool $exact
     * @return DocumentQueryInterface
     */
    public function whereEquals(string $fieldName, $value, bool $exact = false): DocumentQueryInterface
    {
        $this->_whereEquals($fieldName, $value, $exact);
        return $this;
    }

    /**
     * @param WhereParams $whereParams
     * @return DocumentQueryInterface
     */
    public function whereEqualsWithParams(WhereParams $whereParams): DocumentQueryInterface
    {
        $this->_whereEqualsWithParams($whereParams);
        return $this;
    }

    //TBD expr IDocumentQuery<T> IDocumentQueryBase<T, IDocumentQuery<T>>.WhereEquals<TValue>(Expression<Func<T, TValue>> propertySelector, TValue value, bool exact)
    //TBD expr IDocumentQuery<T> IFilterDocumentQueryBase<T, IDocumentQuery<T>>.WhereEquals<TValue>(Expression<Func<T, TValue>> propertySelector, MethodCall value, bool exact)

    /**
     * @param string $fieldName
     * @param mixed|MethodCall $value
     * @param bool $exact
     * @return DocumentQueryInterface
     */
    public function whereNotEquals(string $fieldName, $value, bool $exact = false): DocumentQueryInterface
    {
        $this->_whereNotEquals($fieldName, $value, $exact);
        return $this;
    }

    /**
     * @param WhereParams $whereParams
     * @return DocumentQueryInterface
     */
    public function whereNotEqualsWithParams(WhereParams $whereParams): DocumentQueryInterface
    {
        $this->_whereNotEqualsWithParams($whereParams);
        return $this;
    }

    //TBD expr IDocumentQuery<T> IDocumentQueryBase<T, IDocumentQuery<T>>.WhereNotEquals<TValue>(Expression<Func<T, TValue>> propertySelector, TValue value, bool exact)
    //TBD expr IDocumentQuery<T> IFilterDocumentQueryBase<T, IDocumentQuery<T>>.WhereNotEquals<TValue>(Expression<Func<T, TValue>> propertySelector, MethodCall value, bool exact)

    public function whereIn(string $fieldName, Collection $values, bool $exact = false): DocumentQueryInterface
    {
        $this->_whereIn($fieldName, $values, $exact);
        return $this;
    }

    //TBD expr public IDocumentQuery<T> WhereIn<TValue>(Expression<Func<T, TValue>> propertySelector, IEnumerable<TValue> values, bool exact = false)

    public function whereStartsWith(string $fieldName, $value, bool $exact = false): DocumentQueryInterface
    {
        $this->_whereStartsWith($fieldName, $value, $exact);
        return $this;
    }

    public function whereEndsWith(string $fieldName, $value, bool $exact = false): DocumentQueryInterface
    {
        $this->_whereEndsWith($fieldName, $value, $exact);
        return $this;
    }

    //TBD expr public IDocumentQuery<T> WhereEndsWith<TValue>(Expression<Func<T, TValue>> propertySelector, TValue value)

    public function whereBetween(string $fieldName, $start, $end, bool $exact = false): DocumentQueryInterface
    {
        $this->_whereBetween($fieldName, $start, $end, $exact);
        return $this;
    }

    //TBD expr public IDocumentQuery<T> WhereBetween<TValue>(Expression<Func<T, TValue>> propertySelector, TValue start, TValue end, bool exact = false)


    public function whereGreaterThan(string $fieldName, $value, bool $exact = false): DocumentQueryInterface
    {
        $this->_whereGreaterThan($fieldName, $value, $exact);
        return $this;
    }

    public function whereGreaterThanOrEqual(string $fieldName, $value, bool $exact = false): DocumentQueryInterface
    {
        $this->_whereGreaterThanOrEqual($fieldName, $value, $exact);
        return $this;
    }

    //TBD expr public IDocumentQuery<T> WhereGreaterThan<TValue>(Expression<Func<T, TValue>> propertySelector, TValue value, bool exact = false)
    //TBD expr public IDocumentQuery<T> WhereGreaterThanOrEqual<TValue>(Expression<Func<T, TValue>> propertySelector, TValue value, bool exact = false)

    public function whereLessThan(string $fieldName, $value, bool $exact = false): DocumentQueryInterface
    {
        $this->_whereLessThan($fieldName, $value, $exact);
        return $this;
    }

    //TBD expr public IDocumentQuery<T> WhereLessThanOrEqual<TValue>(Expression<Func<T, TValue>> propertySelector, TValue value, bool exact = false)

    public function whereLessThanOrEqual(string $fieldName, $value, bool $exact = false): DocumentQueryInterface
    {
        $this->_whereLessThanOrEqual($fieldName, $value, $exact);
        return $this;
    }

    //TBD expr public IDocumentQuery<T> WhereLessThanOrEqual<TValue>(Expression<Func<T, TValue>> propertySelector, TValue value, bool exact = false)
    //TBD expr public IDocumentQuery<T> WhereExists<TValue>(Expression<Func<T, TValue>> propertySelector)

    public function whereExists(string $fieldName): DocumentQueryInterface
    {
        $this->_whereExists($fieldName);
        return $this;
    }

//    //TBD expr IDocumentQuery<T> IFilterDocumentQueryBase<T, IDocumentQuery<T>>.WhereRegex<TValue>(Expression<Func<T, TValue>> propertySelector, string pattern)

    public function whereRegex(?string $fieldName, ?string $pattern): DocumentQueryInterface
    {
        $this->_whereRegex($fieldName, $pattern);
        return $this;
    }

    public function andAlso(bool $wrapPreviousQueryClauses = false): DocumentQueryInterface
    {
        $this->_andAlso($wrapPreviousQueryClauses);
        return $this;
    }

    public function orElse(): DocumentQueryInterface
    {
        $this->_orElse();
        return $this;
    }

    public function boost(float $boost): DocumentQueryInterface
    {
        $this->_boost($boost);
        return $this;
    }

    public function fuzzy(float $fuzzy): DocumentQueryInterface
    {
        $this->_fuzzy($fuzzy);
        return $this;
    }

    public function proximity(int $proximity): DocumentQueryInterface
    {
        $this->_proximity($proximity);
        return $this;
    }

    public function randomOrdering(?string $seed = null): DocumentQueryInterface
    {
        $this->_randomOrdering($seed);
        return $this;
    }

    //TBD 4.1 public IDocumentQuery<T> customSortUsing(String typeName, boolean descending)

    /**
     * @param string|GroupBy $fieldName
     * @param string|GroupBy ...$fieldNames
     */
    public function groupBy($fieldName, ...$fieldNames): GroupByDocumentQueryInterface
    {
        $this->_groupBy($fieldName, ...$fieldNames);
        return new GroupByDocumentQuery($this);
    }

    public function ofType(string $resultClass): DocumentQueryInterface
    {
        return $this->createDocumentQueryInternal(null, $resultClass);
    }

    /**
     * Order the results by the specified fields
     * The fields are the names of the fields to sort, defaulting to sorting by ascending.
     * You can prefix a field name with '-' to indicate sorting by descending or '+' to sort by ascending
     *
     * @param string $field
     * @param OrderingType|string|null $sorterNameOrOrdering
     *
     * @return DocumentQueryInterface
     */
    function orderBy(string $field, $sorterNameOrOrdering = null): DocumentQueryInterface
    {
        $this->_orderBy($field, $sorterNameOrOrdering);
        return $this;
    }

    //TBD expr public IDocumentQuery<T> OrderBy<TValue>(params Expression<Func<T, TValue>>[] propertySelectors)


    /**
     * Order the results by the specified fields
     * The field is the name of the field to sort, defaulting to sorting by descending.
     * @param string $field Field to use in order by
     * @param string|OrderingType|null $sorterNameOrOrdering Sorter to use
     */
    function orderByDescending(string $field, $sorterNameOrOrdering = null): DocumentQueryInterface
    {
        $this->_orderByDescending($field, $sorterNameOrOrdering ?? OrderingType::string());
        return $this;
    }

    //TBD expr public IDocumentQuery<T> OrderByDescending<TValue>(params Expression<Func<T, TValue>>[] propertySelectors)

    /**
     * @param Closure $action
     * @return DocumentQueryInterface
     */
    public function addBeforeQueryExecutedListener(Closure $action): DocumentQueryInterface
    {
        $this->_addBeforeQueryExecutedListener($action);
        return $this;
    }

    /**
     * @param Closure $action
     * @return mixed
     */
    public function removeBeforeQueryExecutedListener(Closure $action): DocumentQueryInterface
    {
        $this->_removeBeforeQueryExecutedListener($action);
        return $this;
    }

    //@todo: check this method - I reversed the properties
    public function createDocumentQueryInternal(?QueryData $queryData = null, ?string $resultClass = null): DocumentQuery
    {
        /** @var ?FieldsToFetchToken $newFieldsToFetch */
        $newFieldsToFetch = null;

        if ($queryData != null && $queryData->getFields()->isNotEmpty()) {
            $fields = $queryData->getFields();

            if (!$this->isGroupBy) {
                $identityProperty = $this->getConventions()->getIdentityProperty($resultClass);

                if ($identityProperty != null) {
                    $fields = [];

                    foreach ($queryData->getFields() as $field) {
                        $fields[] = strcmp($field, $identityProperty) == 0 ? DocumentsIndexingFields::DOCUMENT_ID_FIELD_NAME : $field;
                    }
                }
            }

            $sourceAlias = null;
            self::getSourceAliasIfExists($resultClass, $queryData, $fields, $sourceAlias);
            $newFieldsToFetch = FieldsToFetchToken::create($fields, $queryData->getProjections(), $queryData->isCustomFunction(), $sourceAlias);
        }

        if ($newFieldsToFetch != null) {
            $this->updateFieldsToFetchToken($newFieldsToFetch);
        }

        $query = new DocumentQuery($resultClass,
            $this->theSession,
            $this->getIndexName(),
            $this->getCollectionName(),
            $this->isGroupBy,
            $queryData != null ? $queryData->getDeclareTokens() : null,
            $queryData != null ? $queryData->getLoadTokens() : null,
            $queryData != null ? $queryData->getFromAlias() : null,
            $queryData != null && $queryData->isProjectInto()
        );

        $query->queryRaw = $this->queryRaw;
        $query->pageSize = $this->pageSize;
        $query->selectTokens = new QueryTokenList($this->selectTokens);
        $query->fieldsToFetchToken = $this->fieldsToFetchToken;
        $query->whereTokens = new QueryTokenList($this->whereTokens);
        $query->orderByTokens = new QueryTokenList($this->orderByTokens);
        $query->groupByTokens = new QueryTokenList($this->groupByTokens);
        $query->queryParameters = new Parameters($this->queryParameters);
        $query->start = $this->start;
        $query->timeout = $this->timeout;
        $query->queryStats = $this->queryStats;
        $query->theWaitForNonStaleResults = $this->theWaitForNonStaleResults;
        $query->negate = $this->negate;
        $query->documentIncludes = new StringSet($this->documentIncludes);
        $query->counterIncludesTokens = $this->counterIncludesTokens;
        $query->timeSeriesIncludesTokens = $this->timeSeriesIncludesTokens;
        $query->compareExchangeValueIncludesTokens = $this->compareExchangeValueIncludesTokens;
        $query->rootTypes = StringSet::fromArray([$this->className]);
        $query->beforeQueryExecutedCallback = $this->beforeQueryExecutedCallback;
        $query->afterQueryExecutedCallback = $this->afterQueryExecutedCallback;
        $query->afterStreamExecutedCallback = $this->afterStreamExecutedCallback;
        $query->highlightingTokens = $this->highlightingTokens;
        // @todo: uncomment this
//        $query->queryHighlightings = $this->queryHighlightings;
        $query->disableEntitiesTracking = $this->disableEntitiesTracking;
        $query->disableCaching = $this->disableCaching;
        $query->projectionBehavior = ($queryData != null ? $queryData->getProjectionBehavior() : null) ?? $this->projectionBehavior;
        $query->queryTimings = $this->queryTimings;
        // @todo: uncomment this
//        $query->explanations = $this->explanations;
        $query->explanationToken = $this->explanationToken;
        $query->isIntersect = $this->isIntersect;
        $query->defaultOperator = $this->defaultOperator;

        return $query;
    }


    /**
     * @param Callable|FacetBase $builderOrFacets
     *
     * @return AggregationDocumentQueryInterface
     */
    public function aggregateBy(...$builderOrFacets): AggregationDocumentQueryInterface
    {
        if (count($builderOrFacets) == 0) {
            throw new IllegalArgumentException('You must provide argument.');
        }

        if (is_callable($builderOrFacets[0])) {
            return $this->aggregateByBuilder($builderOrFacets[0]);
        }

        return $this->aggregateByFacets(FacetBaseArray::fromArray($builderOrFacets));
    }

    protected function aggregateByBuilder(callable $builder): AggregationDocumentQueryInterface
    {
        $ff = new FacetBuilder();
        $builder($ff);

        return $this->aggregateByFacets(FacetBaseArray::fromArray([$ff->getFacet()]));
    }

    protected function aggregateByFacets(FacetBaseArray $facets): AggregationDocumentQueryInterface
    {
        foreach ($facets as $facet) {
            $this->_aggregateBy($facet);
        }

        return new AggregationDocumentQuery($this);
    }

    public function aggregateUsing(?string $facetSetupDocumentId): AggregationDocumentQueryInterface
    {
        $this->_aggregateUsing($facetSetupDocumentId);

        return new AggregationDocumentQuery($this);
    }

    public function highlight(?string $fieldName, int $fragmentLength, int $fragmentCount, ?HighlightingOptions $options , Highlightings &$highlightings): DocumentQueryInterface
    {
        $this->_highlight($fieldName, $fragmentLength, $fragmentCount, $options, $highlightings);
        return $this;
    }

    //TBD expr IDocumentQuery<T> IDocumentQueryBase<T, IDocumentQuery<T>>.Highlight(Expression<Func<T, object>> path, int fragmentLength, int fragmentCount, out Highlightings highlightings)
    //TBD expr IDocumentQuery<T> IDocumentQueryBase<T, IDocumentQuery<T>>.Highlight(Expression<Func<T, object>> path, int fragmentLength, int fragmentCount, HighlightingOptions options, out Highlightings highlightings)
    //TBD expr public IDocumentQuery<T> Spatial(Expression<Func<T, object>> path, Func<SpatialCriteriaFactory, SpatialCriteria> clause)

//    @Override
//    public IDocumentQuery<T> spatial(String fieldName, Function<SpatialCriteriaFactory, SpatialCriteria> clause) {
//        SpatialCriteria criteria = clause.apply(SpatialCriteriaFactory.INSTANCE);
//        _spatial(fieldName, criteria);
//        return this;
//    }
//
//    @Override
//    public IDocumentQuery<T> spatial(DynamicSpatialField field, Function<SpatialCriteriaFactory, SpatialCriteria> clause) {
//        SpatialCriteria criteria = clause.apply(SpatialCriteriaFactory.INSTANCE);
//        _spatial(field, criteria);
//        return this;
//    }
//
//    //TBD expr public IDocumentQuery<T> Spatial(Func<SpatialDynamicFieldFactory<T>, DynamicSpatialField> field, Func<SpatialCriteriaFactory, SpatialCriteria> clause)
//    //TBD expr IDocumentQuery<T> IDocumentQueryBase<T, IDocumentQuery<T>>.WithinRadiusOf<TValue>(Expression<Func<T, TValue>> propertySelector, double radius, double latitude, double longitude, SpatialUnits? radiusUnits, double distanceErrorPct)
//
//    @Override
//    public IDocumentQuery<T> withinRadiusOf(String fieldName, double radius, double latitude, double longitude) {
//        return withinRadiusOf(fieldName, radius, latitude, longitude, null, Constants.Documents.Indexing.Spatial.DEFAULT_DISTANCE_ERROR_PCT);
//    }
//
//    @Override
//    public IDocumentQuery<T> withinRadiusOf(String fieldName, double radius, double latitude, double longitude, SpatialUnits radiusUnits) {
//        return withinRadiusOf(fieldName, radius, latitude, longitude, radiusUnits, Constants.Documents.Indexing.Spatial.DEFAULT_DISTANCE_ERROR_PCT);
//    }
//
//    @Override
//    public IDocumentQuery<T> withinRadiusOf(String fieldName, double radius, double latitude, double longitude, SpatialUnits radiusUnits, double distanceErrorPct) {
//        _withinRadiusOf(fieldName, radius, latitude, longitude, radiusUnits, distanceErrorPct);
//        return this;
//    }
//
//    //TBD expr IDocumentQuery<T> IDocumentQueryBase<T, IDocumentQuery<T>>.RelatesToShape<TValue>(Expression<Func<T, TValue>> propertySelector, string shapeWkt, SpatialRelation relation, double distanceErrorPct)
//
//    @Override
//    public IDocumentQuery<T> relatesToShape(String fieldName, String shapeWkt, SpatialRelation relation) {
//        return relatesToShape(fieldName, shapeWkt, relation, Constants.Documents.Indexing.Spatial.DEFAULT_DISTANCE_ERROR_PCT);
//    }
//
//    @Override
//    public IDocumentQuery<T> relatesToShape(String fieldName, String shapeWkt, SpatialRelation relation, double distanceErrorPct) {
//        _spatial(fieldName, shapeWkt, relation, null, distanceErrorPct);
//        return this;
//    }
//
//    @Override
//    public IDocumentQuery<T> relatesToShape(String fieldName, String shapeWkt, SpatialRelation relation, SpatialUnits units, double distanceErrorPct) {
//        _spatial(fieldName, shapeWkt, relation, units, distanceErrorPct);
//        return this;
//    }

//    public IDocumentQuery<T> orderByDistance(DynamicSpatialField field, double latitude, double longitude) {
//        _orderByDistance(field, latitude, longitude);
//        return this;
//    }
//
//    //TBD expr IDocumentQuery<T> IDocumentQueryBase<T, IDocumentQuery<T>>.OrderByDistance(Func<DynamicSpatialFieldFactory<T>, DynamicSpatialField> field, double latitude, double longitude)
//
//    @Override
//    public IDocumentQuery<T> orderByDistance(DynamicSpatialField field, String shapeWkt) {
//        _orderByDistance(field, shapeWkt);
//        return this;
//    }
//
//    //TBD expr IDocumentQuery<T> IDocumentQueryBase<T, IDocumentQuery<T>>.OrderByDistance(Func<DynamicSpatialFieldFactory<T>, DynamicSpatialField> field, string shapeWkt)
//
//
//    //TBD expr IDocumentQuery<T> IDocumentQueryBase<T, IDocumentQuery<T>>.OrderByDistance<TValue>(Expression<Func<T, TValue>> propertySelector, double latitude, double longitude)
//
//    @Override
//    public IDocumentQuery<T> orderByDistance(String fieldName, double latitude, double longitude) {
//        orderByDistance(fieldName, latitude, longitude, 0);
//        return this;
//    }
//
//    @Override
//    public IDocumentQuery<T> orderByDistance(String fieldName, double latitude, double longitude, double roundFactor) {
//        _orderByDistance(fieldName, latitude, longitude, roundFactor);
//        return this;
//    }
//
//    //TBD expr IDocumentQuery<T> IDocumentQueryBase<T, IDocumentQuery<T>>.OrderByDistance<TValue>(Expression<Func<T, TValue>> propertySelector, string shapeWkt)
//
//    @Override
//    public IDocumentQuery<T> orderByDistance(String fieldName, String shapeWkt) {
//        _orderByDistance(fieldName, shapeWkt);
//        return this;
//    }
//
//    @Override
//    public IDocumentQuery<T> orderByDistanceDescending(DynamicSpatialField field, double latitude, double longitude) {
//        _orderByDistanceDescending(field, latitude, longitude);
//        return this;
//    }
//
//    //TBD expr IDocumentQuery<T> IDocumentQueryBase<T, IDocumentQuery<T>>.OrderByDistanceDescending(Func<DynamicSpatialFieldFactory<T>, DynamicSpatialField> field, double latitude, double longitude)
//
//    @Override
//    public IDocumentQuery<T> orderByDistanceDescending(DynamicSpatialField field, String shapeWkt) {
//        _orderByDistanceDescending(field, shapeWkt);
//        return this;
//    }
//
//    //TBD expr IDocumentQuery<T> IDocumentQueryBase<T, IDocumentQuery<T>>.OrderByDistanceDescending(Func<DynamicSpatialFieldFactory<T>, DynamicSpatialField> field, string shapeWkt)
//
//    //TBD expr IDocumentQuery<T> IDocumentQueryBase<T, IDocumentQuery<T>>.OrderByDistanceDescending<TValue>(Expression<Func<T, TValue>> propertySelector, double latitude, double longitude)
//
//    @Override
//    public IDocumentQuery<T> orderByDistanceDescending(String fieldName, double latitude, double longitude) {
//        return orderByDistanceDescending(fieldName, latitude, longitude, 0);
//    }
//
//    @Override
//    public IDocumentQuery<T> orderByDistanceDescending(String fieldName, double latitude, double longitude, double roundFactor) {
//        _orderByDistanceDescending(fieldName, latitude, longitude, roundFactor);
//        return this;
//    }
//
//    //TBD expr IDocumentQuery<T> IDocumentQueryBase<T, IDocumentQuery<T>>.OrderByDistanceDescending<TValue>(Expression<Func<T, TValue>> propertySelector, string shapeWkt)
//
//    @Override
//    public IDocumentQuery<T> orderByDistanceDescending(String fieldName, String shapeWkt) {
//        _orderByDistanceDescending(fieldName, shapeWkt);
//        return this;
//    }
//
//    @Override
//    public IDocumentQuery<T> moreLikeThis(MoreLikeThisBase moreLikeThis) {
//        try (MoreLikeThisScope mlt = _moreLikeThis()) {
//            mlt.withOptions(moreLikeThis.getOptions());
//
//            if (moreLikeThis instanceof MoreLikeThisUsingDocument) {
//                mlt.withDocument(((MoreLikeThisUsingDocument) moreLikeThis).getDocumentJson());
//            }
//        }
//
//        return this;
//    }
//
//    @SuppressWarnings("unchecked")
//    @Override
//    public IDocumentQuery<T> moreLikeThis(Consumer<IMoreLikeThisBuilderForDocumentQuery<T>> builder) {
//        MoreLikeThisBuilder<T> f = new MoreLikeThisBuilder<>();
//        builder.accept(f);
//
//        try (MoreLikeThisScope moreLikeThis = _moreLikeThis()) {
//            moreLikeThis.withOptions(f.getMoreLikeThis().getOptions());
//
//            if (f.getMoreLikeThis() instanceof MoreLikeThisUsingDocument) {
//                moreLikeThis.withDocument(((MoreLikeThisUsingDocument) f.getMoreLikeThis()).getDocumentJson());
//            } else if (f.getMoreLikeThis() instanceof MoreLikeThisUsingDocumentForDocumentQuery) {
//                ((MoreLikeThisUsingDocumentForDocumentQuery) f.getMoreLikeThis()).getForDocumentQuery().accept(this);
//            }
//        }
//
//        return this;
//    }
//
//    @Override
//    public ISuggestionDocumentQuery<T> suggestUsing(SuggestionBase suggestion) {
//        _suggestUsing(suggestion);
//        return new SuggestionDocumentQuery<>(this);
//    }
//
//    @Override
//    public ISuggestionDocumentQuery<T> suggestUsing(Consumer<ISuggestionBuilder<T>> builder) {
//        SuggestionBuilder<T> f = new SuggestionBuilder<>();
//        builder.accept(f);
//
//        suggestUsing(f.getSuggestion());
//        return new SuggestionDocumentQuery<>(this);
//    }
}
