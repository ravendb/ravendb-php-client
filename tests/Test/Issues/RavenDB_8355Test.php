<?php

namespace tests\RavenDB\Test\Issues;

use Exception;
use RavenDB\Documents\DocumentStore;
use RavenDB\Documents\Operations\Sorters\DeleteSorterOperation;
use RavenDB\Documents\Operations\Sorters\PutSortersOperation;
use RavenDB\Documents\Queries\Sorting\SorterDefinition;
use RavenDB\Exceptions\Documents\Compilation\SorterCompilationException;
use RavenDB\Exceptions\Documents\Sorters\SorterDoesNotExistException;
use RavenDB\Exceptions\RavenException;
use tests\RavenDB\Infrastructure\Entity\Company;
use tests\RavenDB\Infrastructure\TestRunGuard;
use tests\RavenDB\RemoteTestBase;
use Throwable;

class RavenDB_8355Test extends RemoteTestBase
{
    public static string $sorterCode = "using System;\n" .
    "using System.Collections.Generic;\n" .
    "using Lucene.Net.Index;\n" .
    "using Lucene.Net.Search;\n" .
    "using Lucene.Net.Store;\n" .
    "\n" .
    "namespace SlowTests.Data.RavenDB_8355\n" .
    "{\n" .
    "    public class MySorter : FieldComparator\n" .
    "    {\n" .
    "        private readonly string _args;\n" .
    "\n" .
    "        public MySorter(string fieldName, int numHits, int sortPos, bool reversed, List<string> diagnostics)\n" .
    "        {\n" .
    "            _args = $\"{fieldName}:{numHits}:{sortPos}:{reversed}\";\n" .
    "        }\n" .
    "\n" .
    "        public override int Compare(int slot1, int slot2)\n" .
    "        {\n" .
    "            throw new InvalidOperationException($\"Catch me: {_args}\");\n" .
    "        }\n" .
    "\n" .
    "        public override void SetBottom(int slot)\n" .
    "        {\n" .
    "            throw new InvalidOperationException($\"Catch me: {_args}\");\n" .
    "        }\n" .
    "\n" .
    "        public override int CompareBottom(int doc, IState state)\n" .
    "        {\n" .
    "            throw new InvalidOperationException($\"Catch me: {_args}\");\n" .
    "        }\n" .
    "\n" .
    "        public override void Copy(int slot, int doc, IState state)\n" .
    "        {\n" .
    "            throw new InvalidOperationException($\"Catch me: {_args}\");\n" .
    "        }\n" .
    "\n" .
    "        public override void SetNextReader(IndexReader reader, int docBase, IState state)\n" .
    "        {\n" .
    "            throw new InvalidOperationException($\"Catch me: {_args}\");\n" .
    "        }\n" .
    "\n" .
    "        public override IComparable this[int slot] => throw new InvalidOperationException($\"Catch me: {_args}\");\n" .
    "    }\n" .
    "}\n";

    public function testCanUseCustomSorter(): void
    {
        TestRunGuard::disableTestForRaven6AndLater($this);

        $sorterDefinition = new SorterDefinition();
        $sorterDefinition->setName("MySorter");
        $sorterDefinition->setCode(self::$sorterCode);

        $operation = new PutSortersOperation($sorterDefinition);

        $store = $this->getDocumentStore();
        try {
            $store->maintenance()->send($operation);

            $session = $store->openSession();
            try {
                $company1 = new Company();
                $company1->setName("C1");
                $session->store($company1);

                $company2 = new Company();
                $company2->setName("C2");
                $session->store($company2);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $this->canUseSorterInternal(RavenException::class, $store, "Catch me: name:2:0:False", "Catch me: name:2:0:True");
        } finally {
            $store->close();
        }
    }

    public function testCanUseCustomSorterWithOperations(): void
    {
        TestRunGuard::disableTestForRaven6AndLater($this);

        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $company1 = new Company();
                $company1->setName("C1");
                $session->store($company1);

                $company2 = new Company();
                $company2->setName("C2");
                $session->store($company2);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $this->canUseSorterInternal(SorterDoesNotExistException::class, $store, "There is no sorter with 'MySorter' name", "There is no sorter with 'MySorter' name");

            $sorterDefinition = new SorterDefinition();
            $sorterDefinition->setName("MySorter");
            $sorterDefinition->setCode(self::$sorterCode);

            $operation = new PutSortersOperation($sorterDefinition);
            $store->maintenance()->send($operation);

            // checking if we can send again same sorter
            $store->maintenance()->send(new PutSortersOperation($sorterDefinition));

            $this->canUseSorterInternal(RavenException::class, $store, "Catch me: name:2:0:False", "Catch me: name:2:0:True");

            self::$sorterCode = str_replace("Catch me", "Catch me 2", self::$sorterCode);

            // checking if we can update sorter
            $sorterDefinition2 = new SorterDefinition();
            $sorterDefinition2->setName("MySorter");
            $sorterDefinition2->setCode(self::$sorterCode);
            $store->maintenance()->send(new PutSortersOperation($sorterDefinition2));

            try {
                $otherDefinition = new SorterDefinition();
                $otherDefinition->setName("MySorter_OtherName");
                $otherDefinition->setCode(self::$sorterCode);
                $store->maintenance()->send(new PutSortersOperation($otherDefinition));
                throw new Exception('It should throw exception before reaching this code');
            } catch (Throwable $exception) {
                $this->assertInstanceOf(SorterCompilationException::class, $exception);
                $this->assertStringContainsString("Could not find type 'MySorter_OtherName' in given assembly.", $exception->getMessage());
            }

            $this->canUseSorterInternal(RavenException::class, $store, "Catch me 2: name:2:0:False", "Catch me 2: name:2:0:True");

            $store->maintenance()->send(new DeleteSorterOperation("MySorter"));

            $this->canUseSorterInternal(SorterDoesNotExistException::class, $store, "There is no sorter with 'MySorter' name", "There is no sorter with 'MySorter' name");
        } finally {
            $store->close();
        }
    }

    private static function canUseSorterInternal(?string $expectedClass, ?DocumentStore $store, ?string $asc, ?string $desc): void
    {
        $session = $store->openSession();
        try {
            try {
                $session
                    ->advanced()
                    ->rawQuery(Company::class, "from Companies order by custom(name, 'MySorter')")
                    ->toList();

                throw new Exception('It should throw exception before reaching this code');
            } catch (Throwable $exception) {
                self::assertInstanceOf($expectedClass, $exception);
                self::assertStringContainsString($asc, $exception->getMessage());
            }

            try {
                $session
                    ->query(Company::class)
                    ->orderBy("name", "MySorter")
                    ->toList();

                throw new Exception('It should throw exception before reaching this code');
            } catch (Throwable $exception) {
                self::assertInstanceOf($expectedClass, $exception);
                self::assertStringContainsString($asc, $exception->getMessage());
            }

            try {
                $session
                    ->advanced()
                    ->rawQuery(Company::class, "from Companies order by custom(name, 'MySorter') desc")
                    ->toList();

                throw new Exception('It should throw exception before reaching this code');
            } catch (Throwable $exception) {
                self::assertInstanceOf($expectedClass, $exception);
                self::assertStringContainsString($desc, $exception->getMessage());
            }

            try {
                $session
                    ->query(Company::class)
                    ->orderByDescending("name", "MySorter")
                    ->toList();

                throw new Exception('It should throw exception before reaching this code');
            } catch (Throwable $exception) {
                self::assertInstanceOf($expectedClass, $exception);
                self::assertStringContainsString($desc, $exception->getMessage());
            }

        } finally {
            $session->close();
        }
    }
}
