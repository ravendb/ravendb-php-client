<?php

namespace tests\RavenDB;

use RavenDB\Documents\DocumentStore;
use RavenDB\Documents\DocumentStoreArray;
use RavenDB\Documents\DocumentStoreInterface;

use RavenDB\Exceptions\Cluster\NoLeaderException;
use RavenDB\Exceptions\Database\DatabaseDoesNotExistException;
use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Type\Url;
use RavenDB\Type\UrlArray;

use tests\RavenDB\Driver\RavenTestDriver;

class RemoteTestBase extends RavenTestDriver
{
    private static ?DocumentStoreInterface $globalServer = null;

    private static ?DocumentStoreInterface $globalSecureServer = null;

    private DocumentStoreArray $documentStores;

    public function __construct()
    {
        parent::__construct();

        $this->documentStores = new DocumentStoreArray();
    }

    public function getDocumentStore(string $databaseName = null, bool $secured = false): DocumentStoreInterface
    {
        $databaseName = $this->modifyDatabaseName($databaseName);

        $this->setupGlobalServer($secured);
        $globalServer = self::getGlobalServer($secured);


//        DatabaseRecord databaseRecord = new DatabaseRecord();
//        databaseRecord.setDatabaseName(name);
//
//        customizeDbRecord(databaseRecord);
//
//        CreateDatabaseOperation createDatabaseOperation = new CreateDatabaseOperation(databaseRecord);
//        documentStore.maintenance().server().send(createDatabaseOperation);

        $store =  new DocumentStore($databaseName);
        $store->setUrls($globalServer->getUrls());


//        if (secured) {
//            store.setCertificate(getTestClientCertificate());
//            store.setTrustStore(getTestClientCertificate());
//        }
//
//        customizeStore(store);
//
//        hookLeakedConnectionCheck(store);

        $store->initialize();

//        setupDatabase(store);
//
//        if (waitForIndexingTimeout != null) {
//            waitForIndexing(store, name, waitForIndexingTimeout);
//        }

        $this->documentStores->append($store);

        return $store;
    }

    private function modifyDatabaseName(?string $databaseName): string
    {
        if ($databaseName == null) {
            $databaseName = 'test_db';
        }

        $databaseName .= '_' . 1; // todo: change this to use increment database name  // from Java: index.incrementAndGet()

        return $databaseName;
    }

    private static function getGlobalServer(bool $secured = false): ?DocumentStoreInterface
    {
        return $secured ? self::$globalSecureServer : self::$globalServer;
    }

    private function setupGlobalServer(bool $secured)
    {
        if (self::getGlobalServer($secured) == null) {
            $this->runServer($secured);
        }
    }

    private function runServer(bool $secured): void
    {
        $urls = new UrlArray();
        $urls->append(new Url('http://live-test.ravendb.net'));

        $store = new DocumentStore('test.manager');
        $store->setUrls($urls);
        $store->getConventions()->disableTopologyUpdates();

        if ($secured) {
            self::$globalSecureServer = $store;
        } else {
            self::$globalServer = $store;
        }
    }

    /**
     * @throws IllegalStateException
     */
    public function cleanUp(DocumentStore $store): void
    {
        if (!in_array($store, $this->documentStores->getArrayCopy())) {
            return;
        }

        try {
            // todo: implement this
//            $store->maintenance()->server()->send(new DeleteDatabasesOperation($store->getDatabase(), true));
        } catch (DatabaseDoesNotExistException | NoLeaderException $exception) {
            // ignore
        }
    }
}
