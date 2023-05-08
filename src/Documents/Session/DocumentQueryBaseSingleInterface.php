<?php

namespace RavenDB\Documents\Session;

use Closure;
use RavenDB\Documents\Lazy;

interface DocumentQueryBaseSingleInterface
{
    /**
     * Register the query as a lazy-count query in the session and return a lazy
     * instance that will evaluate the query only when needed.
     * @return Lazy count for query
     */
    function countLazily(): Lazy;

    /**
     * Returns first element or throws if sequence is empty.
     *
     * @return mixed first result
     */
    function first(): mixed;

    /**
     * Returns first element or default value for type if sequence is empty.
     *
     * @return mixed first result of default
     */
    function firstOrDefault(): mixed;

    /**
     * Returns first element or throws if sequence is empty or contains more than one element.
     *
     * @return mixed single result or throws
     */
    function single(): mixed;

    /**
     * Returns first element or default value for given type if sequence is empty. Throws if sequence contains more than
     * one element.
     *
     * @return mixed single result, default or throws
     */
    function singleOrDefault(): mixed;


//    /**
//     * Checks if the given query matches any records
//     * @return true if the given query matches any records
//     */
//    boolean any();

    /**
     * Gets the total count of records for this query
     *
     * @return int total count of records
     */
    function count(): int;

    /**
     * Gets the total count of records for this query as long
     *
     * @return int total count of records (as long)
     */
    function longCount(): int;

    /**
     * Register the query as a lazy query in the session and return a lazy
     * instance that will evaluate the query only when needed.
     * Also provide a function to execute when the value is evaluated
     * @param ?Closure $onEval Action to be executed on evaluation.
     * @return Lazy query result
     */
    function lazily(?Closure $onEval = null): Lazy;
}
