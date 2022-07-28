<?php

namespace RavenDB\Documents\Session;

interface DocumentQueryBaseInterface extends QueryBaseInterface, FilterDocumentQueryBaseInterface
{
//    /**
//     * Adds an ordering for a specific field to the query
//     * @param fieldName Field name
//     * @param descending use descending order
//     * @return Query instance
//     */
//    TSelf addOrder(String fieldName, boolean descending);
//
//    /**
//     * Adds an ordering for a specific field to the query
//     * @param fieldName Field name
//     * @param descending use descending order
//     * @param ordering ordering type
//     * @return Query instance
//     */
//    TSelf addOrder(String fieldName, boolean descending, OrderingType ordering);
//
//    //TBD expr TSelf AddOrder<TValue>(Expression<Func<T, TValue>> propertySelector, bool descending = false, OrderingType ordering = OrderingType.String);
//
//    /**
//     * Specifies a boost weight to the last where clause.
//     * The higher the boost factor, the more relevant the term will be.
//     *
//     * boosting factor where 1.0 is default, less than 1.0 is lower weight, greater than 1.0 is higher weight
//     *
//     * http://lucene.apache.org/java/2_4_0/queryparsersyntax.html#Boosting%20a%20Term
//     * @param boost Boost value
//     * @return Query instance
//     */
//    TSelf boost(double boost);

    /**
     * Apply distinct operation to this query
     */
    function distinct();

//    /**
//     * Adds explanations of scores calculated for queried documents to the query result
//     * @param explanations Output parameter
//     * @return Query instance
//     */
//    TSelf includeExplanations(Reference<Explanations> explanations);
//
//    /**
//     * Adds explanations of scores calculated for queried documents to the query result
//     * @param options Options
//     * @param explanations Output parameter
//     * @return Query instance
//     */
//    TSelf includeExplanations(ExplanationOptions options, Reference<Explanations> explanations);
//
    /**
     * Specifies a fuzziness factor to the single word term in the last where clause
     * 0.0 to 1.0 where 1.0 means closer match
     *
     * http://lucene.apache.org/java/2_4_0/queryparsersyntax.html#Fuzzy%20Searches
     * @param float $fuzzy Fuzzy value
     * @return QueryBaseInterface Query instance
     */
    function fuzzy(float $fuzzy): QueryBaseInterface;

//    TSelf highlight(String fieldName, int fragmentLength, int fragmentCount, Reference<Highlightings> highlightings);
//    TSelf highlight(String fieldName, int fragmentLength, int fragmentCount, HighlightingOptions options, Reference<Highlightings> highlightings);
//    //TBD expr TSelf Highlight(Expression<Func<T, object>> path, int fragmentLength, int fragmentCount, out Highlightings highlightings);
//    //TBD expr TSelf Highlight(Expression<Func<T, object>> path, int fragmentLength, int fragmentCount, HighlightingOptions options, out Highlightings highlightings);

    /**
     * Includes the specified path in the query, loading the document specified in that path
     * @param Callable|string $includes Path to include
     */
    function include($includes);

    //TBD expr TSelf Include(Expression<Func<T, object>> path);

    /**
     * Partition the query so we can intersect different parts of the query
     *  across different index entries.
     *
     *  @return static Query instance
     */
    public function intersect();

    /**
     * Order the results by the specified fields
     * The fields are the names of the fields to sort, defaulting to sorting by ascending.
     * You can prefix a field name with '-' to indicate sorting by descending or '+' to sort by ascending
     *
     * @param string $field
     * @param OrderingType|string|null $sorterNameOrOrdering
     */
    function orderBy(string $field, $sorterNameOrOrdering = null);

    //TBD expr TSelf OrderBy<TValue>(params Expression<Func<T, TValue>>[] propertySelectors);
    //TBD expr TSelf OrderBy<TValue>(Expression<Func<T, TValue>> propertySelector, string sorterName);

    /**
     * Order the results by the specified fields
     * The field is the name of the field to sort, defaulting to sorting by descending.
     * @param string $field Field to use in order by
     * @param string|OrderingType|null $sorterNameOrOrdering Sorter to use
     */
    function orderByDescending(string $field, $sorterNameOrOrdering = null);

    //TBD expr TSelf OrderByDescending<TValue>(params Expression<Func<T, TValue>>[] propertySelectors);
    //TBD expr TSelf OrderByDescending<TValue>(Expression<Func<T, TValue>> propertySelector, string sorterName);

//    /**
//     * Adds an ordering by score for a specific field to the query
//     * @return Query instance
//     */
//    TSelf orderByScore();
//
//    /**
//     * Adds an ordering by score for a specific field to the query
//     * @return Query instance
//     */
//    TSelf orderByScoreDescending();

    /**
     * Specifies a proximity distance for the phrase in the last search clause
     * http://lucene.apache.org/java/2_4_0/queryparsersyntax.html#Proximity%20Searches
     * @param int $proximity Proximity value
     * @return QueryBaseInterface Query instance
     */
    function proximity(int $proximity): QueryBaseInterface;

    /**
     * Order the search results randomly using the specified seed
     * this is useful if you want to have repeatable random queries
     * @param ?string $seed Seed to use
     */
    function randomOrdering(?string $seed = null);

    //TBD 4.1 TSelf customSortUsing(String typeName, boolean descending);

//    /**
//     * Sorts the query results by distance.
//     * @param field Field to use in order by
//     * @param latitude Latitude
//     * @param longitude Longitude
//     * @return Query instance
//     */
//    TSelf orderByDistance(DynamicSpatialField field, double latitude, double longitude);
//
//    //TBD expr TSelf OrderByDistance(Func<DynamicSpatialFieldFactory<T>, DynamicSpatialField> field, double latitude, double longitude);
//
//    TSelf orderByDistance(DynamicSpatialField field, String shapeWkt);
//
//    //TBD expr TSelf OrderByDistance(Func<DynamicSpatialFieldFactory<T>, DynamicSpatialField> field, string shapeWkt);
//
//    //TBD expr  TSelf OrderByDistance<TValue>(Expression<Func<T, TValue>> propertySelector, double latitude, double longitude);
//
//    /**
//     * Sorts the query results by distance.
//     * @param fieldName Field name to use in order by
//     * @param latitude Latitude
//     * @param longitude Longitude
//     * @return Query instance
//     */
//    TSelf orderByDistance(String fieldName, double latitude, double longitude);
//
//    /**
//     * Sorts the query results by distance.
//     * @param fieldName Field name to use in order by
//     * @param latitude Latitude
//     * @param longitude Longitude
//     * @param roundFactor Round factor
//     * @return Query instance
//     */
//    TSelf orderByDistance(String fieldName, double latitude, double longitude, double roundFactor);
//
//
//    //TBD expr TSelf OrderByDistance<TValue>(Expression<Func<T, TValue>> propertySelector, string shapeWkt);
//
//    /**
//     * Sorts the query results by distance.
//     * @param fieldName Field name to use in order by
//     * @param shapeWkt WKT shape to use
//     * @return Query instance
//     */
//    TSelf orderByDistance(String fieldName, String shapeWkt);
//
//    /**
//     * Sorts the query results by distance.
//     * @param field Field to use in order by
//     * @param latitude Latitude
//     * @param longitude Longitude
//     * @return Query instance
//     */
//    TSelf orderByDistanceDescending(DynamicSpatialField field, double latitude, double longitude);
//
//    //TBD expr TSelf OrderByDistanceDescending(Func<DynamicSpatialFieldFactory<T>, DynamicSpatialField> field, double latitude, double longitude);
//
//    TSelf orderByDistanceDescending(DynamicSpatialField field, String shapeWkt);
//
//    //TBD expr TSelf OrderByDistanceDescending(Func<DynamicSpatialFieldFactory<T>, DynamicSpatialField> field, string shapeWkt);
//
//    //TBD expr TSelf OrderByDistanceDescending<TValue>(Expression<Func<T, TValue>> propertySelector, double latitude, double longitude);
//
//    /**
//     * Sorts the query results by distance.
//     * @param fieldName Field name to use in order by
//     * @param latitude Latitude
//     * @param longitude Longitude
//     * @return Query instance
//     */
//    TSelf orderByDistanceDescending(String fieldName, double latitude, double longitude);
//
//    /**
//     * Sorts the query results by distance.
//     * @param fieldName Field name to use in order by
//     * @param latitude Latitude
//     * @param longitude Longitude
//     * @param roundFactor Round factor
//     * @return Query instance
//     */
//    TSelf orderByDistanceDescending(String fieldName, double latitude, double longitude, double roundFactor);
//
//    //TBD expr TSelf OrderByDistanceDescending<TValue>(Expression<Func<T, TValue>> propertySelector, string shapeWkt);
//
//    /**
//     * Sorts the query results by distance.
//     * @param fieldName Field name to use
//     * @param shapeWkt WKT shape to use
//     * @return Query instance
//     */
//    TSelf orderByDistanceDescending(String fieldName, String shapeWkt);
}
