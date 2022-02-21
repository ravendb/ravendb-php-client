<?php

namespace RavenDB\Documents;

use RavenDB\Documents\Operations\MaintenanceOperationExecutor;
use RavenDB\Documents\Session\DocumentSession;
use RavenDB\Documents\Session\DocumentSessionInterface;
use RavenDB\Documents\Session\SessionOptions;
use RavenDB\Type\UrlArray;
use RavenDB\Http\RequestExecutor;
use RavenDB\Documents\Conventions\DocumentConventions;

// !status: IN PROGRESS
interface DocumentStoreInterface
{
//  KeyStore getCertificate();
//
//    void addBeforeStoreListener(EventHandler<BeforeStoreEventArgs> handler);
//    void removeBeforeStoreListener(EventHandler<BeforeStoreEventArgs> handler);
//
//    void addAfterSaveChangesListener(EventHandler<AfterSaveChangesEventArgs> handler);
//    void removeAfterSaveChangesListener(EventHandler<AfterSaveChangesEventArgs> handler);
//
//    void addBeforeDeleteListener(EventHandler<BeforeDeleteEventArgs> handler);
//    void removeBeforeDeleteListener(EventHandler<BeforeDeleteEventArgs> handler);
//
//    void addBeforeQueryListener(EventHandler<BeforeQueryEventArgs> handler);
//    void removeBeforeQueryListener(EventHandler<BeforeQueryEventArgs> handler);
//
//    void addBeforeConversionToDocumentListener(EventHandler<BeforeConversionToDocumentEventArgs> handler);
//    void removeBeforeConversionToDocumentListener(EventHandler<BeforeConversionToDocumentEventArgs> handler);
//
//    void addAfterConversionToDocumentListener(EventHandler<AfterConversionToDocumentEventArgs> handler);
//    void removeAfterConversionToDocumentListener(EventHandler<AfterConversionToDocumentEventArgs> handler);
//
//    void addBeforeConversionToEntityListener(EventHandler<BeforeConversionToEntityEventArgs> handler);
//    void removeBeforeConversionToEntityListener(EventHandler<BeforeConversionToEntityEventArgs> handler);
//
//    void addAfterConversionToEntityListener(EventHandler<AfterConversionToEntityEventArgs> handler);
//    void removeAfterConversionToEntityListener(EventHandler<AfterConversionToEntityEventArgs> handler);
//
//    void addOnFailedRequestListener(EventHandler<FailedRequestEventArgs> handler);
//    void removeOnFailedRequestListener(EventHandler<FailedRequestEventArgs> handler);
//
//    void addOnTopologyUpdatedListener(EventHandler<TopologyUpdatedEventArgs> handler);
//    void removeOnTopologyUpdatedListener(EventHandler<TopologyUpdatedEventArgs> handler);
//
//    void addOnSessionClosingListener(EventHandler<SessionClosingEventArgs> handler);
//    void removeOnSessionClosingListener(EventHandler<SessionClosingEventArgs> handler);
//
//    /**
//     * Subscribe to change notifications from the server
//     * @return Database changes object
//     */
//    IDatabaseChanges changes();
//
//    /**
//     * Subscribe to change notifications from the server
//     * @param database Database to use
//     * @return Database changes object
//     */
//    IDatabaseChanges changes(String database);
//
//    /**
//     * Subscribe to change notifications from the server
//     * @param database Database to use
//     * @param nodeTag The node tag of selected server
//     * @return Database changes object
//     */
//    IDatabaseChanges changes(String database, String nodeTag);
//
//    /**
//     * Setup the context for aggressive caching.
//     *
//     * Aggressive caching means that we will not check the server to see whether the response
//     * we provide is current or not, but will serve the information directly from the local cache
//     * without touching the server.
//     *
//     * @param cacheDuration Specify the aggressive cache duration
//     * @return Context for aggressive caching
//     */
//    CleanCloseable aggressivelyCacheFor(Duration cacheDuration);
//
//    /**
//     * Setup the context for aggressive caching.
//     *
//     * Aggressive caching means that we will not check the server to see whether the response
//     * we provide is current or not, but will serve the information directly from the local cache
//     * without touching the server.
//     *
//     * @param cacheDuration Specify the aggressive cache duration
//     * @param database The database to cache, if not specified, the default database will be used
//     * @return Context for aggressive caching
//     */
//    CleanCloseable aggressivelyCacheFor(Duration cacheDuration, String database);
//
//    /**
//     * Setup the context for aggressive caching.
//     *
//     * Aggressive caching means that we will not check the server to see whether the response
//     * we provide is current or not, but will serve the information directly from the local cache
//     * without touching the server.
//     *
//     * @param cacheDuration Specify the aggressive cache duration
//     * @param mode Aggressive caching mode, if not specified, TrackChanges mode will be used
//     * @return Context for aggressive caching
//     */
//    CleanCloseable aggressivelyCacheFor(Duration cacheDuration, AggressiveCacheMode mode);
//
//    /**
//     * Setup the context for aggressive caching.
//     *
//     * Aggressive caching means that we will not check the server to see whether the response
//     * we provide is current or not, but will serve the information directly from the local cache
//     * without touching the server.
//     *
//     * @param cacheDuration Specify the aggressive cache duration
//     * @param mode Aggressive caching mode, if not specified, TrackChanges mode will be used
//     * @param database The database to cache, if not specified, the default database will be used
//     * @return Context for aggressive caching
//     */
//    CleanCloseable aggressivelyCacheFor(Duration cacheDuration, AggressiveCacheMode mode, String database);
//
//    /**
//     * Setup the context for aggressive caching.
//     *
//     * Aggressive caching means that we will not check the server to see whether the response
//     * we provide is current or not, but will serve the information directly from the local cache
//     * without touching the server.
//     * @return Context for aggressive caching
//     */
//    CleanCloseable aggressivelyCache();
//
//    /**
//     * Setup the context for aggressive caching.
//     *
//     * Aggressive caching means that we will not check the server to see whether the response
//     * we provide is current or not, but will serve the information directly from the local cache
//     * without touching the server.
//     *
//     * @param database The database to cache, if not specified, the default database will be used
//     * @return Context for aggressive caching
//     */
//    CleanCloseable aggressivelyCache(String database);
//
//    /**
//     * Setup the context for no aggressive caching
//     *
//     * This is mainly useful for internal use inside RavenDB, when we are executing
//     * queries that have been marked with WaitForNonStaleResults, we temporarily disable
//     * aggressive caching.
//     * @return Context for aggressive caching
//     */
//    CleanCloseable disableAggressiveCaching();
//
//    /**
//     * Setup the context for no aggressive caching
//     *
//     * This is mainly useful for internal use inside RavenDB, when we are executing
//     * queries that have been marked with WaitForNonStaleResults, we temporarily disable
//     * aggressive caching.
//     * @param database Database name
//     * @return Context for aggressive caching
//     */
//    CleanCloseable disableAggressiveCaching(String database);
//
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
     * @param string $database Database to use
     * @return DocumentSessionInterface Document session
     */
    public function openSession(string $database = ''): DocumentSessionInterface;

    /**
     * Opens the session with the specified options.
     *
     * @param SessionOptions $sessionOptions Session options to use
     * @return DocumentSessionInterface Document session
     */
    public function openSessionWithOptions(SessionOptions $sessionOptions): DocumentSessionInterface;

//    /**
//     * Executes the index creation
//     * @param task Index Creation task to use
//     */
//    void executeIndex(IAbstractIndexCreationTask task);
//
//    /**
//     * Executes the index creation
//     * @param task Index Creation task to use
//     * @param database Target database
//     */
//    void executeIndex(IAbstractIndexCreationTask task, String database);
//
//    /**
//     * Executes the index creation
//     * @param tasks Index Creation tasks to use
//     */
//    void executeIndexes(List<IAbstractIndexCreationTask> tasks);
//
//    /**
//     * Executes the index creation
//     * @param tasks Index Creation tasks to use
//     * @param database Target database
//     */
//    void executeIndexes(List<IAbstractIndexCreationTask> tasks, String database);
//
//    TimeSeriesOperations timeSeries();

    /**
     * Gets the conventions
     * @return DocumentConventions Document conventions
     */
    public function getConventions(): DocumentConventions;

    /**
     * Gets the URL's
     *
     * @return UrlArray Store urls
     */
    public function getUrls(): UrlArray;

//    BulkInsertOperation bulkInsert();
//
//    BulkInsertOperation bulkInsert(String database);
//
//    DocumentSubscriptions subscriptions();

    public function getDatabase(): string;

    public function getRequestExecutor(string $databaseName = ''): RequestExecutor;

    public function maintenance(): MaintenanceOperationExecutor;
//
//    OperationExecutor operations();
//
//    DatabaseSmuggler smuggler();
//
//    CleanCloseable setRequestTimeout(Duration timeout);
//
//    CleanCloseable setRequestTimeout(Duration timeout, String database);

    public function close(): void;
}
