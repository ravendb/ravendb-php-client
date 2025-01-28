<?php

namespace tests\RavenDB\Test\Issues\RavenDB_16328_AnalyzersTest;

use Exception;
use RavenDB\Documents\DocumentStoreInterface;
use RavenDB\Documents\Indexes\Analysis\AnalyzerDefinition;
use RavenDB\Documents\Operations\Indexes\ResetIndexOperation;
use RavenDB\ServerWide\Operations\Analyzers\DeleteServerWideAnalyzerOperation;
use RavenDB\ServerWide\Operations\Analyzers\PutServerWideAnalyzersOperation;
use RavenDB\Type\Duration;
use tests\RavenDB\Infrastructure\TestRunGuard;
use tests\RavenDB\RemoteTestBase;
use Throwable;

class RavenDB_16328_AnalyzersTest extends RemoteTestBase
{
    public static string $analyzer = "using System.IO;\n" .
            "using Lucene.Net.Analysis;\n" .
            "using Lucene.Net.Analysis.Standard;\n" .
            "\n" .
            "namespace SlowTests.Data.RavenDB_14939\n" .
            "{\n" .
            "    public class MyAnalyzer : StandardAnalyzer\n" .
            "    {\n" .
            "        public MyAnalyzer()\n" .
            "            : base(Lucene.Net.Util.Version.LUCENE_30)\n" .
            "        {\n" .
            "        }\n" .
            "\n" .
            "        public override TokenStream TokenStream(string fieldName, TextReader reader)\n" .
            "        {\n" .
            "            return new ASCIIFoldingFilter(base.TokenStream(fieldName, reader));\n" .
            "        }\n" .
            "    }\n" .
            "}\n";

    public function testCanUseCustomAnalyzer(): void
    {
        TestRunGuard::disableTestIfLicenseNotAvailableForV6($this);

        $store = $this->getDocumentStore();
        try {
            $analyzerName = "MyAnalyzer";

            try {
                $store->executeIndex(new MyIndex($analyzerName));
                throw new Exception('It should throw exception before reaching this code');
            } catch (Throwable $exception) {
                $this->assertStringContainsString("Cannot find analyzer type '" . $analyzerName . "' for field: name", $exception->getMessage());
            }
            try {
                $analyzerDefinition = new AnalyzerDefinition();
                $analyzerDefinition->setName($analyzerName);
                $analyzerDefinition->setCode(self::$analyzer);

                $store->maintenance()->server()->send(new PutServerWideAnalyzersOperation($analyzerDefinition));

                $store->executeIndex(new MyIndex($analyzerName));

                $this->fill($store);

                $this->waitForIndexing($store);

                $this->_assertCount($store, MyIndex::class);

                $store->maintenance()->server()->send(new DeleteServerWideAnalyzerOperation($analyzerName));

                $store->maintenance()->send(new ResetIndexOperation((new MyIndex($analyzerName))->getIndexName()));

                // IndexErrors[]
                $errors = $this->waitForIndexingErrors($store, Duration::ofSeconds(10));
                $this->assertCount(1, $errors);
                $this->assertCount(1, $errors[0]->getErrors());
                $this->assertStringContainsString(
                    "Cannot find analyzer type '" . $analyzerName . "' for field: name",
                    $errors[0]->getErrors()[0]->getError()
                );
            } finally {
                $store->maintenance()->server()->send(new DeleteServerWideAnalyzerOperation($analyzerName));
            }
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

    private static function _assertCount(DocumentStoreInterface $store, ?string $index, int $expectedCount = 4): void
    {
        self::waitForIndexing($store);

        $session = $store->openSession();
        try {
            $results = $session->query(Customer::class, $index)
                ->noCaching()
                ->search("name", "Rogério*")
                ->toList();

            self::assertCount($expectedCount, $results);
        } finally {
            $session->close();
        }
    }
}
