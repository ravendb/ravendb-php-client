<?php

namespace tests\RavenDB\Test\Issues;

use Throwable;
use Exception;
use RavenDB\Type\Duration;
use tests\RavenDB\RemoteTestBase;
use tests\RavenDB\RavenTestHelper;
use RavenDB\Documents\Indexes\IndexDefinition;
use RavenDB\Documents\Indexes\IndexErrorsArray;
use tests\RavenDB\Infrastructure\Entity\Company;
use RavenDB\Documents\Operations\Indexes\PutIndexesOperation;
use RavenDB\Documents\Operations\Indexes\StopIndexingOperation;
use RavenDB\Documents\Operations\Indexes\GetIndexErrorsOperation;
use RavenDB\Documents\Operations\Indexes\DeleteIndexErrorsOperation;
use RavenDB\Exceptions\Documents\Indexes\IndexDoesNotExistException;

class RavenDB_6967Test extends RemoteTestBase
{
    public function testCanDeleteIndexErrors(): void
    {
        $store = $this->getDocumentStore();
        try {
            RavenTestHelper::assertNoIndexErrors($store);

            $store->maintenance()->send(new DeleteIndexErrorsOperation());


            try {
                $store->maintenance()->send(new DeleteIndexErrorsOperation([ "DoesNotExist" ]));
                throw new Exception('It should throw exception before reaching this code');
            } catch (Throwable $exception) {
                $this->assertInstanceOf(IndexDoesNotExistException::class, $exception);
            }

            $index1 = new IndexDefinition();
            $index1->setName("Index1");
            $index1->setMaps(["from doc in docs let x = 0 select new { Total = 3/x };"]);

            $store->maintenance()->send(new PutIndexesOperation($index1));

            $index2 = new IndexDefinition();
            $index2->setName("Index2");
            $index2->setMaps(["from doc in docs let x = 0 select new { Total = 4/x };"]);

            $store->maintenance()->send(new PutIndexesOperation($index2));

            $index3 = new IndexDefinition();
            $index3->setName("Index3");
            $index3->setMaps(["from doc in docs let x = 0 select new { Total = 5/x };"]);

            $store->maintenance()->send(new PutIndexesOperation($index3));

            $this->waitForIndexing($store);

            RavenTestHelper::assertNoIndexErrors($store);

            $store->maintenance()->send(new DeleteIndexErrorsOperation());

            $store->maintenance()->send(new DeleteIndexErrorsOperation([ "Index1", "Index2", "Index3" ]));

            try {
                $store->maintenance()->send(new DeleteIndexErrorsOperation([ "Index1", "DoesNotExist" ]));
                throw new Exception('It should throw exception before reaching this code');
            } catch (Throwable $exception) {
                $this->assertInstanceOf(IndexDoesNotExistException::class, $exception);
            }


            $session = $store->openSession();
            try {
                $session->store(new Company());
                $session->store(new Company());
                $session->store(new Company());

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $this->waitForIndexingErrors($store, Duration::ofMinutes(1), "Index1");
            $this->waitForIndexingErrors($store, Duration::ofMinutes(1), "Index2");
            $this->waitForIndexingErrors($store, Duration::ofMinutes(1), "Index3");

            $store->maintenance()->send(new StopIndexingOperation());

            /** @var IndexErrorsArray $indexErrors1 */
            $indexErrors1 = $store->maintenance()->send(new GetIndexErrorsOperation(["Index1"]));
            /** @var IndexErrorsArray $indexErrors2 */
            $indexErrors2 = $store->maintenance()->send(new GetIndexErrorsOperation(["Index2"]));
            /** @var IndexErrorsArray $indexErrors3 */
            $indexErrors3 = $store->maintenance()->send(new GetIndexErrorsOperation(["Index3"]));

            $this->assertGreaterThan(0, self::countAllErrors($indexErrors1));
            $this->assertGreaterThan(0, self::countAllErrors($indexErrors2));
            $this->assertGreaterThan(0, self::countAllErrors($indexErrors3));

            $store->maintenance()->send(new DeleteIndexErrorsOperation([ "Index2" ]));

            /** @var IndexErrorsArray $indexErrors1 */
            $indexErrors1 = $store->maintenance()->send(new GetIndexErrorsOperation(["Index1"]));
            /** @var IndexErrorsArray $indexErrors2 */
            $indexErrors2 = $store->maintenance()->send(new GetIndexErrorsOperation(["Index2"]));
            /** @var IndexErrorsArray $indexErrors3 */
            $indexErrors3 = $store->maintenance()->send(new GetIndexErrorsOperation(["Index3"]));

            $this->assertGreaterThan(0, self::countAllErrors($indexErrors1));
            $this->assertEquals(0, self::countAllErrors($indexErrors2));
            $this->assertGreaterThan(0, self::countAllErrors($indexErrors3));

            $store->maintenance()->send(new DeleteIndexErrorsOperation());

            /** @var IndexErrorsArray $indexErrors1 */
            $indexErrors1 = $store->maintenance()->send(new GetIndexErrorsOperation(["Index1"]));
            /** @var IndexErrorsArray $indexErrors2 */
            $indexErrors2 = $store->maintenance()->send(new GetIndexErrorsOperation(["Index2"]));
            /** @var IndexErrorsArray $indexErrors3 */
            $indexErrors3 = $store->maintenance()->send(new GetIndexErrorsOperation(["Index3"]));

            $this->assertEquals(0, self::countAllErrors($indexErrors1));
            $this->assertEquals(0, self::countAllErrors($indexErrors2));
            $this->assertEquals(0, self::countAllErrors($indexErrors3));

            RavenTestHelper::assertNoIndexErrors($store);

            $this->assertTrue(true);
        } finally {
            $store->close();
        }
    }

    /**
     * @param IndexErrorsArray $indexErrorsArray
     *
     * @return int
     */
    public static function countAllErrors(IndexErrorsArray $indexErrorsArray): int
    {
        $totalErrors = 0;
        foreach ($indexErrorsArray as $indexErrors) {
            $totalErrors += count($indexErrors->getErrors());
        }
        return $totalErrors;
}
}
