<?php

namespace tests\RavenDB\Test\Issues\RavenDB_16334Test;

use RavenDB\Type\Duration;
use tests\RavenDB\RemoteTestBase;

class RavenDB_16334Test extends RemoteTestBase
{
    public function testCanWaitForIndexesWithLoadAfterSaveChangesAllIndexes(): void
    {
        $this->canWaitForIndexesWithLoadAfterSaveChangesInternal(true);
    }

    public function testCanWaitForIndexesWithLoadAfterSaveChangesSingleIndex(): void
    {
        $this->canWaitForIndexesWithLoadAfterSaveChangesInternal(false);
    }

    private function canWaitForIndexesWithLoadAfterSaveChangesInternal(bool $allIndexes): void
    {
        $store = $this->getDocumentStore();
        try {

            (new MyIndex())->execute($store);

            $session = $store->openSession();
            try {
                $mainDocument = new MainDocument();
                $mainDocument->setName("A");
                $mainDocument->setId("main/A");
                $session->store($mainDocument);

                $relatedDocument = new RelatedDocument();
                $relatedDocument->setName("A");
                $relatedDocument->setValue(21.5);
                $relatedDocument->setId("related/A");
                $session->store($relatedDocument);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $this->waitForIndexing($store);

            $session = $store->openSession();
            try {
                /** @var MyIndexResult $result */
                $result = $session->query(MyIndexResult::class, MyIndex::class)
                        ->selectFields(MyIndexResult::class)
                        ->single();
                $this->assertEquals(21.5, $result->getValue());
            } finally {
                $session->close();
            }

            // act
            $session = $store->openSession();
            try {
                $session->advanced()->waitForIndexesAfterSaveChanges(function($builder) use ($allIndexes) {
                    $builder
                            ->withTimeout(Duration::ofSeconds(15))
                            ->throwOnTimeout(true)
                            ->waitForIndexes($allIndexes ? null : ["MyIndex"]);
                });

                $related = $session->load(RelatedDocument::class, "related/A");
                $related->setValue(42);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $this->waitForIndexing($store);
            
            // assert
            $session = $store->openSession();
            try {
                /** @var MyIndexResult $result */
                $result = $session->query(MyIndexResult::class, MyIndex::class)
                        ->selectFields(MyIndexResult::class)
                        ->single();

                $this->assertEquals(42, $result->getValue());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
