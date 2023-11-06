<?php

namespace RavenDB\Documents;

use Closure;
use RavenDB\Auth\AuthOptions;
use RavenDB\Documents\Identity\HiLoIdGeneratorInterface;
use RavenDB\Documents\Operations\OperationExecutor;
use RavenDB\Documents\Indexes\AbstractIndexCreationTaskArray;
use RavenDB\Documents\Indexes\AbstractIndexCreationTaskInterface;
use RavenDB\Documents\Operations\MaintenanceOperationExecutor;
use RavenDB\Documents\Session\AfterConversionToEntityEventArgs;
use RavenDB\Documents\Session\BeforeConversionToEntityEventArgs;
use RavenDB\Documents\Session\DocumentSessionInterface;
use RavenDB\Documents\Session\SessionOptions;
use RavenDB\Documents\TimeSeries\TimeSeriesOperations;
use RavenDB\Primitives\CleanCloseable;
use RavenDB\Type\Duration;
use RavenDB\Type\UrlArray;
use RavenDB\Http\RequestExecutor;
use RavenDB\Documents\Conventions\DocumentConventions;

// !status: IN PROGRESS
interface DocumentStoreInterface
{
    public function getAuthOptions(): ?AuthOptions;
//  KeyStore getCertificate();

    function getHiLoIdGenerator(): ?HiLoIdGeneratorInterface;

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

//    void addOnBeforeRequestListener(EventHandler<BeforeRequestEventArgs> handler);
//    void removeOnBeforeRequestListener(EventHandler<BeforeRequestEventArgs> handler);

//    void addOnSucceedRequestListener(EventHandler<SucceedRequestEventArgs> handler);
//    void removeOnSucceedRequestListener(EventHandler<SucceedRequestEventArgs> handler);

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
     * @param null|string|SessionOptions $dbNameOrOptions Database to use
     *
     * @return DocumentSessionInterface Document session
     */
    public function openSession(null|string|SessionOptions $dbNameOrOptions = null): DocumentSessionInterface;

    /**
     * Executes the index creation
     * @param AbstractIndexCreationTaskInterface $task Index Creation task to use
     * @param string|null $database Target database
     */
    function executeIndex(AbstractIndexCreationTaskInterface $task, ?string $database = null): void;

    /**
     * Executes the index creation
     * @param array|AbstractIndexCreationTaskArray $tasks Index Creation tasks to use
     * @param ?string $database Target database
     */
    function executeIndexes(AbstractIndexCreationTaskArray|array $tasks, ?string $database = null): void;

    function timeSeries(): TimeSeriesOperations;

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
//    BulkInsertOperation bulkInsert(?string $database = '', ?BulkInsertOption $options);
//
//    DocumentSubscriptions subscriptions();

    public function getDatabase(): ?string;

    public function getRequestExecutor(?string $database = null): RequestExecutor;

    public function maintenance(): MaintenanceOperationExecutor;

    public function operations(): OperationExecutor;

//    DatabaseSmuggler smuggler();

    public function setRequestTimeout(?Duration $timeout, ?string $database = null): CleanCloseable;

    public function close(): void;
}
