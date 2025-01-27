<?php

namespace tests\RavenDB\Driver;

use Closure;
use PHPUnit\Framework\TestCase;
use RavenDB\Constants\DocumentsIndexing;
use RavenDB\Documents\DocumentStore;
use RavenDB\Documents\Indexes\IndexErrors;
use RavenDB\Documents\DocumentStoreInterface;
use RavenDB\Documents\Indexes\IndexState;
use RavenDB\Documents\Indexes\IndexErrorsArray;
use RavenDB\Documents\Operations\DatabaseStatistics;
use RavenDB\Documents\Operations\GetStatisticsOperation;
use RavenDB\Documents\Operations\IndexInformation;
use RavenDB\Documents\Operations\MaintenanceOperationExecutor;
use RavenDB\Documents\Operations\Revisions\ConfigureRevisionsOperation;
use RavenDB\Documents\Operations\Revisions\ConfigureRevisionsOperationResult;
use RavenDB\Documents\Operations\Revisions\RevisionsCollectionConfiguration;
use RavenDB\Documents\Operations\Revisions\RevisionsConfiguration;
use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Exceptions\TimeoutException;
use RavenDB\Http\Adapter\HttpClient;
use RavenDB\Type\Duration;
use RavenDB\Type\Url;
use RavenDB\Type\UrlArray;
use RavenDB\Utils\Stopwatch;
use RuntimeException;
use RavenDB\Documents\Operations\Indexes\GetIndexErrorsOperation;
use tests\RavenDB\Infrastructure\Graph\Genre;
use tests\RavenDB\Infrastructure\Graph\Movie;
use tests\RavenDB\Infrastructure\Graph\User;
use tests\RavenDB\Infrastructure\Graph\UserRating;

abstract class RavenTestDriver extends TestCase
{
    protected bool $disposed = false;

    public function isDisposed(): bool
    {
        return $this->disposed;
    }

    public static bool $debug = false;

//  @todo: implement method
    protected function hookLeakedConnectionCheck(DocumentStore $store): void
    {

    }
//    protected void hookLeakedConnectionCheck(DocumentStore store) {
//        store.addBeforeCloseListener((sender, event) -> {
//            try {
//                CloseableHttpClient httpClient = store.getRequestExecutor().getHttpClient();
//
//                Field connManager = httpClient.getClass().getDeclaredField("connManager");
//                connManager.setAccessible(true);
//                PoolingHttpClientConnectionManager connectionManager = (PoolingHttpClientConnectionManager) connManager.get(httpClient);
//
//                int leased = connectionManager.getTotalStats().getLeased();
//                if (leased > 0) {
//                    Thread.sleep(500);
//
//                    // give another try
//                    leased = connectionManager.getTotalStats().getLeased();
//
//                    if (leased > 0) {
//                        throw new IllegalStateException("Looks like you have leaked " + leased + " connections!");
//                    }
//
//                    /*  debug code to find actual connections
//                    Field poolField = connectionManager.getClass().getDeclaredField("pool");
//                    poolField.setAccessible(true);
//                    AbstractConnPool pool = (AbstractConnPool) poolField.get(connectionManager);
//                    Field leasedField = pool.getClass().getSuperclass().getDeclaredField("leased");
//                    leasedField.setAccessible(true);
//                    Set leasedConnections = (Set) leasedField.get(pool);*/
//                }
//            } catch (NoSuchFieldException | IllegalAccessException | InterruptedException e) {
//                throw new IllegalStateException("Unable to check for leaked connections", e);
//            }
//        });
//    }
//
//    protected static void reportError(Exception e) {
//        if (!debug) {
//            return;
//        }
//
//        if (e == null) {
//            throw new IllegalArgumentException("Exception can not be null");
//        }
//    }
//

    protected function reportInfo(string $message): void
    {

    }

    public function withFiddler(): void
    {
        HttpClient::useProxy('http://127.0.0.1:8866');
    }

    protected function setupDatabase(DocumentStoreInterface $documentStore): void
    {
        // empty by design
    }

    protected function runServerInternal(RavenServerLocator $locator, ?Callable $configureStore = null): DocumentStoreInterface
    {
        $this->reportInfo('Starting global server');

        $urls = new UrlArray();

        $arguments = $locator->getCommandArguments();
        $prefix = '--ServerUrl=';
        foreach ($arguments as $argument) {
            if (str_starts_with($argument, $prefix)) {
                $urls->append(new Url(substr($argument, strlen($prefix))));
            }
        }

        if (count($urls) == 0) {
            $this->reportInfo('Url is null');
            throw new IllegalStateException('Unable to start server');
        }

        $store = new DocumentStore();
        $store->setUrls($urls);
        $store->setDatabase('test.manager');
        $store->getConventions()->setDisableTopologyUpdates(true);

        if ($configureStore != null) {
            $configureStore($store);
        }

        $store->initialize();

        return $store;
    }
//    protected IDocumentStore runServerInternal(RavenServerLocator locator, Reference<Process> processReference, Consumer<DocumentStore> configureStore) throws Exception {
//        Process process = RavenServerRunner.run(locator);
//        processReference.value = process;
//
//        reportInfo("Starting global server");
//
//        String url = null;
//        InputStream stdout = process.getInputStream();
//
//        Stopwatch startupDuration = Stopwatch.createStarted();
//        BufferedReader reader = new BufferedReader(new InputStreamReader(stdout));
//
//        List<String> readLines = new ArrayList<>();
//
//        while (true) {
//            String line = reader.readLine();
//            readLines.add(line);
//
//            if (line == null) {
//                throw new RuntimeException(readLines
//                        .stream()
//                        .collect(Collectors.joining(System.lineSeparator())) + IOUtils.toString(process.getInputStream(), StandardCharsets.UTF_8));
//            }
//
//            if (startupDuration.elapsed(TimeUnit.MINUTES) >= 1) {
//                break;
//            }
//
//            String prefix = "Server available on: ";
//            if (line.startsWith(prefix)) {
//                url = line.substring(prefix.length());
//                break;
//            }
//        }
//
//        if (url == null) {
//            reportInfo("Url is null");
//
//            try {
//                process.destroyForcibly();
//            } catch (Exception e) {
//                reportError(e);
//            }
//
//            throw new IllegalStateException("Unable to start server");
//        }
//
//        DocumentStore store = new DocumentStore();
//        store.setUrls(new String[] { url });
//        store.setDatabase("test.manager");
//        store.getConventions().setDisableTopologyUpdates(true);
//
//        if (configureStore != null) {
//            configureStore.accept(store);
//        }
//
//        return store.initialize();
//    }

    public static function waitForIndexing(
        DocumentStoreInterface $store,
        ?string $database = null,
        ?Duration $timeout = null,
        ?string $nodeTag = null
    ): void {
        $admin = $store->maintenance()->forDatabase($database);

        if ($timeout == null) {
            $timeout = Duration::ofMinutes(1);
        }

        $sp = Stopwatch::createStarted();

        while ($sp->elapsedInMillis() < $timeout->toMillis()) {

            /** @var DatabaseStatistics $databaseStatistics */
            $databaseStatistics = $admin->send(new GetStatisticsOperation("wait-for-indexing", $nodeTag));

            $indexes = array_filter(
                $databaseStatistics->getIndexes()->getArrayCopy(),
                function(IndexInformation $index) {
                    return !$index->getState()->isDisabled();
                }
            );

            $indexesAreValid = true;
            $hasError = false;

            /** @var IndexInformation $index */
            foreach ($indexes as $index) {
                if ($index->isStale() || str_starts_with($index->getName(), DocumentsIndexing::SIDE_BY_SIDE_INDEX_NAME_PREFIX)) {
                    $indexesAreValid = false;
                }

                if ($index->getState()->isError()) {
                    $hasError = true;
                }
            }

            if ($hasError) {
                break;
            }

            if ($indexesAreValid) {
                return;
            }

            try {
                usleep(100);
            } catch (\Throwable $e) {
                throw new RuntimeException($e);
            }
        }

        $errors = $admin->send(new GetIndexErrorsOperation());
        $allIndexErrorsText = "";
        $formatIndexErrors = function(IndexErrors $indexErrors): string {
            $errorsListText = implode(PHP_EOL, array_map(function($x) {return '-' . $x->getError();}, $indexErrors->getErrors()->getArrayCopy()));
            return "Index " . $indexErrors->getName() . " (" . count($indexErrors->getErrors()) . " errors): " . PHP_EOL . $errorsListText;
        };
        if (!empty($errors)) {
            $allIndexErrorsText = implode(PHP_EOL, array_map($formatIndexErrors, $errors->getArrayCopy()));
        }

        throw new TimeoutException("The indexes stayed stale for more than " . $timeout->getSeconds() . "." . $allIndexErrorsText);
    }

    public static function waitForIndexingErrors(?DocumentStoreInterface $store, ?Duration $timeout, string ...$indexNames): IndexErrorsArray
    {
        if ($timeout == null) {
            $timeout = Duration::ofSeconds(15);
        }

        $sw = Stopwatch::createStarted();

        while ($sw->elapsed() < $timeout->getSeconds()) {
            /** @var IndexErrorsArray $indexes */
            $indexes = $store->maintenance()->send(new GetIndexErrorsOperation($indexNames));

            /** @var IndexErrors $index */
            foreach ($indexes as $index) {
                if ($index->getErrors() != null && count($index->getErrors()) > 0) {
                    return $indexes;
                }
            }

            usleep(32000);
        }

        throw new TimeoutException("Got no index error for more than " . $timeout->toString());
    }

//    protected boolean waitForDocumentDeletion(IDocumentStore store, String id) throws InterruptedException {
//        Stopwatch sw = Stopwatch.createStarted();
//
//        while (sw.elapsed(TimeUnit.MILLISECONDS) <= 10_000) {
//            try (IDocumentSession session = store.openSession()) {
//                if (!$session->advanced().exists(id)) {
//                    return true;
//                }
//            }
//
//            Thread.sleep(100);
//        }
//
//        return false;
//    }

    /**
     * @param Closure $act
     * @param mixed $expectedValue
     * @param Duration|null $timeout
     * @return mixed
     */
    protected static function waitForValue(Closure $act, $expectedValue, ?Duration $timeout = null)
    {
        if ($timeout == null) {
            $timeout = Duration::ofSeconds(15);
        }

        $sw = Stopwatch::createStarted();
        do {
            try {
                $currentVal = $act();
                if ($expectedValue == $currentVal) {
                    return $currentVal;
                }

                if ($sw->elapsed() > $timeout->getSeconds()) {
                    throw new RuntimeException('Expected value: ' . $currentVal . ', never received.');
                }
            } catch (\Throwable $exception) {
                if ($sw->elapsed() > $timeout->getSeconds()) {
                    throw new RuntimeException($exception);
                }
            }

            usleep(16000); // 16 milliseconds
        } while (true);
    }

//    protected static void killProcess(Process p) {
//        if (p != null && p.isAlive()) {
//            reportInfo("Kill global server");
//
//            try {
//                p.destroyForcibly();
//            } catch (Exception e) {
//                reportError(e);
//            }
//        }
//    }
//
//    public void waitForUserToContinueTheTest(IDocumentStore store) {
//        String databaseNameEncoded = UrlUtils.escapeDataString(store.getDatabase());
//        String documentsPage = store.getUrls()[0] + "/studio/index.html#databases/documents?&database=" + databaseNameEncoded + "&withStop=true&disableAnalytics=true";
//
//        openBrowser(documentsPage);
//
//        do {
//            try {
//                Thread.sleep(500);
//            } catch (InterruptedException ignored) {
//            }
//
//            try (IDocumentSession session = store.openSession()) {
//                if ($session->load(ObjectNode.class, "Debug/Done") != null) {
//                    break;
//                }
//            }
//
//        } while (true);
//    }
//
//    protected void openBrowser(String url) {
//        System.out.println(url);
//
//        if (Desktop.isDesktopSupported()) {
//            Desktop desktop = Desktop.getDesktop();
//            try {
//                desktop.browse(new URI(url));
//            } catch (IOException | URISyntaxException e) {
//                throw new RuntimeException(e);
//            }
//        } else {
//            Runtime runtime = Runtime.getRuntime();
//            try {
//                runtime.exec("xdg-open " + url);
//            } catch (IOException e) {
//                throw new RuntimeException(e);
//            }
//        }
//    }

    protected static function setupRevisions(DocumentStoreInterface $store, bool $purgeOnDelete, int $minimumRevisionsToKeep): ConfigureRevisionsOperationResult
    {
        $revisionsConfiguration = new RevisionsConfiguration();
        $defaultCollection = new RevisionsCollectionConfiguration();
        $defaultCollection->setPurgeOnDelete($purgeOnDelete);
        $defaultCollection->setMinimumRevisionsToKeep($minimumRevisionsToKeep);

        $revisionsConfiguration->setDefaultConfig($defaultCollection);
        $operation = new ConfigureRevisionsOperation($revisionsConfiguration);

        return $store->maintenance()->send($operation);
    }
}
