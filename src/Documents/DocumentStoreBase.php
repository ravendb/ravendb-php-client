<?php

namespace RavenDB\Documents;

use Closure;
use InvalidArgumentException;
use RavenDB\Auth\AuthOptions;
use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Identity\HiLoIdGeneratorInterface;
use RavenDB\Documents\Indexes\AbstractIndexCreationTaskArray;
use RavenDB\Documents\Indexes\AbstractIndexCreationTaskInterface;
use RavenDB\Documents\Indexes\IndexCreation;
use RavenDB\Documents\Operations\Indexes\PutIndexesOperation;
use RavenDB\Documents\Session\InMemoryDocumentSessionOperations;
use RavenDB\Documents\TimeSeries\TimeSeriesOperations;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Exceptions\MalformedURLException;
use RavenDB\Http\RequestExecutor;
use RavenDB\Primitives\ClosureArray;
use RavenDB\Primitives\EventHelper;
use RavenDB\Type\Url;
use RavenDB\Type\UrlArray;
use RavenDB\Utils\StringUtils;

// !status: LOGIC COPIED - IN PROGRESS
abstract class DocumentStoreBase implements DocumentStoreInterface
{
    private ?ClosureArray $onBeforeStore = null;
    private ?ClosureArray $onAfterSaveChanges = null;
    private ?ClosureArray $onBeforeDelete = null;
    private ?ClosureArray $onBeforeQuery = null;
    private ?ClosureArray $onSessionCreated = null;
    private ?ClosureArray $onSessionClosing = null;

    private ?ClosureArray $onBeforeConversionToDocument = null;
    private ?ClosureArray $onAfterConversionToDocument = null;
    private ?ClosureArray $onBeforeConversionToEntity = null;
    private ?ClosureArray $onAfterConversionToEntity = null;
    private ?ClosureArray $onBeforeRequest = null;
    private ?ClosureArray $onSucceedRequest = null;

    private ?ClosureArray $onFailedRequest = null;
    private ?ClosureArray $onTopologyUpdated = null;


    public function __construct()
    {
        $this->database = null;
        $this->urls = null;

        $this->onBeforeStore = new ClosureArray();
        $this->onAfterSaveChanges = new ClosureArray();
        $this->onBeforeDelete = new ClosureArray();
        $this->onBeforeQuery = new ClosureArray();
        $this->onSessionCreated = new ClosureArray();
        $this->onSessionClosing = new ClosureArray();

        $this->onBeforeConversionToDocument = new ClosureArray();
        $this->onAfterConversionToDocument = new ClosureArray();
        $this->onBeforeConversionToEntity = new ClosureArray();
        $this->onAfterConversionToEntity = new ClosureArray();
        $this->onBeforeRequest = new ClosureArray();
        $this->onSucceedRequest = new ClosureArray();

        $this->onFailedRequest = new ClosureArray();
        $this->onTopologyUpdated = new ClosureArray();

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

    public abstract function getHiLoIdGenerator(): ?HiLoIdGeneratorInterface;

    public abstract function getIdentifier(): ?string;
//
//    public abstract void setIdentifier(String identifier);

    abstract public function initialize(): DocumentStoreInterface;

//    public abstract IDocumentSession openSession();
//
//    public abstract IDocumentSession openSession(String database);
//
//    public abstract IDocumentSession openSession(SessionOptions sessionOptions);

    public function executeIndex(AbstractIndexCreationTaskInterface $task, ?string $database = null): void
    {
        $this->assertInitialized();
        $task->execute($this, $this->conventions, $this->database);
    }

    /**
     * @param array|AbstractIndexCreationTaskArray $tasks
     * @param string|null $database
     */
    public function executeIndexes(AbstractIndexCreationTaskArray|array $tasks, ?string $database = null): void
    {
        if (is_array($tasks)) {
            $tasks = AbstractIndexCreationTaskArray::fromArray($tasks);
        }
        $this->assertInitialized();
        $indexesToAdd = IndexCreation::createIndexesToAdd($tasks, $this->conventions);

        $this->maintenance()
                ->forDatabase($this->getEffectiveDatabase($database))
                ->send(new PutIndexesOperation($indexesToAdd));
    }

    private ?TimeSeriesOperations $timeSeriesOperation = null;

    public function timeSeries(): TimeSeriesOperations
    {
        if ($this->timeSeriesOperation == null) {
            $this->timeSeriesOperation = new TimeSeriesOperations($this);
        }

        return $this->timeSeriesOperation;
    }

    private ?DocumentConventions $conventions = null;

    public function & getConventions(): DocumentConventions
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


    protected ?UrlArray $urls = null;

    public function getUrls(): ?UrlArray
    {
        return $this->urls;
    }

    /**
     * @param UrlArray|Url|array|string|null $value
     * @throws IllegalStateException
     */
    public function setUrls($value = null): void
    {
        $this->assertNotInitialized('urls');

        $urls = $this->_convertToUrlArray($value);

        $this->urls = $urls;
    }

    /**
     * @param UrlArray|Url|array|string|null $urls
     * @return UrlArray
     */
    private function _convertToUrlArray($urls): UrlArray
    {
        if ($urls == null) {
            throw new IllegalArgumentException("Url is null");
        }

        $urlArray = null;

        if (is_string($urls)) {
            $urls = [$urls];
        }

        if (is_array($urls)) {
            $urlArray = new UrlArray();
            foreach ($urls as $url) {
                if ($url == null) {
                    throw new IllegalArgumentException("Urls cannot contain null");
                }
                if (is_string($url)) {
                    try {
                        $url = new Url($url);
                    } catch (MalformedURLException $exception) {
                        throw new IllegalArgumentException("The url '" . $url . "' is not valid");
                    }
                }

                $urlArray->append($url);
            }
        }

        if ($urls instanceof Url) {
            $urlArray = new UrlArray();
            $urlArray->append($urls);
        }

        if ($urls instanceof UrlArray) {
            $urlArray = $urls;
        }

        if ($urlArray == null) {
            throw new IllegalArgumentException("Urls invalid value");
        }

        /** @var Url $urlItem */
        foreach ($urlArray as $urlItem) {
            $urlItem->setValue(StringUtils::stripEnd($urlItem->getValue(), '/'));
        }

        return $urlArray;
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
//    public abstract BulkInsertOperation bulkInsert(String database = '', ?BulkInsertOptions $options = null);
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


    public function addBeforeStoreListener(Closure $handler): void
    {
        $this->onBeforeStore->append($handler);
    }
    public function removeBeforeStoreListener(Closure $handler) {
        $this->onBeforeStore->removeValue($handler);
    }

    /** AfterSaveChangesEventArgs */
    public function addAfterSaveChangesListener(Closure $handler): void
    {
        $this->onAfterSaveChanges->append($handler);
    }

    /** AfterSaveChangesEventArgs */
    public function removeAfterSaveChangesListener(Closure $handler): void
    {
        $this->onAfterSaveChanges->removeValue($handler);
    }

    public function addBeforeDeleteListener(Closure $handler): void
    {
        $this->onBeforeDelete->append($handler);
    }
    public function removeBeforeDeleteListener(Closure $handler): void
    {
        $this->onBeforeDelete->removeValue($handler);
    }

    public function addBeforeQueryListener(Closure $handler): void
    {
        $this->onBeforeQuery->append($handler);
    }

    public function removeBeforeQueryListener(Closure $handler): void
    {
        $this->onBeforeQuery->removeValue($handler);
    }

    public function addOnSessionClosingListener(Closure $handler): void
    {
        $this->onSessionClosing->append($handler);
    }

    public function removeOnSessionClosingListener(Closure $handler): void
    {
        $this->onSessionClosing->removeValue($handler);
    }

    public function addBeforeConversionToDocumentListener(Closure $handler): void
    {
        $this->onBeforeConversionToDocument->append($handler);
    }

    public function removeBeforeConversionToDocumentListener(Closure $handler): void
    {
        $this->onBeforeConversionToDocument->removeValue($handler);
    }

    public function addAfterConversionToDocumentListener(Closure $handler): void
    {
        $this->onAfterConversionToDocument->append($handler);
    }

    public function removeAfterConversionToDocumentListener(Closure $handler): void
    {
        $this->onAfterConversionToDocument->removeValue($handler);
    }

    public function addBeforeConversionToEntityListener(Closure $handler): void
    {
        $this->onBeforeConversionToEntity->append($handler);
    }

    public function removeBeforeConversionToEntityListener(Closure $handler): void
    {
        $this->onBeforeConversionToEntity->removeValue($handler);
    }

    public function addAfterConversionToEntityListener(Closure $handler): void
    {
        $this->onAfterConversionToEntity->append($handler);
    }

    public function removeAfterConversionToEntityListener(Closure $handler): void
    {
        $this->onAfterConversionToEntity->removeValue($handler);
    }

//    public function addOnBeforeRequestListener(EventHandler<BeforeRequestEventArgs> handler) {
//        assertNotInitialized("onSucceedRequest");
//        $this->onBeforeRequest->append($handler);
//    }
//
//    public function removeOnBeforeRequestListener(EventHandler<BeforeRequestEventArgs> handler) {
//        assertNotInitialized("onSucceedRequest");
//        $this->onBeforeRequest->removeValue($handler);
//    }
//
//    public function addOnSucceedRequestListener(EventHandler<SucceedRequestEventArgs> handler) {
//        assertNotInitialized("onSucceedRequest");
//        $this->onSucceedRequest->append($handler);
//    }
//
//    public function removeOnSucceedRequestListener(EventHandler<SucceedRequestEventArgs> handler) {
//        assertNotInitialized("onSucceedRequest");
//        $this->onSucceedRequest->removeValue($handler);
//    }
//
//    public function addOnFailedRequestListener(EventHandler<FailedRequestEventArgs> handler) {
//        assertNotInitialized("onFailedRequest");
//        $this->onFailedRequest->append($handler);
//    }
//
//    public function removeOnFailedRequestListener(EventHandler<FailedRequestEventArgs> handler) {
//        assertNotInitialized("onFailedRequest");
//        $this->onFailedRequest->removeValue($handler);
//    }
//
//    public function addOnTopologyUpdatedListener(EventHandler<TopologyUpdatedEventArgs> handler) {
//        assertNotInitialized("onTopologyUpdated");
//        $this->onTopologyUpdated->append($handler);
//    }
//
//    public function removeOnTopologyUpdatedListener(EventHandler<TopologyUpdatedEventArgs> handler) {
//        assertNotInitialized("onTopologyUpdated");
//        $this->onTopologyUpdated->removeValue($handler);
//    }

    protected ?string $database = null;

    public function getDatabase(): ?string
    {
        return $this->database;
    }

    /**
     * Sets the default database
     *
     * @throws IllegalStateException
     */
    public function setDatabase(?string $database): void
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
//    public function setCertificate(KeyStore certificate) {
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
//    public function setCertificatePrivateKeyPassword(char[] certificatePrivateKeyPassword) {
//        assertNotInitialized("certificatePrivateKeyPassword");
//        _certificatePrivateKeyPassword = certificatePrivateKeyPassword;
//    }
//
//    public KeyStore getTrustStore() {
//        return _trustStore;
//    }
//
//    public function setTrustStore(KeyStore trustStore) {
//        $this->_trustStore = trustStore;
//    }
//
//    public abstract DatabaseSmuggler smuggler();

    abstract public function getRequestExecutor(?string $database = null): RequestExecutor;

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
        foreach ($this->onBeforeStore as $handler) {
            $session->addBeforeStoreListener($handler);
        }

        foreach ($this->onAfterSaveChanges as $handler) {
            $session->addAfterSaveChangesListener($handler);
        }

        foreach ($this->onBeforeDelete as $handler) {
            $session->addBeforeDeleteListener($handler);
        }

        foreach ($this->onBeforeQuery as $handler) {
            $session->addBeforeQueryListener($handler);
        }

        foreach ($this->onBeforeConversionToDocument as $handler) {
            $session->addBeforeConversionToDocumentListener($handler);
        }

        foreach ($this->onAfterConversionToDocument as $handler) {
            $session->addAfterConversionToDocumentListener($handler);
        }

        foreach ($this->onBeforeConversionToEntity as $handler) {
            $session->addBeforeConversionToEntityListener($handler);
        }

        foreach ($this->onAfterConversionToEntity as $handler) {
            $session->addAfterConversionToEntityListener($handler);
        }

        foreach ($this->onSessionClosing as $handler) {
            $session->addOnSessionClosingListener($handler);
        }
    }

    private function _registerEventsForRequestExecutor(RequestExecutor $requestExecutor): void
    {
//        foreach ($this->onFailedRequest as $handler) {
//            $requestExecutor->addOnFailedRequestListener($handler);
//        }
//
//        foreach ($this->onTopologyUpdated as $handler) {
//            $requestExecutor->addOnTopologyUpdatedListener($handler);
//        }

        foreach ($this->onBeforeRequest as $handler) {
            $requestExecutor->addOnBeforeRequestListener($handler);
        }

        foreach ($this->onSucceedRequest as $handler) {
            $requestExecutor->addOnSucceedRequestListener($handler);
        }
    }

    protected function afterSessionCreated(InMemoryDocumentSessionOperations $session): void
    {
        // @todo: implement this
//        EventHelper::invoke($this->onSessionCreated, $this, new SessionCreatedEventArgs($session));
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
        return self::getEffectiveDatabaseForStore($this, $database);
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function getEffectiveDatabaseForStore(DocumentStoreInterface $store, ?string $database = null): string
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
