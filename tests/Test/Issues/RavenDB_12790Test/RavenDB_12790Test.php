<?php

namespace tests\RavenDB\Test\Issues\RavenDB_12790Test;

use Exception;
use RavenDB\Exceptions\Documents\Indexes\IndexDoesNotExistException;
use tests\RavenDB\RemoteTestBase;
use Throwable;

class RavenDB_12790Test extends RemoteTestBase
{
    public function testLazyQueryAgainstMissingIndex(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $document = new Document();
                $document->setName("name");
                $session->store($document);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            // intentionally not creating the index that we query against

            $session = $store->openSession();
            try {
                try {
                    $session->query(Document::class, DocumentIndex::class)->toList();

                    throw new Exception('It should throw exception before reaching this code');
                } catch (Throwable $exception) {
                    $this->assertInstanceOf(IndexDoesNotExistException::class, $exception);
                }
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $lazyQuery = $session->query(Document::class, DocumentIndex::class)
                        ->lazily();

                try {
                    $lazyQuery->getValue();

                    throw new Exception('It should throw exception before reaching this code');
                } catch (Throwable $exception) {
                    $this->assertInstanceOf(IndexDoesNotExistException::class, $exception);
                }
            } finally {
                $session->close();
            }

        } finally {
            $store->close();
        }
    }
}
