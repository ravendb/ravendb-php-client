<?php

namespace RavenDB\Documents\Session;

use Closure;
use RavenDB\Constants\DocumentsIndexingSpatial;
use RavenDB\Documents\Indexes\Spatial\SpatialRelation;
use RavenDB\Documents\Indexes\Spatial\SpatialUnits;
use RavenDB\Documents\Queries\SearchOperator;
use RavenDB\Documents\Queries\Spatial\DynamicSpatialField;
use RavenDB\Type\Collection;

interface FilterDocumentQueryBaseInterface extends QueryBaseInterface
{
    /**
     * Negate the next operation
     *
     * @return FilterDocumentQueryBaseInterface
     */
    function not(): FilterDocumentQueryBaseInterface;

    /**
     * Add an AND to the query
     * @param bool $wrapPreviousQueryClauses wrap previous query clauses
     *
     * @return FilterDocumentQueryBaseInterface
     */
    public function andAlso(bool $wrapPreviousQueryClauses = false): FilterDocumentQueryBaseInterface;

    /**
     * Simplified method for closing a clause within the query
     *
     * @return FilterDocumentQueryBaseInterface
     */
    function closeSubclause(): FilterDocumentQueryBaseInterface;

    /**
     * Performs a query matching ALL of the provided values against the given field (AND)
     * @param ?string $fieldName Field name
     * @param Collection|array $values values to match
     *
     * @return FilterDocumentQueryBaseInterface
     */
    function containsAll(?string $fieldName, Collection|array $values): FilterDocumentQueryBaseInterface;

    //TBD expr TSelf ContainsAll<TValue>(Expression<Func<T, TValue>> propertySelector, IEnumerable<TValue> values);

    /**
     * Performs a query matching ANY of the provided values against the given field (OR)
     * @param ?string $fieldName Field name
     * @param Collection|array $values values to match
     *
     * @return FilterDocumentQueryBaseInterface
     */
    function containsAny(?string $fieldName, Collection|array $values): FilterDocumentQueryBaseInterface;

    //TBD expr TSelf ContainsAny<TValue>(Expression<Func<T, TValue>> propertySelector, IEnumerable<TValue> values);

    /**
     * Negate the next operation
     *
     * @return FilterDocumentQueryBaseInterface
     */
    function negateNext(): FilterDocumentQueryBaseInterface;

    /**
     *  Simplified method for opening a new clause within the query
     *
     * @return FilterDocumentQueryBaseInterface
     */
    function openSubclause(): FilterDocumentQueryBaseInterface;

    /**
     * Add an OR to the query
     *
     * @return FilterDocumentQueryBaseInterface
     */
    public function orElse(): FilterDocumentQueryBaseInterface;

    /**
     * Perform a search for documents which fields that match the searchTerms.
     * If there is more than a single term, each of them will be checked independently.
     *
     * Space separated terms e.g. 'John Adam' means that we will look in selected field for 'John'
     * or 'Adam'.
     * @param string $fieldName Field name
     * @param string $searchTerms Search terms
     * @param ?SearchOperator $operator Search operator
     *
     * @return FilterDocumentQueryBaseInterface
     */
    public function search(string $fieldName, string $searchTerms, ?SearchOperator $operator = null): QueryBaseInterface;

    //TBD expr TSelf Search<TValue>(Expression<Func<T, TValue>> propertySelector, string searchTerms, SearchOperator @operator = SearchOperator.Or);

    /**
     * Filter the results from the index using the specified where clause.
     * @param string $fieldName Field name
     * @param string $whereClause Where clause
     * @param bool $exact Use exact matcher
     *
     * @return FilterDocumentQueryBaseInterface
     */
    function whereLucene(string $fieldName, string $whereClause, bool $exact = false): FilterDocumentQueryBaseInterface;

    /**
     * Matches fields where the value is between the specified start and end, inclusive
     * @param string $fieldName Field name
     * @param mixed $start Range start
     * @param mixed $end Range end
     * @param bool $exact Use exact matcher
     *
     * @return FilterDocumentQueryBaseInterface
     */
    function whereBetween(string $fieldName, $start, $end, bool $exact = false): FilterDocumentQueryBaseInterface;

    //TBD expr TSelf WhereBetween<TValue>(Expression<Func<T, TValue>> propertySelector, TValue start, TValue end, bool exact = false);

    /**
     * Matches fields which ends with the specified value.
     * @param string $fieldName Field name
     * @param mixed $value Value to use
     * @param bool $exact Use exact matcher
     *
     * @return FilterDocumentQueryBaseInterface
     */
    function whereEndsWith(string $fieldName, $value, bool $exact = false): FilterDocumentQueryBaseInterface;

    //TBD expr TSelf WhereEndsWith<TValue>(Expression<Func<T, TValue>> propertySelector, TValue value);

    /**
     * @param string $fieldName
     * @param mixed|MethodCall $value
     * @param bool $exact
     *
     * @return FilterDocumentQueryBaseInterface
     */
    function whereEquals(string $fieldName, $value, bool $exact = false): FilterDocumentQueryBaseInterface;

    //TBD expr TSelf WhereEquals<TValue>(Expression<Func<T, TValue>> propertySelector, TValue value, bool exact = false);
    //TBD expr TSelf WhereEquals<TValue>(Expression<Func<T, TValue>> propertySelector, MethodCall value, bool exact = false);

    /**
     * @param WhereParams $whereParams
     *
     * @return FilterDocumentQueryBaseInterface
     */
    function whereEqualsWithParams(WhereParams $whereParams): FilterDocumentQueryBaseInterface;

    /**
     * @param string $fieldName
     * @param mixed|MethodCall $value
     * @param bool $exact
     *
     * @return FilterDocumentQueryBaseInterface
     */
    function whereNotEquals(string $fieldName, $value, bool $exact = false): FilterDocumentQueryBaseInterface;

    //TBD expr TSelf WhereNotEquals<TValue>(Expression<Func<T, TValue>> propertySelector, TValue value, bool exact = false);
    //TBD expr TSelf WhereNotEquals<TValue>(Expression<Func<T, TValue>> propertySelector, MethodCall value, bool exact = false);

    /**
     * @param WhereParams $whereParams
     *
     * @return FilterDocumentQueryBaseInterface
     */
    function whereNotEqualsWithParams(WhereParams $whereParams): FilterDocumentQueryBaseInterface;

    /**
     * Matches fields where the value is greater than the specified value
     * @param string $fieldName Field name
     * @param mixed $value Value to use
     * @param bool $exact Use exact matcher
     *
     * @return FilterDocumentQueryBaseInterface
     */
    function whereGreaterThan(string $fieldName, $value, bool $exact = false): FilterDocumentQueryBaseInterface;

//    //TBD expr TSelf WhereGreaterThan<TValue>(Expression<Func<T, TValue>> propertySelector, TValue value, bool exact = false);


    /**
     * Matches fields where the value is greater than or equal to the specified value
     * @param string $fieldName Field name
     * @param mixed $value Value to use
     * @param bool $exact Use exact matcher
     *
     * @return FilterDocumentQueryBaseInterface
     */
    function whereGreaterThanOrEqual(string $fieldName, $value, bool $exact = false): FilterDocumentQueryBaseInterface;

    //TBD expr TSelf WhereGreaterThanOrEqual<TValue>(Expression<Func<T, TValue>> propertySelector, TValue value, bool exact = false);

    /**
     * Check that the field has one of the specified values
     * @param string $fieldName Field name
     * @param Collection|array $values Values to use
     * @param bool $exact Use exact matcher
     *
     * @return FilterDocumentQueryBaseInterface
     */
    function whereIn(string $fieldName, Collection|array $values, bool $exact = false): FilterDocumentQueryBaseInterface;

    //TBD expr TSelf WhereIn<TValue>(Expression<Func<T, TValue>> propertySelector, IEnumerable<TValue> values, bool exact = false);

    /**
     * Matches fields where the value is less than the specified value
     * @param string $fieldName Field name
     * @param mixed $value Value to use
     * @param bool $exact Use exact matcher
     *
     * @return FilterDocumentQueryBaseInterface
     */
    function whereLessThan(string $fieldName, $value, bool $exact = false): FilterDocumentQueryBaseInterface;

    //TBD expr TSelf WhereLessThan<TValue>(Expression<Func<T, TValue>> propertySelector, TValue value, bool exact = false);

    /**
     *  Matches fields where the value is less than or equal to the specified value
     * @param string $fieldName Field name
     * @param mixed $value Value to use
     * @param bool $exact Use exact matcher
     *
     * @return FilterDocumentQueryBaseInterface
     */
    function whereLessThanOrEqual(string $fieldName, $value, bool $exact = false): FilterDocumentQueryBaseInterface;

    //TBD expr TSelf WhereLessThanOrEqual<TValue>(Expression<Func<T, TValue>> propertySelector, TValue value, bool exact = false);

    /**
     * Matches fields which starts with the specified value.
     * @param string $fieldName Field name
     * @param mixed $value Value to use
     * @param bool $exact Use exact matcher
     *
     * @return FilterDocumentQueryBaseInterface
     */
    function whereStartsWith(string $fieldName, $value, bool $exact = false): FilterDocumentQueryBaseInterface;

    //TBD expr TSelf WhereStartsWith<TValue>(Expression<Func<T, TValue>> propertySelector, TValue value);

    //TBD expr TSelf WhereExists<TValue>(Expression<Func<T, TValue>> propertySelector);

    /**
     * Check if the given field exists
     * @param string $fieldName Field name
     *
     * @return FilterDocumentQueryBaseInterface
     */
    function whereExists(string $fieldName): FilterDocumentQueryBaseInterface;

    //TBD expr TSelf WhereRegex<TValue>(Expression<Func<T, TValue>> propertySelector, string pattern);

    /**
     * Checks value of a given field against supplied regular expression pattern
     * @param ?string $fieldName Field name
     * @param ?string $pattern Regexp pattern
     * @return FilterDocumentQueryBaseInterface Query instance
     */
    function whereRegex(?string $fieldName, ?string $pattern): FilterDocumentQueryBaseInterface;

    //TBD expr TSelf WithinRadiusOf<TValue>(Expression<Func<T, TValue>> propertySelector, double radius, double latitude, double longitude, SpatialUnits? radiusUnits = null, double distanceErrorPct = Constants.Documents.Indexing.Spatial.DefaultDistanceErrorPct);

    /**
     * Filter matches to be inside the specified radius
     * @param string|null $fieldName
     * @param float $radius
     * @param float $latitude
     * @param float $longitude
     * @param SpatialUnits|null $radiusUnits
     * @param float $distanceErrorPct
     * @return DocumentQueryInterface instance
     */
    public function withinRadiusOf(?string $fieldName, float $radius, float $latitude, float $longitude, ?SpatialUnits $radiusUnits = null, float $distanceErrorPct = DocumentsIndexingSpatial::DEFAULT_DISTANCE_ERROR_PCT): DocumentQueryInterface;

    //TBD expr TSelf RelatesToShape<TValue>(Expression<Func<T, TValue>> propertySelector, string shapeWkt, SpatialRelation relation, double distanceErrorPct = Constants.Documents.Indexing.Spatial.DefaultDistanceErrorPct);

    /**
     * Filter matches based on a given shape - only documents with the shape defined in fieldName that
     * have a relation rel with the given shapeWkt will be returned
     * @param string|null $fieldName
     * @param string|null $shapeWkt
     * @param SpatialRelation|null $relation
     * @param SpatialUnits|null $units
     * @param float $distanceErrorPct
     * @return DocumentQueryInterface instance
     */
    function relatesToShape(?string $fieldName, ?string $shapeWkt, ?SpatialRelation $relation, ?SpatialUnits $units = null, float $distanceErrorPct = DocumentsIndexingSpatial::DEFAULT_DISTANCE_ERROR_PCT): DocumentQueryInterface;

    //TBD expr IDocumentQuery<T> Spatial(Expression<Func<T, object>> path, Func<SpatialCriteriaFactory, SpatialCriteria> clause);

    /**
     * Ability to use one factory to determine spatial shape that will be used in query.
     * @param string|DynamicSpatialField $field Spatial field
     * @param Closure $clause Spatial criteria factory
     * @return DocumentQueryInterface Query instance
     */
    public function spatial(string|DynamicSpatialField $field, Closure $clause): DocumentQueryInterface;

    //TBD expr IDocumentQuery<T> spatial(Function<SpatialDynamicFieldFactory<T>, DynamicSpatialField> field, Function<SpatialCriteriaFactory, SpatialCriteria> clause);
}
