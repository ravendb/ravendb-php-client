<?php

namespace tests\RavenDB\Test\Issues\RavenDB_14939Test;

use Exception;
use RavenDB\Documents\DocumentStoreInterface;
use RavenDB\Documents\Indexes\Analysis\AnalyzerDefinition;
use RavenDB\Documents\Indexes\IndexErrors;
use RavenDB\Documents\Operations\Analyzers\DeleteAnalyzerOperation;
use RavenDB\Documents\Operations\Analyzers\PutAnalyzersOperation;
use RavenDB\Documents\Operations\Indexes\ResetIndexOperation;
use RavenDB\Exceptions\Documents\Compilation\IndexCompilationException;
use RavenDB\Type\Duration;
use tests\RavenDB\RemoteTestBase;
use tests\RavenDB\Test\Issues\RavenDB_16328_AnalyzersTest\RavenDB_16328_AnalyzersTest;
use Throwable;

class RavenDB_14939Test extends RemoteTestBase
{
    public function testCanUseCustomAnalyzerWithOperations(): void
    {
        $store = $this->getDocumentStore();
        try {
            $analyzerName = $store->getDatabase();

            try {
                $store->executeIndex(new MyIndex($analyzerName));
                throw new Exception('It should throw exception before reaching this code');
            } catch (Throwable $exception) {
                $this->assertInstanceOf(IndexCompilationException::class, $exception);
                $this->assertStringContainsString("Cannot find analyzer type '" . $analyzerName . "' for field: name", $exception->getMessage());
            }

            $analyzerDefinition = new AnalyzerDefinition();
            $analyzerDefinition->setName($analyzerName);
            $analyzerDefinition->setCode(self::getAnalyzer($analyzerName));
            $store->maintenance()->send(new PutAnalyzersOperation($analyzerDefinition));

            $store->executeIndex(new MyIndex($analyzerName));

            self::fill($store);

            $this->waitForIndexing($store);

            $this->_assertCount(MyIndex::class, $store);

            $store->maintenance()->send(new DeleteAnalyzerOperation($analyzerName));

            $store->maintenance()->send(new ResetIndexOperation((new MyIndex($analyzerName))->getIndexName()));

            /** @var array<IndexErrors> $errors */
            $errors = $this->waitForIndexingErrors($store, Duration::ofSeconds(10));
            $this->assertCount(1, $errors);
            $this->assertCount(1, $errors[0]->getErrors());

            $this->assertStringContainsString(
                "Cannot find analyzer type '" . $analyzerName . "' for field: name",
                $errors[0]->getErrors()[0]->getError()
            );

        } finally {
            $store->close();
        }
    }

    private static function fill(DocumentStoreInterface $store): void
    {
        $session = $store->openSession();
        try {
            $c1 = new Customer();
            $c1->setName("Rogério");
            $session->store($c1);

            $c2 = new Customer();
            $c2->setName("Rogerio");
            $session->store($c2);

            $c3 = new Customer();
            $c3->setName("Paulo Rogerio");
            $session->store($c3);

            $c4 = new Customer();
            $c4->setName("Paulo Rogério");
            $session->store($c4);

            $session->saveChanges();
        } finally {
            $session->close();
        }
    }

    private static function _assertCount(?string $indexClass, DocumentStoreInterface $store): void
    {
        self::waitForIndexing($store);

        $session = $store->openSession();
        try {
            $results = $session->query(Customer::class, $indexClass)
                    ->noCaching()
                    ->search("name", "Rogério*");

            self::assertEquals(4, $results->count());
        } finally {
            $session->close();
        }
    }

    private static function getAnalyzer(?string $analyzerName): string
    {
        return str_replace("MyAnalyzer", $analyzerName, RavenDB_16328_AnalyzersTest::$analyzer);
    }
}
