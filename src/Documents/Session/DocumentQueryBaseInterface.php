<?php

namespace RavenDB\Documents\Session;

use RavenDB\Documents\Queries\Explanation\ExplanationOptions;
use RavenDB\Documents\Queries\Explanation\Explanations;
use RavenDB\Documents\Queries\Highlighting\HighlightingOptions;
use RavenDB\Documents\Queries\Highlighting\Highlightings;
use RavenDB\Documents\Queries\Spatial\DynamicSpatialField;

interface DocumentQueryBaseInterface extends QueryBaseInterface, FilterDocumentQueryBaseInterface
{
    /**
     * Adds an ordering for a specific field to the query
     * @param ?string $fieldName Field name
     * @param bool $descending use descending order
     * @param ?OrderingType $ordering ordering type
     * @return DocumentQueryBaseInterface Query instance
     */
    function addOrder(?string $fieldName, bool $descending, ?OrderingType $ordering = null): DocumentQueryBaseInterface;

//    //TBD expr TSelf AddOrder<TValue>(Expression<Func<T, TValue>> propertySelector, bool descending = false, OrderingType ordering = OrderingType.String);


    /**
     * Specifies a boost weight to the last where clause.
     * The higher the boost factor, the more relevant the term will be.
     *
     * boosting factor where 1.0 is default, less than 1.0 is lower weight, greater than 1.0 is higher weight
     *
     * http://lucene.apache.org/java/2_4_0/queryparsersyntax.html#Boosting%20a%20Term
     * @param float $boost Boost value
     * @return DocumentQueryBaseInterface instance
     */
    function boost(float $boost): DocumentQueryBaseInterface;

    /**
     * Apply distinct operation to this query
     *
     * @return DocumentQueryBaseInterface
     */
    function distinct(): DocumentQueryBaseInterface;

//    /**
//     * Adds explanations of scores calculated for queried documents to the query result
//     * @param explanations Output parameter
//     * @return Query instance
//     */
//    TSelf includeExplanations(Reference<Explanations> explanations);
//
    /**
     * Adds explanations of scores calculated for queried documents to the query result
     * @param null|ExplanationOptions $options Options
     * @param Explanations $explanations Output parameter
     * @return DocumentQueryBaseInterface Query instance
     */
    public function includeExplanations(?ExplanationOptions $options, Explanations &$explanations): DocumentQueryBaseInterface;

    /**
     * Specifies a fuzziness factor to the single word term in the last where clause
     * 0.0 to 1.0 where 1.0 means closer match
     *
     * http://lucene.apache.org/java/2_4_0/queryparsersyntax.html#Fuzzy%20Searches
     * @param float $fuzzy Fuzzy value
     *
     * @return DocumentQueryBaseInterface Query instance
     */
    function fuzzy(float $fuzzy): DocumentQueryBaseInterface;

    public function highlight(?string $fieldName, int $fragmentLength, int $fragmentCount, ?HighlightingOptions $options , Highlightings &$highlightings): DocumentQueryBaseInterface;
//    //TBD expr TSelf Highlight(Expression<Func<T, object>> path, int fragmentLength, int fragmentCount, out Highlightings highlightings);
//    //TBD expr TSelf Highlight(Expression<Func<T, object>> path, int fragmentLength, int fragmentCount, HighlightingOptions options, out Highlightings highlightings);

    /**
     * Includes the specified path in the query, loading the document specified in that path
     * @param Callable|string $includes Path to include
     *
     * @return DocumentQueryBaseInterface
     */
    function include($includes): DocumentQueryBaseInterface;

    //TBD expr TSelf Include(Expression<Func<T, object>> path);

    /**
     * Partition the query so we can intersect different parts of the query
     *  across different index entries.
     *
     *  @return DocumentQueryBaseInterface Query instance
     */
    public function intersect(): DocumentQueryBaseInterface;

    /**
     * Order the results by the specified fields
     * The fields are the names of the fields to sort, defaulting to sorting by ascending.
     * You can prefix a field name with '-' to indicate sorting by descending or '+' to sort by ascending
     *
     * @param string $field
     * @param OrderingType|string|null $sorterNameOrOrdering
     *
     * @return DocumentQueryBaseInterface
     */
    function orderBy(string $field, $sorterNameOrOrdering = null): DocumentQueryBaseInterface;

    //TBD expr TSelf OrderBy<TValue>(params Expression<Func<T, TValue>>[] propertySelectors);
    //TBD expr TSelf OrderBy<TValue>(Expression<Func<T, TValue>> propertySelector, string sorterName);

    /**
     * Order the results by the specified fields
     * The field is the name of the field to sort, defaulting to sorting by descending.
     * @param string $field Field to use in order by
     * @param string|OrderingType|null $sorterNameOrOrdering Sorter to use
     *
     * @return DocumentQueryBaseInterface
     */
    function orderByDescending(string $field, $sorterNameOrOrdering = null): DocumentQueryBaseInterface;

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
     *
     * @return DocumentQueryBaseInterface Query instance
     */
    function proximity(int $proximity): DocumentQueryBaseInterface;

    /**
     * Order the search results randomly using the specified seed
     * this is useful if you want to have repeatable random queries
     * @param ?string $seed Seed to use
     *
     * @return DocumentQueryBaseInterface
     */
    function randomOrdering(?string $seed = null): DocumentQueryBaseInterface;

    //TBD 4.1 TSelf customSortUsing(String typeName, boolean descending);

    /**
     * Sorts the query results by distance.
     * @param DynamicSpatialField|string $field
     * @param float|string $latitudeOrShapeWkt
     * @param float|null $longitude
     * @param float $roundFactor
     * @return DocumentQueryInterface Query instance
     */
    function orderByDistance(DynamicSpatialField|string $field, float|string $latitudeOrShapeWkt, ?float $longitude = null, float $roundFactor = 0): DocumentQueryInterface;

    //TBD expr TSelf OrderByDistance(Func<DynamicSpatialFieldFactory<T>, DynamicSpatialField> field, double latitude, double longitude);

    //TBD expr TSelf OrderByDistance(Func<DynamicSpatialFieldFactory<T>, DynamicSpatialField> field, string shapeWkt);

    //TBD expr  TSelf OrderByDistance<TValue>(Expression<Func<T, TValue>> propertySelector, double latitude, double longitude);

    //TBD expr TSelf OrderByDistance<TValue>(Expression<Func<T, TValue>> propertySelector, string shapeWkt);

    /**
     * Sorts the query results by distance.
     * @param DynamicSpatialField|string $field
     * @param float|string $latitudeOrShapeWkt
     * @param float|null $longitude
     * @param float $roundFactor
     * @return DocumentQueryInterface instance
     */
    function orderByDistanceDescending(DynamicSpatialField|string $field, float|string $latitudeOrShapeWkt, ?float $longitude = null, float $roundFactor = 0): DocumentQueryInterface;

    //TBD expr TSelf OrderByDistanceDescending(Func<DynamicSpatialFieldFactory<T>, DynamicSpatialField> field, double latitude, double longitude);

    //TBD expr TSelf OrderByDistanceDescending(Func<DynamicSpatialFieldFactory<T>, DynamicSpatialField> field, string shapeWkt);

    //TBD expr TSelf OrderByDistanceDescending<TValue>(Expression<Func<T, TValue>> propertySelector, double latitude, double longitude);

    //TBD expr TSelf OrderByDistanceDescending<TValue>(Expression<Func<T, TValue>> propertySelector, string shapeWkt);
}
