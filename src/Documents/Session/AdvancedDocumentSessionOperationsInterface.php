<?php

namespace RavenDB\Documents\Session;

use Closure;
use RavenDB\Documents\Commands\Batches\CommandDataInterface;

interface AdvancedDocumentSessionOperationsInterface
{
//    /**
//     * The document store associated with this session
//     * @return Document store
//     */
//    IDocumentStore getDocumentStore();
//
//    /**
//     * Allow extensions to provide additional state per session
//     * @return External state
//     */
//    Map<String, Object> getExternalState();
//
//    ServerNode getCurrentSessionNode();
//
//    RequestExecutor getRequestExecutor();
//
//    SessionInfo getSessionInfo();
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
    public function addBeforeQueryListener(Closure $handler): void;
    public function removeBeforeQueryListener(Closure $handler): void;
//    void removeBeforeQueryListener(EventHandler<BeforeQueryEventArgs> handler);

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
//    void addOnSessionClosingListener(EventHandler<SessionClosingEventArgs> handler);
//    void removeOnSessionClosingListener(EventHandler<SessionClosingEventArgs> handler);
//    /**
//     * Gets a value indicating whether any of the entities tracked by the session has changes.
//     * @return true if any entity associated with session has changes
//     */
//    boolean hasChanges();
//
//    /**
//     * Gets the max number of requests per session.
//     * @return maximum number of requests per session
//     */
//    int getMaxNumberOfRequestsPerSession();
//
//    /**
//     * Sets the max number of requests per session.
//     * @param maxRequests Sets the maximum requests
//     */
//    void setMaxNumberOfRequestsPerSession(int maxRequests);

    /**
     * Gets the number of requests for this session
     * @return int Number of requests issued on this session
     */
    function getNumberOfRequests(): int;

    //    /**
//     * Gets the store identifier for this session.
//     * The store identifier is the identifier for the particular RavenDB instance.
//     * @return Store identifier
//     */
//    String storeIdentifier();
//
//    /**
//     * Gets value indicating whether the session should use optimistic concurrency.
//     * When set to true, a check is made so that a change made behind the session back would fail
//     * and raise ConcurrencyException
//     * @return true if optimistic concurrency should be used
//     */
//    boolean isUseOptimisticConcurrency();
//
//    /**
//     * Sets value indicating whether the session should use optimistic concurrency.
//     * When set to true, a check is made so that a change made behind the session back would fail
//     * and raise ConcurrencyException
//     * @param useOptimisticConcurrency Sets the optimistic concurrency
//     */
//    void setUseOptimisticConcurrency(boolean useOptimisticConcurrency);
//
//    /**
//     * Clears this instance.
//     * Remove all entities from the delete queue and stops tracking changes for all entities.
//     */
//    void clear();

    /**
     * Defer commands to be executed on saveChanges()
     *
     * defer(CommandDataInterface $command): void
     * defer(CommandDataInterface $command, array $commands): void
     * defer(array $commands): void
     *
     * @param CommandDataInterface|array $commands More commands to defer
     */
    public function defer(...$commands): void;

//    /**
//     * Evicts the specified entity from the session.
//     * Remove the entity from the delete queue and stops tracking changes for this entity.
//     * @param <T> entity class
//     * @param entity Entity to evict
//     */
//    <T> void evict(T entity);

    /**
     * Gets the document id for the specified entity.
     *
     *  This function may return null if the entity isn't tracked by the session, or if the entity is
     *   a new entity with an ID that should be generated on the server.
     * @param ?object $instance Entity to get id from
     * @return string document id
     */
    function getDocumentId(?object $instance): ?string;

//    /**
//     * Gets the metadata for the specified entity.
//     * If the entity is transient, it will load the metadata from the store
//     * and associate the current state of the entity with the metadata from the server.
//     * @param <T> class of instance
//     * @param instance instance to get metadata from
//     * @return Entity metadata
//     */
//    <T> IMetadataDictionary getMetadataFor(T instance);
//
    /**
     * Gets change vector for the specified entity.
     * If the entity is transient, it will load the metadata from the store
     * and associate the current state of the entity with the metadata from the server.
     *
     * @param ?object $instance Instance to get metadata from
     * @return ?string Change vector
     */
    function getChangeVectorFor(?object $instance): ?string;

//    /**
//     * Gets all the counter names for the specified entity.
//     * @param instance The instance
//     * @param <T> Class of instance
//     * @return List of counter names
//     */
//    <T> List<String> getCountersFor(T instance);
//
//    /**
//     * Gets all time series names for the specified entity.
//     * @param instance The instance
//     * @param <T> Class of instance
//     * @return List of time series names
//     */
//    <T> List<String> getTimeSeriesFor(T instance);
//
//    /**
//     * Gets last modified date for the specified entity.
//     * If the entity is transient, it will load the metadata from the store
//     * and associate the current state of the entity with the metadata from the server.
//     * @param instance Instance to get last modified date from
//     * @param <T> Class of instance
//     * @return Last modified date
//     */
//    <T> Date getLastModifiedFor(T instance);
//
//    /**
//     * Determines whether the specified entity has changed.
//     * @param entity Entity to check
//     * @return true if entity has changed
//     */
//    boolean hasChanged(Object entity);

    /**
     * Returns whether a document with the specified id is loaded in the
     * current session
     * @param string $id Id of document
     * @return bool true is entity is loaded in session
     */
    function isLoaded(string $id): bool;

//    /**
//     * Mark the entity as one that should be ignore for change tracking purposes,
//     * it still takes part in the session, but is ignored for SaveChanges.
//     * @param entity Entity for which changed should be ignored
//     */
//    void ignoreChangesFor(Object entity);

    /**
     * Returns all changes for each entity stored within session.
     * Including name of the field/property that changed, its old and new value and change type.
     *
     * @return array Document changes
     */
//    Map<String, List<DocumentsChanges>> whatChanged();
    public function whatChanged(): array;

//    /**
//     * SaveChanges will wait for the changes made to be replicates to `replicas` nodes
//     */
//    void waitForReplicationAfterSaveChanges();
//
//    /**
//     * SaveChanges will wait for the changes made to be replicates to `replicas` nodes
//     * @param options Configuration options
//     */
//    void waitForReplicationAfterSaveChanges(Consumer<InMemoryDocumentSessionOperations.ReplicationWaitOptsBuilder> options);
//
//    /**
//     * SaveChanges will wait for the indexes to catch up with the saved changes
//     */
//    void waitForIndexesAfterSaveChanges();
//
//    /**
//     * SaveChanges will wait for the indexes to catch up with the saved changes
//     * @param options Configuration options
//     */
//    void waitForIndexesAfterSaveChanges(Consumer<InMemoryDocumentSessionOperations.IndexesWaitOptsBuilder> options);
//
//    /**
//     * Overwrite the existing transaction mode for the current session.
//     * @param mode Transaction mode
//     */
//    void setTransactionMode(TransactionMode mode);
//
//    EntityToJson getEntityToJson();
}
