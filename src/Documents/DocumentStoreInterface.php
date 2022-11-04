<?php

namespace RavenDB\Documents;

use Closure;
use RavenDB\Auth\AuthOptions;
use RavenDB\Documents\Operations\OperationExecutor;
use RavenDB\Documents\Indexes\AbstractIndexCreationTaskArray;
use RavenDB\Documents\Indexes\AbstractIndexCreationTaskInterface;
use RavenDB\Documents\Operations\MaintenanceOperationExecutor;
use RavenDB\Documents\Session\AfterConversionToEntityEventArgs;
use RavenDB\Documents\Session\BeforeConversionToEntityEventArgs;
use RavenDB\Documents\Session\DocumentSessionInterface;
use RavenDB\Documents\Session\SessionOptions;
use RavenDB\Type\UrlArray;
use RavenDB\Http\RequestExecutor;
use RavenDB\Documents\Conventions\DocumentConventions;

// !status: IN PROGRESS
interface DocumentStoreInterface
{
    public function getAuthOptions(): ?AuthOptions;
//  KeyStore getCertificate();

    function addBeforeStoreListener(Closure $handler);
    function removeBeforeStoreListener(Closure $handler);

    /** AfterSaveChangesEventArgs */
    function addAfterSaveChangesListener(Closure $handler): void;
    /** AfterSaveChangesEventArgs */
    function removeAfterSaveChangesListener(Closure $handler): void;

    function addBeforeDeleteListener(Closure $handler);
    function removeBeforeDeleteListener(Closure $handler);

    function addBeforeQueryListener(Closure $handler);
    function removeBeforeQueryListener(Closure $handler);

    function addBeforeConversionToDocumentListener(Closure $handler): void;
    function removeBeforeConversionToDocumentListener(Closure $handler): void;

    function addAfterConversionToDocumentListener(Closure $handler): void;
    function removeAfterConversionToDocumentListener(Closure $handler): void;

    /**
     * @param Closure $handler with args: BeforeConversionToEntityEventArgs
     */
    function addBeforeConversionToEntityListener(Closure $handler): void;

    /**
     * @param Closure $handler with args: BeforeConversionToEntityEventArgs
     */
    function removeBeforeConversionToEntityListener(Closure $handler): void;

    /**
     * @param Closure $handler with args: AfterConversionToEntityEventArgs
     */
    function addAfterConversionToEntityListener(Closure $handler): void;

    /**
     * @param Closure $handler with args: AfterConversionToEntityEventArgs
     */
    function removeAfterConversionToEntityListener(Closure $handler): void;

//    void addOnFailedRequestListener(EventHandler<FailedRequestEventArgs> handler);
//    void removeOnFailedRequestListener(EventHandler<FailedRequestEventArgs> handler);
//
//    void addOnTopologyUpdatedListener(EventHandler<TopologyUpdatedEventArgs> handler);
//    void removeOnTopologyUpdatedListener(EventHandler<TopologyUpdatedEventArgs> handler);
//
//    void addOnSessionClosingListener(EventHandler<SessionClosingEventArgs> handler);
//    void removeOnSessionClosingListener(EventHandler<SessionClosingEventArgs> handler);

//    /**
//     * @return Gets the identifier for this store.
//     */
//    String getIdentifier();
//
//    /**
//     * Sets the identifier for this store.
//     * @param identifier Identifier to set
//     */
//    void setIdentifier(String identifier);

    /**
     * Initializes this instance.
     *
     * @return DocumentStoreInterface initialized store
     */
    public function initialize(): DocumentStoreInterface;

    /**
     * Opens the session for a particular database
     *
     * @param string|SessionOptions $dbNameOrOptions Database to use
     *
     * @return DocumentSessionInterface Document session
     */
    public function openSession($dbNameOrOptions = ''): DocumentSessionInterface;

    /**
     * Executes the index creation
     * @param AbstractIndexCreationTaskInterface $task Index Creation task to use
     * @param string|null $database Target database
     */
    function executeIndex(AbstractIndexCreationTaskInterface $task, ?string $database = null): void;

    /**
     * Executes the index creation
     * @param AbstractIndexCreationTaskArray|array $tasks Index Creation tasks to use
     * @param ?string $database Target database
     */
    function executeIndexes($tasks, ?string $database = null): void;

//    TimeSeriesOperations timeSeries();

    /**
     * Gets the conventions
     * @return DocumentConventions Document conventions
     */
    public function & getConventions(): DocumentConventions;

    /**
     * Gets the URL's
     *
     * @return UrlArray|null Store urls
     */
    public function getUrls(): ?UrlArray;

//    BulkInsertOperation bulkInsert();
//
//    BulkInsertOperation bulkInsert(String database);
//
//    DocumentSubscriptions subscriptions();

    public function getDatabase(): ?string;

    public function getRequestExecutor(?string $database = null): RequestExecutor;

    public function maintenance(): MaintenanceOperationExecutor;

    public function operations(): OperationExecutor;

//    DatabaseSmuggler smuggler();
//
//    CleanCloseable setRequestTimeout(Duration timeout);
//
//    CleanCloseable setRequestTimeout(Duration timeout, String database);

    public function close(): void;
}
