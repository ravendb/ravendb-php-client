<?php

namespace RavenDB\Documents\Session\Operations\Lazy;

use Closure;
use RavenDB\Documents\Lazy;
use RavenDB\Documents\Session\Loaders\LazyLoaderWithIncludeInterface;

interface LazySessionOperationsInterface
{
    /**
     * Begin a load while including the specified path
     * @param ?string $path Path in documents in which server should look for a 'referenced' documents.
     * @return LazyLoaderWithIncludeInterface Lazy loader with includes support
     */
    function include(?string $path): LazyLoaderWithIncludeInterface;

    //TBD expr ILazyLoaderWithInclude<TResult> Include<TResult>(Expression<Func<TResult, string>> path);

    //TBD expr ILazyLoaderWithInclude<TResult> Include<TResult>(Expression<Func<TResult, IEnumerable<string>>> path);

    /**
     * Loads the specified entity/entities with the specified id.
     *
     * @param string|null $className Result class
     * @param string|array $ids Identifier of an entity/entities that will be loaded.
     * @param Closure|null $onEval Action to be executed on evaluation.
     * @return Lazy
     */
    public function load(?string $className, string|array $ids, ?Closure $onEval = null): Lazy;

    /**
     * Loads multiple entities that contain common prefix.
     *
     * @param string|null $className Result class
     * @param string|null $idPrefix prefix for which documents should be returned e.g. "products/"
     * @param string|null $matches pipe ('|') separated values for which document IDs (after 'idPrefix') should be matched ('?' any single character, '*' any characters)
     * @param int $start number of documents that should be skipped. By default: 0.
     * @param int $pageSize maximum number of documents that will be retrieved. By default: 25.
     * @param string|null $exclude pipe ('|') separated values for which document IDs (after 'idPrefix') should not be matched ('?' any single character, '*' any characters)
     * @param string|null $startAfter skip document fetching until given ID is found and return documents after that ID (default: null)
     * @return Lazy Lazy map of results
     */
    public function loadStartingWith(?string $className, ?string $idPrefix, ?string $matches = null, int $start = 0, int $pageSize = 25, ?string $exclude = null, ?string $startAfter = null): Lazy;


//    /**
//     * Loads the specified entity with the specified id and changeVector.
//     * If the entity is loaded into the session, the tracked entity will be returned otherwise the entity will be loaded only if it is fresher then the provided changeVector.
//     * @param clazz Result class
//     * @param id Identifier of a entity that will be conditional loaded.
//     * @param changeVector Change vector of a entity that will be conditional loaded.
//     * @param <TResult> Result class
//     * @return Lazy Entity and change vector
//     */
//    <TResult> Lazy<ConditionalLoadResult<TResult>> conditionalLoad(Class<TResult> clazz, String id, String changeVector);
}
