<?php

namespace tests\RavenDB;

use DateInterval;
use RavenDB\Documents\DocumentStore;
use RavenDB\Documents\DocumentStoreArray;
use RavenDB\Documents\DocumentStoreInterface;

use RavenDB\Exceptions\Cluster\NoLeaderException;
use RavenDB\Exceptions\Database\DatabaseDoesNotExistException;
use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Primitives\CleanCloseable;
use RavenDB\ServerWide\DatabaseRecord;
use RavenDB\ServerWide\Operations\CreateDatabaseOperation;
use RavenDB\ServerWide\Operations\DeleteDatabasesOperation;

use RavenDB\Type\Duration;
use RavenDB\Utils\AtomicInteger;
use RuntimeException;
use tests\RavenDB\Driver\RavenServerLocator;
use tests\RavenDB\Driver\RavenTestDriver;

// !class - IN PROGRESS
class RemoteTestBase extends RavenTestDriver implements CleanCloseable
{
    public ?SamplesTestBase $samples = null;
    public ?IndexesTestBase $indexes = null;
    public ?ReplicationTestBase2 $replication = null;

    private RavenServerLocator $locator;
    private RavenServerLocator $securedLocator;

    private static ?DocumentStoreInterface $globalServer = null;
//    private static Process globalServerProcess;

    private static ?DocumentStoreInterface $globalSecureServer = null;
//    private static Process globalSecuredServerProcess;

    private ?DocumentStoreArray $documentStores = null;

    private static ?AtomicInteger $index = null;

    public function __construct()
    {
        parent::__construct("");

        $this->locator = new TestServiceLocator();
        $this->securedLocator = new TestSecuredServiceLocator();

        $this->documentStores = new DocumentStoreArray();

        $this->samples = new SamplesTestBase($this);
        $this->indexes = new IndexesTestBase($this);
        $this->replication = new ReplicationTestBase2($this);

        if (self::$index == null) {
            self::$index = new AtomicInteger();
        }
    }

    protected function customizeDbRecord(DatabaseRecord &$dbRecord): void
    {

    }

    protected function customizeStore(DocumentStore &$store): void
    {

    }

//    public KeyStore getTestClientCertificate() throws IOException, GeneralSecurityException {
//        KeyStore clientStore = KeyStore.getInstance("PKCS12");
//        clientStore.load(new FileInputStream(securedLocator.getServerCertificatePath()), "".toCharArray());
//        return clientStore;
//    }
//
//    public KeyStore getTestCaCertificate() throws IOException, GeneralSecurityException {
//        String caPath = securedLocator.getServerCaPath();
//        if (caPath != null) {
//            KeyStore trustStore = KeyStore.getInstance("PKCS12");
//            trustStore.load(null, null);
//
//            CertificateFactory x509 = CertificateFactory.getInstance("X509");
//
//            try (InputStream source = new FileInputStream(new File(caPath))) {
//                Certificate certificate = x509.generateCertificate(source);
//                trustStore.setCertificateEntry("ca-cert", certificate);
//                return trustStore;
//            }
//        }
//
//        return null;
//    }

    /**
     * @throws IllegalStateException
     */
    private function runServer(bool $secured): DocumentStoreInterface
    {
        $store = $this->runServerInternal($this->getLocator($secured), function(DocumentStore &$store) use (&$secured) {
            if ($secured) {
                $store->setAuthOptions($this->securedLocator->getClientAuthOptions());
            }
        });

        if ($secured) {
            self::$globalSecureServer = $store;
        } else {
            self::$globalServer = $store;
        }

        return $store;
    }

//    @SuppressWarnings("UnusedReturnValue")
//    private IDocumentStore runServer(boolean secured) throws Exception {
//        Reference<Process> processReference = new Reference<>();
//        IDocumentStore store = runServerInternal(getLocator(secured), processReference, s -> {
//            if (secured) {
//                try {
//                    KeyStore clientCert = getTestClientCertificate();
//                    s.setCertificate(clientCert);
//                    s.setTrustStore(getTestCaCertificate());
//                } catch (Exception e) {
//                    throw ExceptionsUtils.unwrapException(e);
//                }
//            }
//        });
//        setGlobalServerProcess(secured, processReference.value);
//
//        if (secured) {
//            globalSecuredServer = store;
//        } else {
//            globalServer = store;
//        }
//
//        Runtime.getRuntime().addShutdownHook(new Thread(() -> killGlobalServerProcess(secured)));
//        return store;
//    }
//
    private function getLocator(bool $secured): RavenServerLocator
    {
        return $secured ? $this->securedLocator : $this->locator;
    }

    private static function getGlobalServer(bool $secured = false): ?DocumentStoreInterface
    {
        return $secured ? self::$globalSecureServer : self::$globalServer;
    }

//
//    private static Process getGlobalProcess(boolean secured) {
//        return secured ? globalSecuredServerProcess : globalServerProcess;
//    }
//
//    private static void setGlobalServerProcess(boolean secured, Process p) {
//        if (secured) {
//            globalSecuredServerProcess = p;
//        } else {
//            globalServerProcess = p;
//        }
//    }
//
//    private static void killGlobalServerProcess(boolean secured) {
//        Process p;
//        if (secured) {
//            p = globalSecuredServerProcess;
//            globalSecuredServerProcess = null;
//            globalSecuredServer.close();
//            globalSecuredServer = null;
//        } else {
//            p = globalServerProcess;
//            globalServerProcess = null;
//            globalServer.close();
//            globalServer = null;
//        }
//
//        killProcess(p);
//    }

    /**
     * @throws IllegalStateException
     */
    public function getSecuredDocumentStore(?string $databaseName = null): DocumentStoreInterface
    {
        return $this->getDocumentStore($databaseName, true);
    }

    /**
     * @throws IllegalStateException
     */
    public function getDocumentStore(?string $database = null, bool $secured = false, ?Duration $waitForIndexingTimeout = null ): DocumentStoreInterface
    {
        if ($database == null) {
            $database = 'test_db';
        }

        $name = $database . '_' . self::$index->incrementAndGet();
        $this->reportInfo("getDocumentStore for db " . $name . ".");

        if (self::getGlobalServer($secured) == null) {
            $this->runServer($secured);
        }

        $documentStore = self::getGlobalServer($secured);
        $databaseRecord = new DatabaseRecord();
        $databaseRecord->setDatabaseName($name);

        $this->customizeDbRecord($databaseRecord);

        $createDatabaseOperation = new CreateDatabaseOperation($databaseRecord);
        $documentStore->maintenance()->server()->send($createDatabaseOperation);

        $store =  new DocumentStore();
        $store->setUrls($documentStore->getUrls());
        $store->setDatabase($name);

        if ($secured) {
            $store->setAuthOptions($this->securedLocator->getClientAuthOptions());
        }

        $this->customizeStore($store);

        $this->hookLeakedConnectionCheck($store);
        $store->initialize();

        $documentStores = $this->documentStores;

        $store->addAfterCloseListener(function($sender, $event) use (&$documentStores) {
            $storeRemoved = true;
            /** @var DocumentStore $store */
            $store = $sender;
            foreach ($documentStores->getArrayCopy() as $storeInList) {
                $storeRemoved = $store === $storeInList ? false : $storeRemoved;
            }
            if ($storeRemoved) {
                return;
            }

            try {
                $store->maintenance()->server()->send(new DeleteDatabasesOperation($store->getDatabase(), true));
            } catch (DatabaseDoesNotExistException | NoLeaderException $exception) {
                // ignore
            }
        });

        $this->setupDatabase($store);

        if ($waitForIndexingTimeout != null) {
            $this->waitForIndexing($store, $name, $waitForIndexingTimeout);
        }

        $this->documentStores->append($store);

        return $store;
    }

    public function close(): void
    {
        if ($this->isDisposed()) {
            return;
        }

        $exceptions = [];

        foreach ($this->documentStores as $documentStore) {
            try {
                $documentStore->close();
            } catch (\Throwable $e) {
                $exceptions[] = $e;
            }
        }

        $this->disposed = true;

        if (count($exceptions) > 0) {
            $message = '';
            foreach ($exceptions as $exception) {
                $message .= $exception->getMessage() . ', ';
            }

            throw new RuntimeException($message);
        }
    }

    protected function waitForDocument(
        string $className,
        DocumentStoreInterface $store,
        string $docId,
        $predicate = null,
        int $timeout = 10000
    ): bool {
//        @todo: implement method

//        Stopwatch sw = Stopwatch.createStarted();
//        Exception ex = null;
//        while (sw.elapsed().toMillis() < timeout) {
//            try (IDocumentSession session = store.openSession(store.getDatabase())) {
//                try {
//                    T doc = session.load(clazz, docId);
//                    if (doc != null) {
//                        if (predicate == null || predicate.apply(doc)) {
//                            return true;
//                        }
//                    }
//                } catch (Exception e) {
//                    ex = e;
//                }
//            }
//
//            try {
//                Thread.sleep(100);
//            } catch (InterruptedException e) {
//                // empty
//            }
//        }

        return false;
    }

    /**
     * @throws IllegalStateException
     */
    public function cleanUp(DocumentStore $store): void
    {

    }


}
