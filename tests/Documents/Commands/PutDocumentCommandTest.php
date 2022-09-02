<?php

namespace tests\RavenDB\Documents\Commands;

use InvalidArgumentException;
use RavenDB\Documents\Commands\Batches\PutResult;
use RavenDB\Documents\Commands\PutDocumentCommand;
use RavenDB\Documents\DocumentStore;
use RavenDB\Exceptions\IllegalStateException;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RemoteTestBase;

// !status: DONE
class PutDocumentCommandTest extends RemoteTestBase
{
    /**
     * @throws InvalidArgumentException
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
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanPutDocumentUsingCommandWithSurrogatePairs(): void
    {
        $store = $this->getDocumentStore();

        try {
            $nameWithEmojis = json_decode('"Marcin \uD83D\uDE21\uD83D\uDE21\uD83E\uDD2C\uD83D\uDE00😡😡🤬😀"');

            $user = new User();
            $user->setName($nameWithEmojis);
            $user->setAge(31);

            $node = $store->getConventions()->getEntityMapper()->normalize($user, 'json');

            $command = new PutDocumentCommand("users/2", null, $node);
            $store->getRequestExecutor()->execute($command);

            /** @var PutResult $result */
            $result = $command->getResult();

            $this->assertEquals("users/2", $result->getId());

            $this->assertNotNull($result->getChangeVector());

            $session = $store->openSession();
            try {
                $loadedUser = $session->load(User::class, "users/2");

                $this->assertEquals($nameWithEmojis, $loadedUser->getName());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
