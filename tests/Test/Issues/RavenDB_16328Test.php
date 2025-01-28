<?php

namespace tests\RavenDB\Test\Issues;

use Exception;
use RavenDB\Documents\Queries\Sorting\SorterDefinition;
use RavenDB\Exceptions\Documents\Compilation\SorterCompilationException;
use RavenDB\ServerWide\Sorters\DeleteServerWideSorterOperation;
use RavenDB\ServerWide\Sorters\PutServerWideSortersOperation;
use tests\RavenDB\Infrastructure\Entity\Company;
use tests\RavenDB\Infrastructure\TestRunGuard;
use tests\RavenDB\RemoteTestBase;
use Throwable;

class RavenDB_16328Test extends RemoteTestBase
{
    public function testCanUseCustomSorter(): void
    {
        TestRunGuard::disableTestIfLicenseNotAvailableForV6($this);

        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $c1 = new Company();
                $c1->setName("C1");

                $c2 = new Company();
                $c2->setName("C2");

                $session->store($c1);
                $session->store($c2);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $sorterName = $store->getDatabase();

            $sorterCode = $this->getSorter($sorterName);

            $sorterDefinition = new SorterDefinition();
            $sorterDefinition->setName($sorterName);
            $sorterDefinition->setCode($sorterCode);
            $store->maintenance()->server()->send(new PutServerWideSortersOperation($sorterDefinition));

            // checking if we can send again same sorter
            $store->maintenance()->server()->send(new PutServerWideSortersOperation($sorterDefinition));

            $sorterCode = str_replace("Catch me", "Catch me 2", $sorterCode);

            // checking if we can update sorter
            $updatedSorter = new SorterDefinition();
            $updatedSorter->setName($sorterName);
            $updatedSorter->setCode($sorterCode);
            $store->maintenance()->server()->send(new PutServerWideSortersOperation($updatedSorter));

            // We should not be able to add sorter with non-matching name
            $finalSorterCode = $sorterCode;

            try {
                $invalidSorter = new SorterDefinition();
                $invalidSorter->setName($sorterName . "_OtherName");
                $invalidSorter->setCode($finalSorterCode);
                $store->maintenance()->server()->send(new PutServerWideSortersOperation($invalidSorter));

                throw new Exception('It should throw exception before reaching this code');
            } catch (Throwable $exception) {
                $this->assertInstanceOf(SorterCompilationException::class, $exception);
            }

            $store->maintenance()->server()->send(new DeleteServerWideSorterOperation($sorterName));
        } finally {
            $store->close();
        }
    }

    private static function getSorter(?string $sorterName): string
    {
        return str_replace("MySorter", $sorterName, RavenDB_8355Test::$sorterCode);
    }
}
