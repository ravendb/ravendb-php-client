<?php

namespace tests\RavenDB\Test\Client\Documents;

use RavenDB\Documents\Commands\GetDocumentsCommand;
use RavenDB\Documents\Commands\GetDocumentsResult;
use RavenDB\Documents\Session\InMemoryDocumentSessionOperations;
use tests\RavenDB\Infrastructure\Entity\Person;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RemoteTestBase;

class BasicDocumentsTest extends RemoteTestBase
{
    public function testCanChangeDocumentCollectionWithDeleteAndSave(): void
    {
        $store = $this->getDocumentStore();
        try {
            $documentId = "users/1";

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Grisha");

                $session->store($user, $documentId);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $session->delete($documentId);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $user = $session->load(User::class, $documentId);
                $this->assertNull($user);
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $person = new Person();
                $person->setName("Grisha");

                $session->store($person, $documentId);
                $session->saveChanges();
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    /**
     * @throws \Symfony\Component\Serializer\Exception\ExceptionInterface
     */
    public function testGet(): void
    {
        $store = $this->getDocumentStore();
        try {
            $dummy = $store->getConventions()->getEntityMapper()->normalize(new User());
            unset($dummy["id"]);

            $session = $store->openSession();
            try {
                $user1 = new User();
                $user1->setName("Fitzchak");

                $user2 = new User();
                $user2->setName("Arek");

                $session->store($user1, "users/1");
                $session->store($user2, "users/2");

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $requestExecutor = $store->getRequestExecutor();

            $getDocumentsCommand = GetDocumentsCommand::forMultipleDocuments(["users/1", "users/2"], null, false);

            $requestExecutor->execute($getDocumentsCommand);

            /** @var GetDocumentsResult $docs */
            $docs = $getDocumentsCommand->getResult();
            $this->assertCount(2, $docs->getResults());

            $doc1 = $docs->getResults()[0];
            $doc2 = $docs->getResults()[1];

            $this->assertNotNull($doc1);
            $this->assertArrayHasKey("@metadata", $doc1);
            $this->assertCount(count($dummy)+1, $doc1); // +1 for @metadata

            $this->assertNotNull($doc2);
            $this->assertArrayHasKey("@metadata", $doc2);
            $this->assertCount(count($dummy)+1, $doc2); // +1 for @metadata

            /** @var InMemoryDocumentSessionOperations $session */
            $session = $store->openSession();
            try {
                /** @var User $user1 */
                $user1 = $session->getEntityToJson()->convertToEntity(User::class, "users/1", $doc1, false);
                $user2 = $session->getEntityToJson()->convertToEntity(User::class, "users/2", $doc2, false);

                $this->assertEquals("Fitzchak", $user1->getName());

                $this->assertEquals("Arek", $user2->getName());

            } finally {
                $session->close();
            }

            $getDocumentsCommand = GetDocumentsCommand::forMultipleDocuments(["users/1", "users/2"], null, true);

            $requestExecutor->execute($getDocumentsCommand);

            /** @var GetDocumentsResult $docs */
            $docs = $getDocumentsCommand->getResult();

            $this->assertCount(2, $docs->getResults());

            $doc1 = $docs->getResults()[0];
            $doc2 = $docs->getResults()[1];

            $this->assertNotNull($doc1);
            $this->assertArrayHasKey("@metadata", $doc1);
            $this->assertCount(1, $doc1);

            $this->assertNotNull($doc2);
            $this->assertArrayHasKey("@metadata", $doc2);
            $this->assertCount(1, $doc2);
        } finally {
            $store->close();
        }
    }
}
