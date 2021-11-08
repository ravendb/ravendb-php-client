<?php

namespace RavenDB\Documents;

use InvalidArgumentException;
use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Session\InMemoryDocumentSessionOperations;
use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Http\RequestExecutor;
use RavenDB\Type\UrlArray;
use RavenDB\Utils\StringUtils;

abstract class DocumentStoreBase implements DocumentStoreInterface
{
    private string $database;

    protected bool $disposed = false;
    protected bool $initialized = false;

    protected UrlArray $urls;

    private ?DocumentConventions $documentConventions = null;


    public function __construct(string $database)
    {
        $this->database = $database;
        $this->urls = new UrlArray();
    }

    abstract public function initialize(): DocumentStoreInterface;

    abstract public function getRequestExecutor(string $databaseName = null): RequestExecutor;


    public function getDatabase(): string
    {
        return $this->database;
    }

    public function setDatabase(string $database): void
    {
        $this->database = $database;
    }

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
        $this->urls = $urls;
    }

    public function getConventions(): DocumentConventions
    {
        if ($this->documentConventions == null) {
            $this->documentConventions = new DocumentConventions();
        }

        return $this->documentConventions;
    }

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

    public function isDisposed(): bool
    {
        return $this->disposed;
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

    protected function registerEvents(InMemoryDocumentSessionOperations $session)
    {
        // todo: implement this

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

    public function getLastTransactionIndex(string $database): int
    {
//        int index = _lastRaftIndexPerDatabase.get($database);
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

    protected function afterSessionCreated(InMemoryDocumentSessionOperations $session): void
    {
        // todo: implement this
        // EventHelper.invoke(onSessionCreated, this, new SessionCreatedEventArgs(session));
    }
}
