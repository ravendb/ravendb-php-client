<?php

namespace tests\RavenDB\Documents\Commands;

use RavenDB\Documents\Commands\batches\PutResult;
use RavenDB\Documents\Commands\PutDocumentCommand;
use RavenDB\Documents\DocumentStore;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Exceptions\IllegalStateException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RemoteTestBase;

class PutDocumentCommandTest extends RemoteTestBase
{
    /**
     * @throws IllegalArgumentException
     * @throws IllegalStateException
     * @throws ExceptionInterface
     */
    public function testCanPutDocumentUsingCommand()
    {
        /** @var DocumentStore $store */
        $store = $this->getDocumentStore();

        try {
            $user = new User();
            $user->setName("Aleksandar");
            $user->setAge(41);

            $entityMapper = $store->getConventions()->getEntityMapper();
            $document = $entityMapper->normalize($user, 'json');

            $command = new PutDocumentCommand("users/1", null, $document);
            $store->getRequestExecutor()->execute($command);

            /** @var PutResult $result */
            $result = $command->getResult();

            $this->assertEquals("users/1", $result->getId());

            $this->assertNotNull($result->getChangeVector());

            $session = $store->openSession();
            try {
                /** @var User $loadedUser */
                $loadedUser = $session->load(User::class, "users/1");
                $this->assertEquals('Aleksandar', $loadedUser->getName());
            } finally {
//                $session->dispose();
            }
        } finally {
            $this->cleanUp($store);
        }
    }
}
