<?php

namespace RavenDB\Documents\Session;

use Closure;
use RavenDB\Documents\Session\Operations\Lazy\EagerSessionOperationsInterface;
use RavenDB\Documents\Session\Operations\Lazy\LazySessionOperationsInterface;
use ReflectionException;
use RavenDB\Documents\Indexes\AbstractCommonApiForIndexes;
use RavenDB\Type\ObjectArray;

interface AdvancedSessionOperationsInterface extends
    AdvancedDocumentSessionOperationsInterface,
    DocumentQueryBuilderInterface
{
    /**
     * Access the eager operations
     * @return EagerSessionOperationsInterface Eager session operations
     */
    function eagerly(): EagerSessionOperationsInterface;

    /**
     * Access the lazy operations
     * @return LazySessionOperationsInterface Lazy session operations
     */
    function lazily(): LazySessionOperationsInterface;

    /**
     * @return AttachmentsSessionOperationsInterface Access the attachments operations
     */
    function attachments(): AttachmentsSessionOperationsInterface;

    /**
     * @return RevisionsSessionOperationsInterface Access the revisions operations
     */
    function revisions(): RevisionsSessionOperationsInterface;

    /**
     * @return ClusterTransactionOperationsInterface Access cluster transaction operations
     */
    function clusterTransaction(): ClusterTransactionOperationsInterface;

    /**
     * Updates entity with the latest changes from server
     *
     * @param object $entity
     */
    public function refresh(object $entity): void;

    /**
     * Query the specified index using provided raw query
     *
     * @param ?string $className result class
     * @param string $query Query
     *
     * @return RawDocumentQueryInterface Raw document query
     */
    public function rawQuery(?string $className, string $query): RawDocumentQueryInterface;

//    /**
//     * Issue a graph query based on the raw match query provided
//     * @param clazz result class
//     * @param query Graph Query
//     * @param <T> result class
//     * @return Graph query
//     */
//    <T> IGraphDocumentQuery<T> graphQuery(Class<T> clazz, String query);


    /**
     * Check if document exists
     * @param ?string $id document id to check
     * @return bool true if document exists
     */
    function exists(?string $id): bool;

    /**
     *
     * @param string $className entity class
     * @param string|null $idPrefix prefix for which documents should be returned e.g. "products/"
     * @param string|null $matches  pipe ('|') separated values for which document IDs (after 'idPrefix') should be matched ('?' any single character, '*' any characters)
     * @param int $start number of documents that should be skipped. By default: 0.
     * @param int $pageSize maximum number of documents that will be retrieved. By default: 25.
     * @param string|null $exclude pipe ('|') separated values for which document IDs (after 'idPrefix') should not be matched ('?' any single character, '*' any characters)
     * @param string|null $startAfter skip document fetching until given ID is found and return documents after that ID (default: null)
     *
     * @return ObjectArray Matched entities
     */
    public function loadStartingWith(
        string $className,
        ?string $idPrefix,
        ?string $matches = null,
        int $start = 0,
        int $pageSize = 25,
        ?string $exclude = null,
        ?string $startAfter = null
    ): ObjectArray;

//    /**
//     * Loads multiple entities that contain common prefix into a given stream.
//     * @param idPrefix prefix for which documents should be returned e.g. "products/"
//     * @param output the stream that will contain the load results
//     */
//    void loadStartingWithIntoStream(String idPrefix, OutputStream output);
//
//    /**
//     * Loads multiple entities that contain common prefix into a given stream.
//     * @param idPrefix prefix for which documents should be returned e.g. "products/"
//     * @param output the stream that will contain the load results
//     * @param matches pipe ('|') separated values for which document IDs (after 'idPrefix') should be matched ('?' any single character, '*' any characters)
//     */
//    void loadStartingWithIntoStream(String idPrefix, OutputStream output, String matches);
//
//    /**
//     * Loads multiple entities that contain common prefix into a given stream.
//     * @param idPrefix prefix for which documents should be returned e.g. "products/"
//     * @param output the stream that will contain the load results
//     * @param matches pipe ('|') separated values for which document IDs (after 'idPrefix') should be matched ('?' any single character, '*' any characters)
//     * @param start number of documents that should be skipped. By default: 0.
//     */
//    void loadStartingWithIntoStream(String idPrefix, OutputStream output, String matches, int start);
//
//    /**
//     * Loads multiple entities that contain common prefix into a given stream.
//     * @param idPrefix prefix for which documents should be returned e.g. "products/"
//     * @param output the stream that will contain the load results
//     * @param matches pipe ('|') separated values for which document IDs (after 'idPrefix') should be matched ('?' any single character, '*' any characters)
//     * @param start number of documents that should be skipped. By default: 0.
//     * @param pageSize maximum number of documents that will be retrieved. By default: 25.
//     */
//    void loadStartingWithIntoStream(String idPrefix, OutputStream output, String matches, int start, int pageSize);
//
//    /**
//     * Loads multiple entities that contain common prefix into a given stream.
//     * @param idPrefix prefix for which documents should be returned e.g. "products/"
//     * @param output the stream that will contain the load results
//     * @param matches pipe ('|') separated values for which document IDs (after 'idPrefix') should be matched ('?' any single character, '*' any characters)
//     * @param start number of documents that should be skipped. By default: 0.
//     * @param pageSize maximum number of documents that will be retrieved. By default: 25.
//     * @param exclude pipe ('|') separated values for which document IDs (after 'idPrefix') should not be matched ('?' any single character, '*' any characters)
//     */
//    void loadStartingWithIntoStream(String idPrefix, OutputStream output, String matches, int start, int pageSize, String exclude);
//
//    /**
//     * Loads multiple entities that contain common prefix into a given stream.
//     * @param idPrefix prefix for which documents should be returned e.g. "products/"
//     * @param output the stream that will contain the load results
//     * @param matches pipe ('|') separated values for which document IDs (after 'idPrefix') should be matched ('?' any single character, '*' any characters)
//     * @param start number of documents that should be skipped. By default: 0.
//     * @param pageSize maximum number of documents that will be retrieved. By default: 25.
//     * @param exclude pipe ('|') separated values for which document IDs (after 'idPrefix') should not be matched ('?' any single character, '*' any characters)
//     * @param startAfter skip document fetching until given ID is found and return documents after that ID (default: null)
//     */
//    void loadStartingWithIntoStream(String idPrefix, OutputStream output, String matches, int start, int pageSize, String exclude, String startAfter);
//
//    /**
//     * Loads the specified entities with the specified ids directly into a given stream.
//     * @param ids Collection of the Ids of the documents that should be loaded
//     * @param output the stream that will contain the load results
//     */
//    void loadIntoStream(Collection<String> ids, OutputStream output);

    /**
     * @param string|object|null $idOrEntity
     * @param string|null $path
     * @param mixed $valueToAdd
     */
    function increment($idOrEntity, ?string $path, $valueToAdd): void;

    /**
     * @param string|object|null $idOrEntity
     * @param string|null $path
     * @param mixed $value
     */
    function patch($idOrEntity, ?string $path, $value): void;

    /**
     * @param string|object|null $idOrEntity
     * @param string|null $pathToArray
     * @param Closure $arrayAdder
     */
    public function patchArray($idOrEntity, ?string $pathToArray, Closure $arrayAdder): void;

    /**
     * @param string|object|null $idOrEntity
     * @param string|null $pathToObject
     * @param Closure $dictionaryAdder
     */
    public function patchObject($idOrEntity, ?string $pathToObject, Closure $dictionaryAdder): void;

    /**
     * @param string|null $id
     * @param object      $entity
     * @param string|null $pathToObject
     * @param mixed       $value
     */
    public function addOrPatch(?string $id, object $entity, ?string $pathToObject, $value): void;

    public function addOrPatchArray(?string $id, object $entity, ?string $pathToArray, Closure $arrayAdder): void;

    /**
     * @param string|null $id
     * @param object      $entity
     * @param string|null $pathToObject
     * @param mixed       $valToAdd
     */
    public function addOrIncrement(?string $id, object $entity, ?string $pathToObject, $valToAdd): void;


//    /**
//     * Stream the results on the query to the client, converting them to
//     * Java types along the way.
//     *
//     * Does NOT track the entities in the session, and will not includes changes there when saveChanges() is called
//     * @param query Query to stream results for
//     * @param <T> Result class
//     * @return results iterator
//     */
//    <T> CloseableIterator<StreamResult<T>> stream(IDocumentQuery<T> query);
//
//    /**
//     * Stream the results on the query to the client, converting them to
//     * Java types along the way.
//     *
//     * Does NOT track the entities in the session, and will not includes changes there when saveChanges() is called
//     * @param query Query to stream results for
//     * @param streamQueryStats Information about the performed query
//     * @param <T> Result class
//     * @return results iterator
//     */
//    <T> CloseableIterator<StreamResult<T>> stream(IDocumentQuery<T> query, Reference<StreamQueryStatistics> streamQueryStats);
//
//    /**
//     * Stream the results on the query to the client, converting them to
//     * Java types along the way.
//     *
//     * Does NOT track the entities in the session, and will not includes changes there when saveChanges() is called
//     * @param query Query to stream results for
//     * @param <T> Result class
//     * @return results iterator
//     */
//    <T> CloseableIterator<StreamResult<T>> stream(IRawDocumentQuery<T> query);
//
//    /**
//     * Stream the results on the query to the client, converting them to
//     * Java types along the way.
//     *
//     * Does NOT track the entities in the session, and will not includes changes there when saveChanges() is called
//     * @param query Query to stream results for
//     * @param streamQueryStats Information about the performed query
//     * @param <T> Result class
//     * @return results iterator
//     */
//    <T> CloseableIterator<StreamResult<T>> stream(IRawDocumentQuery<T> query, Reference<StreamQueryStatistics> streamQueryStats);
//
//    /**
//     * Stream the results of documents search to the client, converting them to CLR types along the way.
//     * Does NOT track the entities in the session, and will not includes changes there when saveChanges() is called
//     * @param <T> Result class
//     * @param clazz Entity class
//     * @param startsWith prefix for which documents should be returned e.g. "products/"
//     * @return results iterator
//     */
//    <T> CloseableIterator<StreamResult<T>> stream(Class<T> clazz, String startsWith);
//
//    /**
//     * Stream the results of documents search to the client, converting them to CLR types along the way.
//     * Does NOT track the entities in the session, and will not includes changes there when saveChanges() is called
//     * @param clazz Result class
//     * @param startsWith prefix for which documents should be returned e.g. "products/"
//     * @param matches pipe ('|') separated values for which document ID (after 'idPrefix') should be matched ('?' any single character, '*' any characters)
//     * @param <T> Result class
//     * @return results iterator
//     */
//    <T> CloseableIterator<StreamResult<T>> stream(Class<T> clazz, String startsWith, String matches);
//
//    /**
//     * Stream the results of documents search to the client, converting them to CLR types along the way.
//     * Does NOT track the entities in the session, and will not includes changes there when saveChanges() is called
//     * @param clazz Entity class
//     * @param startsWith prefix for which documents should be returned e.g. "products/"
//     * @param matches pipe ('|') separated values for which document ID (after 'idPrefix') should be matched ('?' any single character, '*' any characters)
//     * @param start number of documents that should be skipped
//     * @param <T> Result class
//     * @return results iterator
//     */
//    <T> CloseableIterator<StreamResult<T>> stream(Class<T> clazz, String startsWith, String matches, int start);
//
//    /**
//     * Stream the results of documents search to the client, converting them to CLR types along the way.
//     * Does NOT track the entities in the session, and will not includes changes there when saveChanges() is called
//     * @param clazz Entity class
//     * @param startsWith prefix for which documents should be returned e.g. "products/"
//     * @param matches pipe ('|') separated values for which document ID (after 'idPrefix') should be matched ('?' any single character, '*' any characters)
//     * @param start number of documents that should be skipped
//     * @param pageSize maximum number of documents that will be retrieved
//     * @param <T> Result class
//     * @return results iterator
//     */
//    <T> CloseableIterator<StreamResult<T>> stream(Class<T> clazz, String startsWith, String matches, int start, int pageSize);
//
//    /**
//     * Stream the results of documents search to the client, converting them to CLR types along the way.
//     * Does NOT track the entities in the session, and will not includes changes there when saveChanges() is called
//     * @param clazz Entity class
//     * @param startsWith prefix for which documents should be returned e.g. "products/"
//     * @param matches pipe ('|') separated values for which document ID (after 'idPrefix') should be matched ('?' any single character, '*' any characters)
//     * @param start number of documents that should be skipped
//     * @param pageSize maximum number of documents that will be retrieved
//     * @param startAfter skip document fetching until given ID is found and return documents after that ID (default: null)
//     * @param <T> Result class
//     * @return results iterator
//     */
//    <T> CloseableIterator<StreamResult<T>> stream(Class<T> clazz, String startsWith, String matches, int start, int pageSize, String startAfter);
//
//    /**
//     * Returns the results of a query directly into stream
//     * @param query Query to use
//     * @param output Target output stream
//     * @param <T> Result class
//     */
//    <T> void streamInto(IDocumentQuery<T> query, OutputStream output);
//
//    /**
//     * Returns the results of a query directly into stream
//     * @param query  Query to use
//     * @param output Target output stream
//     * @param <T> Result class
//     */
//    <T> void streamInto(IRawDocumentQuery<T> query, OutputStream output);

    /**
     * Loads the specified entity with the specified id and changeVector.
     *
     * If the entity is loaded into the session, the tracked entity will be returned otherwise the entity will be loaded only if it is fresher then the provided changeVector.
     *
     * @template T
     *
     * @param ?string $className Result class
     * @param ?string $id Identifier of a entity that will be conditional loaded.
     * @param ?string $changeVector Change vector of an entity that will be conditional loaded.
     *
     * @return ConditionalLoadResult<T> Entity and change vector
     */
    function conditionalLoad(?string $className, ?string $id, ?string $changeVector): ConditionalLoadResult;
}
