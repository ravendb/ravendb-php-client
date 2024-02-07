<?php

namespace RavenDB\Documents\Session;

use RavenDB\Type\Duration;

interface AbstractDocumentQueryInterface
{
//  String getIndexName();
//
//    String getCollectionName();
//
//    /**
//     * Gets the document convention from the query session
//     * @return document conventions
//     */
//    DocumentConventions getConventions();

    /**
     * Determines if it is a dynamic map-reduce query
     * @return bool true if it is dynamic query
     */
    function isDynamicMapReduce(): bool;

    /**
     * Instruct the query to wait for non stale result for the specified wait timeout.
     * @param Duration $waitTimeout Wait timeout
     */
    function _waitForNonStaleResults(?Duration $waitTimeout = null);

//    /**
//     * Gets the fields for projection
//     * @return list of projection fields
//     */
//    List<String> getProjectionFields();
//
//    /**
//     * Order the search results randomly
//     */
//    void _randomOrdering();
//
//    /**
//     * Order the search results randomly using the specified seed
//     * this is useful if you want to have repeatable random queries
//     * @param seed Seed to use
//     */
//    void _randomOrdering(String seed);
//
//    //TBD 4.1 void _customSortUsing(String typeName);
//
//    //TBD 4.1 void _customSortUsing(String typeName, boolean descending);
//
//    /**
//     * Includes the specified path in the query, loading the document specified in that path
//     * @param path include path
//     */
//    void _include(String path);
//
//    /**
//     * Includes the specified documents and/or counters in the query, specified by IncludeBuilder
//     * @param includes builder
//     */
//    void _include(IncludeBuilderBase includes);
//
//    // TBD expr linq void Include(Expression<Func<T, object>> path);

    /**
     * Takes the specified count.
     * @param int $count Items to take
     */
    function _take(int $count): void;

    /**
     * Skips the specified count.
     * @param int $count Items to skip
     */
    function _skip(int $count): void;

    function _whereEquals(string $fieldName, $value, bool $exact = false): void;

    function _whereEqualsWithParams(WhereParams $whereParams): void;

    public function _whereNotEquals(string $fieldName, $value, bool $exact = false): void;

    public function _whereNotEqualsWithParams(WhereParams $whereParams): void;

    /**
     * Simplified method for opening a new clause within the query
     */
    public function _openSubclause(): void;

    /**
     * Simplified method for closing a clause within the query
     */
    public function _closeSubclause(): void;

    /**
     * Negate the next operation
     */
    public function _negateNext(): void;

//    /**
//     * Check that the field has one of the specified value
//     * @param fieldName Field name
//     * @param values Values to match
//     */
//    void _whereIn(String fieldName, Collection< ? > values);
//
//    /**
//     * Check that the field has one of the specified value
//     * @param fieldName Field name
//     * @param values Values to match
//     * @param exact Use exact matcher
//     */
//    void _whereIn(String fieldName, Collection< ? > values, boolean exact);

    /**
     * Matches fields which starts with the specified value.
     * @param string $fieldName Field name
     * @param mixed $value to match
     * @param bool $exact Use exact matcher
     */
    function _whereStartsWith(string $fieldName, $value, bool $exact = false): void;

    /**
     * Matches fields which ends with the specified value.
     * @param string $fieldName Field name
     * @param mixed $value Value to match
     * @param bool $exact Use exact matcher
     */
    function _whereEndsWith(string $fieldName, $value, bool $exact): void;

    /**
     * Matches fields where the value is between the specified start and end, inclusive
     * @param string $fieldName Field name
     * @param mixed $start Range start
     * @param mixed $end Range end
     * @param bool $exact Use exact matcher
     */
    function _whereBetween(string $fieldName, mixed $start, mixed $end, bool $exact = false): void;

    /**
     * Matches fields where the value is greater than the specified value
     */
    public function _whereGreaterThan(string $fieldName, $value, bool $exact = false): void;

    /**
     * Matches fields where the value is greater than or equal to the specified value
     */
    public function _whereGreaterThanOrEqual(string $fieldName, $value, bool $exact = false): void;

    /**
     * Matches fields where the value is less than the specified value
     */
    public function _whereLessThan(string $fieldName, $value, bool $exact = false): void;

    /**
     * Matches fields where the value is less than or equal to the specified value
     */
    public function _whereLessThanOrEqual(string $fieldName, $value, bool $exact = false): void;
//    void _whereExists(String fieldName);
//
//    void _whereRegex(String fieldName, String pattern);

    /**
     * Add an AND to the query
     */
    public function _andAlso(bool $wrapPreviousQueryClauses = false): void;

    /**
     * Add an OR to the query
     */
    public function _orElse(): void;
//
//    /**
//     * Specifies a boost weight to the last where clause.
//     * The higher the boost factor, the more relevant the term will be.
//     *
//     * boosting factor where 1.0 is default, less than 1.0 is lower weight, greater than 1.0 is higher weight
//     *
//     * http://lucene.apache.org/java/2_4_0/queryparsersyntax.html#Boosting%20a%20Term
//     * @param boost Boost value
//     */
//    void _boost(double boost);
//
//    /**
//     * Specifies a fuzziness factor to the single word term in the last where clause
//     *
//     * 0.0 to 1.0 where 1.0 means closer match
//     *
//     * http://lucene.apache.org/java/2_4_0/queryparsersyntax.html#Fuzzy%20Searches
//     *
//     * @param fuzzy Fuzzy value
//     */
//    void _fuzzy(double fuzzy);
//
//    /**
//     * Specifies a proximity distance for the phrase in the last search clause
//     *
//     * http://lucene.apache.org/java/2_4_0/queryparsersyntax.html#Proximity%20Searches
//     * @param proximity Proximity value
//     */
//    void _proximity(int proximity);
//
//    /**
//     * Order the results by the specified fields
//     * The field is the name of the field to sort, defaulting to sorting by ascending.
//     * @param field Field to use
//     */
//    void _orderBy(String field);
//
//    /**
//     * Order the results by the specified fields
//     * The field is the name of the field to sort using sorterName.
//     * @param field Field to use
//     * @param sorterName Sorter name
//     */
//    void _orderBy(String field, String sorterName);
//
//    /**
//     * Order the results by the specified fields
//     * The field is the name of the field to sort, defaulting to sorting by ascending.
//     * @param field Field to use
//     * @param ordering Ordering type
//     */
//    void _orderBy(String field, OrderingType ordering);
//
//    void _orderByDescending(String field);
//
//    void _orderByDescending(String field, String sorterName);
//
//    void _orderByDescending(String field, OrderingType ordering);
//
//    void _orderByScore();
//
//    void _orderByScoreDescending();
//
//    void _highlight(String fieldName, int fragmentLength, int fragmentCount, HighlightingOptions options, Reference<Highlightings> highlightings);
//
//    /**
//     * Perform a search for documents which fields that match the searchTerms.
//     * If there is more than a single term, each of them will be checked independently.
//     * @param fieldName Field name
//     * @param searchTerms Search terms
//     */
//    void _search(String fieldName, String searchTerms);
//
//    /**
//     * Perform a search for documents which fields that match the searchTerms.
//     * If there is more than a single term, each of them will be checked independently.
//     * @param fieldName Field name
//     * @param searchTerms Search terms
//     * @param operator Operator
//     */
//    void _search(String fieldName, String searchTerms, SearchOperator operator);
//
//    String toString();
//
//    void _intersect();
//
//    void _addRootType(Class clazz);
//
//    void _distinct();
//
//    /**
//     * Performs a query matching ANY of the provided values against the given field (OR)
//     * @param fieldName Field name
//     * @param values Values to match
//     */
//    void _containsAny(String fieldName, Collection< ? > values);
//
//    /**
//     * Performs a query matching ALL of the provided values against the given field (AND)
//     * @param fieldName Field name
//     * @param values Values to match
//     * @param values Values to match
//     */
//    void _containsAll(String fieldName, Collection<? > values);
//
//    void _groupBy(String fieldName, String... fieldNames);
//
//    void _groupBy(GroupBy field, GroupBy... fields);
//
//    void _groupByKey(String fieldName);
//
//    void _groupByKey(String fieldName, String projectedName);
//
//    void _groupBySum(String fieldName);
//
//    void _groupBySum(String fieldName, String projectedName);
//
//    void _groupByCount();
//
//    void _groupByCount(String projectedName);
//
//    void _whereTrue();
//
//    void _spatial(DynamicSpatialField field, SpatialCriteria criteria);
//
//    void _spatial(String fieldName, SpatialCriteria criteria);
//
//    void _orderByDistance(DynamicSpatialField field, double latitude, double longitude);
//
//    void _orderByDistance(String fieldName, double latitude, double longitude);
//
//    void _orderByDistance(String fieldName, double latitude, double longitude, double roundFactor);
//
//    void _orderByDistance(DynamicSpatialField field, String shapeWkt);
//
//    void _orderByDistance(String fieldName, String shapeWkt);
//
//    void _orderByDistance(String fieldName, String shapeWkt, double roundFactor);
//
//    void _orderByDistanceDescending(DynamicSpatialField field, double latitude, double longitude);
//
//    void _orderByDistanceDescending(String fieldName, double latitude, double longitude);
//
//    void _orderByDistanceDescending(String fieldName, double latitude, double longitude, double roundFactor);
//
//    void _orderByDistanceDescending(DynamicSpatialField field, String shapeWkt);
//
//    void _orderByDistanceDescending(String fieldName, String shapeWkt);
//
//    void _orderByDistanceDescending(String fieldName, String shapeWkt, double roundFactor);
//
//    void _aggregateBy(FacetBase facet);
//
//    void _aggregateUsing(String facetSetupDocumentId);
//
//    MoreLikeThisScope _moreLikeThis();
//
//    String addAliasToIncludesTokens(String fromAlias);
//
//    void _suggestUsing(SuggestionBase suggestion);
//
//    String getParameterPrefix();
//
//    void setParameterPrefix(String prefix);
//
//    Iterator<T> iterator();
}
