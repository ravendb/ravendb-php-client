<?php

namespace RavenDB\Documents\Session;

use RavenDB\Documents\Queries\SearchOperator;
use RavenDB\Documents\Session\Tokens\DeclareTokenArray;
use RavenDB\Documents\Session\Tokens\LoadTokenList;

class DocumentQuery extends AbstractDocumentQuery
    implements DocumentQueryInterface, AbstractDocumentQueryImplInterface
{
    public function __construct(
        string $className,
        InMemoryDocumentSessionOperations $session,
        ?string $indexName,
        ?string $collectionName,
        bool $isGroupBy,
        ?DeclareTokenArray $declareTokens = null,
        ?LoadTokenList $loadTokens = null,
        ?string $fromAlias = null,
        bool $isProjectInto = false
    ) {
        parent::__construct($className, $session, $indexName, $collectionName, $isGroupBy, $declareTokens, $loadTokens, $fromAlias, $isProjectInto);
    }

//    public <TProjection> IDocumentQuery<TProjection> selectFields(Class<TProjection> projectionClass) {
//        return selectFields(projectionClass, ProjectionBehavior.DEFAULT);
//    }
//
//    @Override
//    public <TProjection> IDocumentQuery<TProjection> selectFields(Class<TProjection> projectionClass, ProjectionBehavior projectionBehavior) {
//        try {
//            PropertyDescriptor[] propertyDescriptors = Introspector.getBeanInfo(projectionClass).getPropertyDescriptors();
//
//            String[] projections = Arrays.stream(propertyDescriptors)
//                    .filter(x -> !Object.class.equals(x.getReadMethod().getDeclaringClass())) // ignore class field etc,
//                    .map(x -> x.getName())
//                    .toArray(String[]::new);
//
//            String[] fields = Arrays.stream(propertyDescriptors)
//                    .filter(x -> !Object.class.equals(x.getReadMethod().getDeclaringClass())) // ignore class field etc,
//                    .map(x -> x.getName())
//                    .toArray(String[]::new);
//
//
//            QueryData queryData = new QueryData(fields, projections);
//            queryData.setProjectInto(true);
//            queryData.setProjectionBehavior(projectionBehavior);
//            return selectFields(projectionClass, queryData);
//        } catch (IntrospectionException e) {
//            throw new RuntimeException("Unable to project to class: " + projectionClass.getName() + e.getMessage(), e);
//        }
//    }
//
//    @Override
//    public <TTimeSeries> IDocumentQuery<TTimeSeries> selectTimeSeries(Class<TTimeSeries> clazz, Consumer<ITimeSeriesQueryBuilder> timeSeriesQuery) {
//        QueryData queryData = createTimeSeriesQueryData(timeSeriesQuery);
//        return selectFields(clazz, queryData);
//    }
//
//    @Override
//    public IDocumentQuery<T> distinct() {
//        _distinct();
//        return this;
//    }
//
//    @Override
//    public IDocumentQuery<T> orderByScore() {
//        _orderByScore();
//        return this;
//    }
//
//    @Override
//    public IDocumentQuery<T> orderByScoreDescending() {
//        _orderByScoreDescending();
//        return this;
//    }
//
//    @Override
//    public IDocumentQuery<T> includeExplanations(Reference<Explanations> explanations) {
//        _includeExplanations(null, explanations);
//        return this;
//    }
//
//    @Override
//    public IDocumentQuery<T> includeExplanations(ExplanationOptions options, Reference<Explanations> explanations) {
//        _includeExplanations(options, explanations);
//        return this;
//    }
//
//    @Override
//    public IDocumentQuery<T> timings(Reference<QueryTimings> timings) {
//        _includeTimings(timings);
//        return this;
//    }
//
//    @Override
//    public <TProjection> IDocumentQuery<TProjection> selectFields(Class<TProjection> projectionClass, String... fields) {
//        return selectFields(projectionClass, ProjectionBehavior.DEFAULT, fields);
//    }
//
//    @Override
//    public <TProjection> IDocumentQuery<TProjection> selectFields(Class<TProjection> projectionClass, ProjectionBehavior projectionBehavior, String... fields) {
//        QueryData queryData = new QueryData(fields, fields);
//        queryData.setProjectInto(true);
//        queryData.setProjectionBehavior(projectionBehavior);
//
//        IDocumentQuery<TProjection> selectFields = selectFields(projectionClass, queryData);
//        return selectFields;
//    }
//
//    @Override
//    public <TProjection> IDocumentQuery<TProjection> selectFields(Class<TProjection> projectionClass, QueryData queryData) {
//        queryData.setProjectInto(true);
//        return createDocumentQueryInternal(projectionClass, queryData);
//    }
//
//    @Override
//    public IDocumentQuery<T> waitForNonStaleResults() {
//        _waitForNonStaleResults(null);
//        return this;
//    }
//
//    @Override
//    public IDocumentQuery<T> waitForNonStaleResults(Duration waitTimeout) {
//        _waitForNonStaleResults(waitTimeout);
//        return this;
//    }
//
//    @Override
//    public IDocumentQuery<T> addParameter(String name, Object value) {
//        _addParameter(name, value);
//        return this;
//    }
//
//    @Override
//    public IDocumentQuery<T> addOrder(String fieldName, boolean descending) {
//        return addOrder(fieldName, descending, OrderingType.STRING);
//    }
//
//    @Override
//    public IDocumentQuery<T> addOrder(String fieldName, boolean descending, OrderingType ordering) {
//        if (descending) {
//            orderByDescending(fieldName, ordering);
//        } else {
//            orderBy(fieldName, ordering);
//        }
//        return this;
//    }
//
//    //TBD expr public IDocumentQuery<T> AddOrder<TValue>(Expression<Func<T, TValue>> propertySelector, bool descending, OrderingType ordering)
//
//
//    @Override
//    public IDocumentQuery<T> addAfterQueryExecutedListener(Consumer<QueryResult> action) {
//        _addAfterQueryExecutedListener(action);
//        return this;
//    }
//
//    @Override
//    public IDocumentQuery<T> removeAfterQueryExecutedListener(Consumer<QueryResult> action) {
//        _removeAfterQueryExecutedListener(action);
//        return this;
//    }
//
//    @Override
//    public IDocumentQuery<T> addAfterStreamExecutedListener(Consumer<ObjectNode> action) {
//        _addAfterStreamExecutedListener(action);
//        return this;
//    }
//
//    @Override
//    public IDocumentQuery<T> removeAfterStreamExecutedListener(Consumer<ObjectNode> action) {
//        _removeAfterStreamExecutedListener(action);
//        return this;
//    }
//
//    public IDocumentQuery<T> openSubclause() {
//        _openSubclause();
//        return this;
//    }
//
//    @Override
//    public IDocumentQuery<T> closeSubclause() {
//        _closeSubclause();
//        return this;
//    }
//

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

//    @Override
//    public IDocumentQuery<T> intersect() {
//        _intersect();
//        return this;
//    }
//
//    @Override
//    public IDocumentQuery<T> containsAny(String fieldName, Collection< ? > values) {
//        _containsAny(fieldName, values);
//        return this;
//    }
//
//    //TBD expr public IDocumentQuery<T> ContainsAny<TValue>(Expression<Func<T, TValue>> propertySelector, IEnumerable<TValue> values)
//
//    @Override
//    public IDocumentQuery<T> containsAll(String fieldName, Collection< ? > values) {
//        _containsAll(fieldName, values);
//        return this;
//    }
//
//    //TBD expr public IDocumentQuery<T> ContainsAll<TValue>(Expression<Func<T, TValue>> propertySelector, IEnumerable<TValue> values)
//
//    @Override
//    public IDocumentQuery<T> statistics(Reference<QueryStatistics> stats) {
//        _statistics(stats);
//        return this;
//    }
//
//    @Override
//    public IDocumentQuery<T> usingDefaultOperator(QueryOperator queryOperator) {
//        _usingDefaultOperator(queryOperator);
//        return this;
//    }
//
//    @Override
//    public IDocumentQuery<T> noTracking() {
//        _noTracking();
//        return this;
//    }
//
//    @Override
//    public IDocumentQuery<T> noCaching() {
//        _noCaching();
//        return this;
//    }
//
//    @Override
//    public IDocumentQuery<T> include(String path) {
//        _include(path);
//        return this;
//    }
//
//    @Override
//    public IDocumentQuery<T> include(Consumer<IQueryIncludeBuilder> includes) {
//        QueryIncludeBuilder includeBuilder = new QueryIncludeBuilder(getConventions());
//        includes.accept(includeBuilder);
//        _include(includeBuilder);
//        return this;
//    }
//
//    //TBD expr IDocumentQuery<T> IDocumentQueryBase<T, IDocumentQuery<T>>.Include(Expression<Func<T, object>> path)
//

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

//    @Override
//    public IDocumentQuery<T> whereIn(String fieldName, Collection< ? > values) {
//        return whereIn(fieldName, values, false);
//    }
//
//    @Override
//    public IDocumentQuery<T> whereIn(String fieldName, Collection< ? > values, boolean exact) {
//        _whereIn(fieldName, values, exact);
//        return this;
//    }

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

    public function whereLessThan(string $fieldName, $value, bool $exact): DocumentQueryInterface
    {
        $this->_whereLessThan($fieldName, $value, $exact);
        return $this;
    }

    //TBD expr public IDocumentQuery<T> WhereLessThanOrEqual<TValue>(Expression<Func<T, TValue>> propertySelector, TValue value, bool exact = false)

    public function whereLessThanOrEqual(string $fieldName, $value, bool $exact): DocumentQueryInterface
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

    public function whereRegex(string $fieldName, string $pattern): DocumentQueryInterface
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

//    @Override
//    public IDocumentQuery<T> boost(double boost) {
//        _boost(boost);
//        return this;
//    }
//
//    @Override
//    public IDocumentQuery<T> fuzzy(double fuzzy) {
//        _fuzzy(fuzzy);
//        return this;
//    }
//
//    @Override
//    public IDocumentQuery<T> proximity(int proximity) {
//        _proximity(proximity);
//        return this;
//    }

    public function randomOrdering(?string $seed = null): DocumentQueryInterface
    {
        $this->_randomOrdering($seed);
        return $this;
    }

    //TBD 4.1 public IDocumentQuery<T> customSortUsing(String typeName, boolean descending)

//    @Override
//    public IGroupByDocumentQuery<T> groupBy(String fieldName, String... fieldNames) {
//        _groupBy(fieldName, fieldNames);
//
//        return new GroupByDocumentQuery<>(this);
//    }
//
//    @Override
//    public IGroupByDocumentQuery<T> groupBy(GroupBy field, GroupBy... fields) {
//        _groupBy(field, fields);
//
//        return new GroupByDocumentQuery<>(this);
//    }
//
//    @Override
//    public <TResult> IDocumentQuery<TResult> ofType(Class<TResult> tResultClass) {
//        return createDocumentQueryInternal(tResultClass);
//    }

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

//    @Override
//    public IDocumentQuery<T> orderByDescending(String field, String sorterName) {
//        _orderByDescending(field, sorterName);
//        return this;
//    }
//
//    public IDocumentQuery<T> orderByDescending(String field) {
//        return orderByDescending(field, OrderingType.STRING);
//    }
//
//    public IDocumentQuery<T> orderByDescending(String field, OrderingType ordering) {
//        _orderByDescending(field, ordering);
//        return this;
//    }
//
//    //TBD expr public IDocumentQuery<T> OrderByDescending<TValue>(params Expression<Func<T, TValue>>[] propertySelectors)
//
//    @Override
//    public IDocumentQuery<T> addBeforeQueryExecutedListener(Consumer<IndexQuery> action) {
//        _addBeforeQueryExecutedListener(action);
//        return this;
//    }
//
//    @Override
//    public IDocumentQuery<T> removeBeforeQueryExecutedListener(Consumer<IndexQuery> action) {
//        _removeBeforeQueryExecutedListener(action);
//        return this;
//    }
//
//    public <TResult> DocumentQuery<TResult> createDocumentQueryInternal(Class<TResult> resultClass) {
//        return createDocumentQueryInternal(resultClass, null);
//    }
//
//    @SuppressWarnings("unchecked")
//    public <TResult> DocumentQuery<TResult> createDocumentQueryInternal(Class<TResult> resultClass, QueryData queryData) {
//        FieldsToFetchToken newFieldsToFetch;
//
//        if (queryData != null && queryData.getFields().length > 0) {
//            String[] fields = queryData.getFields();
//
//            if (!isGroupBy) {
//                Field identityProperty = getConventions().getIdentityProperty(resultClass);
//
//                if (identityProperty != null) {
//                    fields = Arrays.stream(queryData.getFields())
//                            .map(p -> p.equals(identityProperty.getName()) ? Constants.Documents.Indexing.Fields.DOCUMENT_ID_FIELD_NAME : p)
//                            .toArray(String[]::new);
//                }
//            }
//
//            Reference<String> sourceAliasReference = new Reference<>();
//            getSourceAliasIfExists(resultClass, queryData, fields, sourceAliasReference);
//            newFieldsToFetch = FieldsToFetchToken.create(fields, queryData.getProjections(), queryData.isCustomFunction(), sourceAliasReference.value);
//        } else {
//            newFieldsToFetch = null;
//        }
//
//        if (newFieldsToFetch != null) {
//            updateFieldsToFetchToken(newFieldsToFetch);
//        }
//
//        DocumentQuery query = new DocumentQuery<>(resultClass,
//                theSession,
//                getIndexName(),
//                getCollectionName(),
//                isGroupBy,
//                queryData != null ? queryData.getDeclareTokens() : null,
//                queryData != null ? queryData.getLoadTokens() : null,
//                queryData != null ? queryData.getFromAlias() : null,
//                queryData != null ? queryData.isProjectInto() : null);
//
//        query.queryRaw = queryRaw;
//        query.pageSize = pageSize;
//        query.selectTokens = new LinkedList<>(selectTokens);
//        query.fieldsToFetchToken = fieldsToFetchToken;
//        query.whereTokens = new LinkedList<>(whereTokens);
//        query.orderByTokens = new LinkedList<>(orderByTokens);
//        query.groupByTokens = new LinkedList<>(groupByTokens);
//        query.queryParameters = new Parameters(queryParameters);
//        query.start = start;
//        query.timeout = timeout;
//        query.queryStats = queryStats;
//        query.theWaitForNonStaleResults = theWaitForNonStaleResults;
//        query.negate = negate;
//        query.documentIncludes = new HashSet<>(documentIncludes);
//        query.counterIncludesTokens = counterIncludesTokens;
//        query.timeSeriesIncludesTokens = timeSeriesIncludesTokens;
//        query.compareExchangeValueIncludesTokens = compareExchangeValueIncludesTokens;
//        query.rootTypes = Sets.newHashSet(clazz);
//        query.beforeQueryExecutedCallback = beforeQueryExecutedCallback;
//        query.afterQueryExecutedCallback = afterQueryExecutedCallback;
//        query.afterStreamExecutedCallback = afterStreamExecutedCallback;
//        query.highlightingTokens = highlightingTokens;
//        query.queryHighlightings = queryHighlightings;
//        query.disableEntitiesTracking = disableEntitiesTracking;
//        query.disableCaching = disableCaching;
//        query.projectionBehavior = ObjectUtils.firstNonNull(queryData != null ? queryData.getProjectionBehavior() : null, projectionBehavior);
//        query.queryTimings = queryTimings;
//        query.explanations = explanations;
//        query.explanationToken = explanationToken;
//        query.isIntersect = isIntersect;
//        query.defaultOperator = defaultOperator;
//
//        return query;
//    }
//
//    @Override
//    public IAggregationDocumentQuery<T> aggregateBy(Consumer<IFacetBuilder<T>> builder) {
//        FacetBuilder<T> ff = new FacetBuilder<>();
//        builder.accept(ff);
//
//        return aggregateBy(ff.getFacet());
//    }
//
//    @Override
//    public IAggregationDocumentQuery<T> aggregateBy(FacetBase facet) {
//        _aggregateBy(facet);
//
//        return new AggregationDocumentQuery<>(this);
//    }
//
//    @Override
//    public IAggregationDocumentQuery<T> aggregateBy(FacetBase... facets) {
//        for (FacetBase facet : facets) {
//            _aggregateBy(facet);
//        }
//
//        return new AggregationDocumentQuery<>(this);
//    }
//
//    @Override
//    public IAggregationDocumentQuery<T> aggregateUsing(String facetSetupDocumentId) {
//        _aggregateUsing(facetSetupDocumentId);
//
//        return new AggregationDocumentQuery<>(this);
//    }
//
//    @Override
//    public IDocumentQuery<T> highlight(String fieldName, int fragmentLength, int fragmentCount, Reference<Highlightings> highlightings) {
//        _highlight(fieldName, fragmentLength, fragmentCount, null, highlightings);
//        return this;
//    }
//
//    @Override
//    public IDocumentQuery<T> highlight(String fieldName, int fragmentLength, int fragmentCount, HighlightingOptions options, Reference<Highlightings> highlightings) {
//        _highlight(fieldName, fragmentLength, fragmentCount, options, highlightings);
//        return this;
//    }
//
//    //TBD expr IDocumentQuery<T> IDocumentQueryBase<T, IDocumentQuery<T>>.Highlight(Expression<Func<T, object>> path, int fragmentLength, int fragmentCount, out Highlightings highlightings)
//    //TBD expr IDocumentQuery<T> IDocumentQueryBase<T, IDocumentQuery<T>>.Highlight(Expression<Func<T, object>> path, int fragmentLength, int fragmentCount, HighlightingOptions options, out Highlightings highlightings)
//    //TBD expr public IDocumentQuery<T> Spatial(Expression<Func<T, object>> path, Func<SpatialCriteriaFactory, SpatialCriteria> clause)
//
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
//
//    @Override
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
