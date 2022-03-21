<?php

namespace RavenDB\Documents;

use Closure;
use InvalidArgumentException;
use RavenDB\Auth\AuthOptions;
use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Session\InMemoryDocumentSessionOperations;
use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Http\RequestExecutor;
use RavenDB\Primitives\ClosureArray;
use RavenDB\Type\UrlArray;
use RavenDB\Utils\StringUtils;

// !status: LOGIC COPIED - IN PROGRESS
abstract class DocumentStoreBase implements DocumentStoreInterface
{
//      private final List<EventHandler<BeforeStoreEventArgs>> onBeforeStore = new ArrayList<>();
//    private final List<EventHandler<AfterSaveChangesEventArgs>> onAfterSaveChanges = new ArrayList<>();
//    private final List<EventHandler<BeforeDeleteEventArgs>> onBeforeDelete = new ArrayList<>();
    private ClosureArray $onBeforeQuery;
//    private final List<EventHandler<SessionCreatedEventArgs>> onSessionCreated = new ArrayList<>();
//    private final List<EventHandler<SessionClosingEventArgs>> onSessionClosing = new ArrayList<>();
//
//    private final List<EventHandler<BeforeConversionToDocumentEventArgs>> onBeforeConversionToDocument = new ArrayList<>();
//    private final List<EventHandler<AfterConversionToDocumentEventArgs>> onAfterConversionToDocument = new ArrayList<>();
//    private final List<EventHandler<BeforeConversionToEntityEventArgs>> onBeforeConversionToEntity = new ArrayList<>();
//    private final List<EventHandler<AfterConversionToEntityEventArgs>> onAfterConversionToEntity = new ArrayList<>();
//    private final List<EventHandler<BeforeRequestEventArgs>> onBeforeRequest = new ArrayList<>();
//    private final List<EventHandler<SucceedRequestEventArgs>> onSucceedRequest = new ArrayList<>();
//
//    private final List<EventHandler<FailedRequestEventArgs>> onFailedRequest = new ArrayList<>();
//    private final List<EventHandler<TopologyUpdatedEventArgs>> onTopologyUpdated = new ArrayList<>();

    public function __construct()
    {
        $this->database = '';
        $this->urls = new UrlArray();

        $this->onBeforeQuery = new ClosureArray();

//        $this->subscriptions = new DocumentSubscriptions($this);
    }

//    public abstract void close();
//
//    public abstract void addBeforeCloseListener(EventHandler<VoidArgs> event);
//
//    public abstract void removeBeforeCloseListener(EventHandler<VoidArgs> event);
//
//    public abstract void addAfterCloseListener(EventHandler<VoidArgs> event);
//
//    public abstract void removeAfterCloseListener(EventHandler<VoidArgs> event);

    protected bool $disposed = false;

    public function isDisposed(): bool
    {
        return $this->disposed;
    }


//    public abstract IDatabaseChanges changes();
//
//    public abstract IDatabaseChanges changes(String database);
//
//    public abstract IDatabaseChanges changes(String database, String nodeTag);
//
//    @Override
//    public abstract CleanCloseable aggressivelyCacheFor(Duration cacheDuration);
//
//    @Override
//    public abstract CleanCloseable aggressivelyCacheFor(Duration cacheDuration, String database);
//
//    @Override
//    public abstract CleanCloseable aggressivelyCacheFor(Duration cacheDuration, AggressiveCacheMode mode);
//
//    @Override
//    public abstract CleanCloseable aggressivelyCacheFor(Duration cacheDuration, AggressiveCacheMode mode, String database);
//
//    @Override
//    public abstract CleanCloseable disableAggressiveCaching();
//
//    @Override
//    public abstract CleanCloseable disableAggressiveCaching(String database);
//
    public abstract function getIdentifier(): ?string;
//
//    public abstract void setIdentifier(String identifier);

    abstract public function initialize(): DocumentStoreInterface;

//    public abstract IDocumentSession openSession();
//
//    public abstract IDocumentSession openSession(String database);
//
//    public abstract IDocumentSession openSession(SessionOptions sessionOptions);
//
//    public void executeIndex(IAbstractIndexCreationTask task) {
//        executeIndex(task, null);
//    }
//
//    public void executeIndex(IAbstractIndexCreationTask task, String database) {
//        assertInitialized();
//        task.execute(this, conventions, database);
//    }
//
//    @Override
//    public void executeIndexes(List<IAbstractIndexCreationTask> tasks) {
//        executeIndexes(tasks, null);
//    }
//
//    @Override
//    public void executeIndexes(List<IAbstractIndexCreationTask> tasks, String database) {
//        assertInitialized();
//        IndexDefinition[] indexesToAdd = IndexCreation.createIndexesToAdd(tasks, conventions);
//
//        maintenance()
//                .forDatabase(getEffectiveDatabase(database))
//                .send(new PutIndexesOperation(indexesToAdd));
//    }
//
//    private TimeSeriesOperations _timeSeriesOperation;
//
//    public TimeSeriesOperations timeSeries() {
//        if (_timeSeriesOperation == null) {
//            _timeSeriesOperation = new TimeSeriesOperations(this);
//        }
//
//        return _timeSeriesOperation;
//    }
//

    private ?DocumentConventions $conventions = null;

    public function getConventions(): DocumentConventions
    {
        if ($this->conventions == null) {
            $this->conventions = new DocumentConventions();
        }

        return $this->conventions;
    }


    /**
     * @throws IllegalStateException
     */
    public function setConventions(?DocumentConventions $conventions): void
    {
        $this->assertNotInitialized("conventions");
        $this->conventions = $conventions;
    }


    protected UrlArray $urls;

    public function getUrls(): UrlArray
    {
        return $this->urls;
    }

    /**
     * @throws IllegalStateException
     */
    public function setUrls(UrlArray $urls): void
    {
        $this->assertNotInitialized('urls');

//        @todo: validate data in this method
//        if (value == null) {
//            throw new IllegalArgumentException("value is null");
//        }
//
//        for (int i = 0; i < value.length; i++) {
//            if (value[i] == null)
//                throw new IllegalArgumentException("Urls cannot contain null");
//
//            try {
//                new URL(value[i]);
//            } catch (MalformedURLException e) {
//                throw new IllegalArgumentException("The url '" + value[i] + "' is not valid");
//            }
//
//            value[i] = StringUtils.stripEnd(value[i], "/");
//        }

        $this->urls = $urls;
    }

    protected bool $initialized = false;

    protected ?AuthOptions $authOptions = null;

    public function getAuthOptions(): ?AuthOptions
    {
        return $this->authOptions;
    }

    public function setAuthOptions(?AuthOptions $options): void
    {
        $this->assertNotInitialized('authOptions');
        $this->authOptions = $options;
    }

//    private KeyStore _certificate;
//    private char[] _certificatePrivateKeyPassword = "".toCharArray();
//    private KeyStore _trustStore;
//
//    public abstract BulkInsertOperation bulkInsert();
//
//    public abstract BulkInsertOperation bulkInsert(String database);
//
//    private final DocumentSubscriptions _subscriptions;
//
//    public DocumentSubscriptions subscriptions() {
//        return _subscriptions;
//    }
//
//    private ConcurrentMap<String, Long> _lastRaftIndexPerDatabase = new ConcurrentSkipListMap<>(String::compareToIgnoreCase);

    public function getLastTransactionIndex(string $database): int
    {
//        Long index = _lastRaftIndexPerDatabase.get(database);
//        if (index == null || index == 0) {
//            return null;
//        }
//
//        return index;

        return 0;
    }

    public function setLastTransactionIndex(string $database, int $index): void
    {
//        if (index == null) {
//            return;
//        }
//
//        _lastRaftIndexPerDatabase.compute(database, (__, initialValue) -> {
//                if (initialValue == null) {
//                    return index;
//                }
//                return Math.max(initialValue, index);
//        });
    }

    /**
     * @throws IllegalStateException
     */
    public function ensureNotClosed(): void
    {
        if ($this->disposed) {
            throw new IllegalStateException("The document store has already been disposed and cannot be used");
        }
    }

    /**
     * @throws IllegalStateException
     */
    public function assertInitialized(): void
    {
        if (!$this->initialized) {
            throw new IllegalStateException(
                'You cannot open a session or access the database commands before initializing ' .
                'the document store. Did you forget calling initialize()?'
            );
        }
    }

    /**
     * @throws IllegalStateException
     */
    public function assertNotInitialized(string $property): void
    {
        if ($this->initialized) {
            throw new IllegalStateException(
                'You cannot set ' . $property . ' after the document store has been initialized.'
            );
        }
    }


//    public void addBeforeStoreListener(EventHandler<BeforeStoreEventArgs> handler) {
//        this.onBeforeStore.add(handler);
//
//    }
//    public void removeBeforeStoreListener(EventHandler<BeforeStoreEventArgs> handler) {
//        this.onBeforeStore.remove(handler);
//    }
//
//    public void addAfterSaveChangesListener(EventHandler<AfterSaveChangesEventArgs> handler) {
//        this.onAfterSaveChanges.add(handler);
//    }
//
//    public void removeAfterSaveChangesListener(EventHandler<AfterSaveChangesEventArgs> handler) {
//        this.onAfterSaveChanges.remove(handler);
//    }
//
//    public void addBeforeDeleteListener(EventHandler<BeforeDeleteEventArgs> handler) {
//        this.onBeforeDelete.add(handler);
//    }
//    public void removeBeforeDeleteListener(EventHandler<BeforeDeleteEventArgs> handler) {
//        this.onBeforeDelete.remove(handler);
//    }
//
//    public function addBeforeQueryListener(EventHandler<BeforeQueryEventArgs> $handler): void
    public function addBeforeQueryListener(Closure $handler): void
    {
        $this->onBeforeQuery->append($handler);
    }
//
//    public void removeBeforeQueryListener(EventHandler<BeforeQueryEventArgs> handler) {
//        this.onBeforeQuery.remove(handler);
//    }
//
//    public void addOnSessionClosingListener(EventHandler<SessionClosingEventArgs> handler) {
//        this.onSessionClosing.add(handler);
//    }
//
//    public void removeOnSessionClosingListener(EventHandler<SessionClosingEventArgs> handler) {
//        this.onSessionClosing.remove(handler);
//    }
//
//    public void addBeforeConversionToDocumentListener(EventHandler<BeforeConversionToDocumentEventArgs> handler) {
//        this.onBeforeConversionToDocument.add(handler);
//    }
//
//    public void removeBeforeConversionToDocumentListener(EventHandler<BeforeConversionToDocumentEventArgs> handler) {
//        this.onBeforeConversionToDocument.remove(handler);
//    }
//
//    public void addAfterConversionToDocumentListener(EventHandler<AfterConversionToDocumentEventArgs> handler) {
//        this.onAfterConversionToDocument.add(handler);
//    }
//
//    public void removeAfterConversionToDocumentListener(EventHandler<AfterConversionToDocumentEventArgs> handler) {
//        this.onAfterConversionToDocument.remove(handler);
//    }
//
//    public void addBeforeConversionToEntityListener(EventHandler<BeforeConversionToEntityEventArgs> handler) {
//        this.onBeforeConversionToEntity.add(handler);
//    }
//
//    public void removeBeforeConversionToEntityListener(EventHandler<BeforeConversionToEntityEventArgs> handler) {
//        this.onBeforeConversionToEntity.remove(handler);
//    }
//
//    public void addAfterConversionToEntityListener(EventHandler<AfterConversionToEntityEventArgs> handler) {
//        this.onAfterConversionToEntity.add(handler);
//    }
//
//    public void removeAfterConversionToEntityListener(EventHandler<AfterConversionToEntityEventArgs> handler) {
//        this.onAfterConversionToEntity.remove(handler);
//    }
//
//    public void addOnBeforeRequestListener(EventHandler<BeforeRequestEventArgs> handler) {
//        assertNotInitialized("onSucceedRequest");
//        this.onBeforeRequest.add(handler);
//    }
//
//    public void removeOnBeforeRequestListener(EventHandler<BeforeRequestEventArgs> handler) {
//        assertNotInitialized("onSucceedRequest");
//        this.onBeforeRequest.remove(handler);
//    }
//
//    public void addOnSucceedRequestListener(EventHandler<SucceedRequestEventArgs> handler) {
//        assertNotInitialized("onSucceedRequest");
//        this.onSucceedRequest.add(handler);
//    }
//
//    public void removeOnSucceedRequestListener(EventHandler<SucceedRequestEventArgs> handler) {
//        assertNotInitialized("onSucceedRequest");
//        this.onSucceedRequest.remove(handler);
//    }
//
//    public void addOnFailedRequestListener(EventHandler<FailedRequestEventArgs> handler) {
//        assertNotInitialized("onFailedRequest");
//        this.onFailedRequest.add(handler);
//    }
//
//    public void removeOnFailedRequestListener(EventHandler<FailedRequestEventArgs> handler) {
//        assertNotInitialized("onFailedRequest");
//        this.onFailedRequest.remove(handler);
//    }
//
//    public void addOnTopologyUpdatedListener(EventHandler<TopologyUpdatedEventArgs> handler) {
//        assertNotInitialized("onTopologyUpdated");
//        this.onTopologyUpdated.add(handler);
//    }
//
//    public void removeOnTopologyUpdatedListener(EventHandler<TopologyUpdatedEventArgs> handler) {
//        assertNotInitialized("onTopologyUpdated");
//        this.onTopologyUpdated.remove(handler);
//    }

    protected string $database;

    public function getDatabase(): string
    {
        return $this->database;
    }

    /**
     * Sets the default database
     *
     * @throws IllegalStateException
     */
    public function setDatabase(string $database): void
    {
        $this->assertNotInitialized('database');
        $this->database = $database;
    }


//
//    /**
//     * The client certificate to use for authentication
//     * @return Certificate to use
//     */
//    public KeyStore getCertificate() {
//        return _certificate;
//    }
//
//    /**
//     * The client certificate to use for authentication
//     * @param certificate Certificate to use
//     */
//    public void setCertificate(KeyStore certificate) {
//        assertNotInitialized("certificate");
//        _certificate = certificate;
//    }
//
//    /**
//     * Password used for private key encryption
//     * @return Private key password
//     */
//    public char[] getCertificatePrivateKeyPassword() {
//        return _certificatePrivateKeyPassword;
//    }
//
//    /**
//     * If private key is inside certificate is encrypted, you can specify password
//     * @param certificatePrivateKeyPassword Private key password
//     */
//    public void setCertificatePrivateKeyPassword(char[] certificatePrivateKeyPassword) {
//        assertNotInitialized("certificatePrivateKeyPassword");
//        _certificatePrivateKeyPassword = certificatePrivateKeyPassword;
//    }
//
//    public KeyStore getTrustStore() {
//        return _trustStore;
//    }
//
//    public void setTrustStore(KeyStore trustStore) {
//        this._trustStore = trustStore;
//    }
//
//    public abstract DatabaseSmuggler smuggler();

    abstract public function getRequestExecutor(string $databaseName = ''): RequestExecutor;

    //    @Override
//    public CleanCloseable aggressivelyCache() {
//        return aggressivelyCache(null);
//    }
//
//    @Override
//    public CleanCloseable aggressivelyCache(String database) {
//        return aggressivelyCacheFor(conventions.aggressiveCache().getDuration(), database);
//    }

    /**
     * @param InMemoryDocumentSessionOperations|RequestExecutor $object
     */
    public function registerEvents($object)
    {
        if (is_a($object, InMemoryDocumentSessionOperations::class)) {
            $this->_registerEventsForInMemoryDocumentSessionOperations($object);
            return;
        }

        if (is_a($object, RequestExecutor::class)) {
            $this->_registerEventsForRequestExecutor($object);
            return;
        }

        throw new InvalidArgumentException('Passed object must be instance of InMemoryDocumentSessionOperation or RequestExecutor');
    }

    private function _registerEventsForInMemoryDocumentSessionOperations(InMemoryDocumentSessionOperations $session): void
    {
//        for (EventHandler<BeforeStoreEventArgs> handler : onBeforeStore) {
//            session.addBeforeStoreListener(handler);
//        }
//
//            for (EventHandler<AfterSaveChangesEventArgs> handler : onAfterSaveChanges) {
//            session.addAfterSaveChangesListener(handler);
//        }
//
//            for (EventHandler<BeforeDeleteEventArgs> handler : onBeforeDelete) {
//            session.addBeforeDeleteListener(handler);
//        }
//
//            for (EventHandler<BeforeQueryEventArgs> handler : onBeforeQuery) {
//            session.addBeforeQueryListener(handler);
//        }
//
//            for (EventHandler<BeforeConversionToDocumentEventArgs> handler : onBeforeConversionToDocument) {
//            session.addBeforeConversionToDocumentListener(handler);
//        }
//
//            for (EventHandler<AfterConversionToDocumentEventArgs> handler : onAfterConversionToDocument) {
//            session.addAfterConversionToDocumentListener(handler);
//        }
//
//            for (EventHandler<BeforeConversionToEntityEventArgs> handler : onBeforeConversionToEntity) {
//            session.addBeforeConversionToEntityListener(handler);
//        }
//
//            for (EventHandler<AfterConversionToEntityEventArgs> handler : onAfterConversionToEntity) {
//            session.addAfterConversionToEntityListener(handler);
//        }
//
//            for (EventHandler<SessionClosingEventArgs> handler : onSessionClosing) {
//            session.addOnSessionClosingListener(handler);
//        }
    }

    private function _registerEventsForRequestExecutor(RequestExecutor $requestExecutor): void
    {

//        for (EventHandler<FailedRequestEventArgs> handler : onFailedRequest) {
//            requestExecutor.addOnFailedRequestListener(handler);
//        }
//
//        for (EventHandler<TopologyUpdatedEventArgs> handler : onTopologyUpdated) {
//            requestExecutor.addOnTopologyUpdatedListener(handler);
//        }
//
//        for (EventHandler<BeforeRequestEventArgs> handler : onBeforeRequest) {
//            requestExecutor.addOnBeforeRequestListener(handler);
//        }
//
//        for (EventHandler<SucceedRequestEventArgs> handler : onSucceedRequest) {
//            requestExecutor.addOnSucceedRequestListener(handler);
//        }
    }

    protected function afterSessionCreated(InMemoryDocumentSessionOperations $session): void
    {
        // todo: implement this
        // EventHelper.invoke(onSessionCreated, this, new SessionCreatedEventArgs(session));
    }

//    public abstract MaintenanceOperationExecutor maintenance();
//
//    public abstract OperationExecutor operations();
//
//    public abstract CleanCloseable setRequestTimeout(Duration timeout);
//
//    public abstract CleanCloseable setRequestTimeout(Duration timeout, String database);

    /**
     * @throws InvalidArgumentException
     */
    public function getEffectiveDatabase(?string $database = null): string
    {
        return $this->getEffectiveDatabaseForStore($this, $database);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function getEffectiveDatabaseForStore(DocumentStoreInterface $store, ?string $database = null): string
    {
        if ($database == null) {
            $database = $store->getDatabase();
        }

        if (StringUtils::isNotBlank($database)) {
            return $database;
        }

        throw new InvalidArgumentException("Cannot determine database to operate on. " .
            "Please either specify 'database' directly as an action parameter " .
            "or set the default database to operate on using 'DocumentStore->setDatabaseName' method. " .
            "Did you forget to pass 'databaseName' parameter?");
    }
}
