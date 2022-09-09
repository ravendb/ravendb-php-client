<?php

namespace tests\RavenDB\Test\Client\_HiLoTest;

use RavenDB\Documents\DocumentStore;
use RavenDB\Documents\Identity\HiLoIdGenerator;
use RavenDB\Documents\Identity\MultiDatabaseHiLoIdGenerator;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RemoteTestBase;

class HiLoTest extends RemoteTestBase
{
    public function testHiloCanNotGoDown(): void
    {
        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                $hiloDoc = new HiloDoc();
                $hiloDoc->setMax(32);

                $session->store($hiloDoc, "Raven/Hilo/users");
                $session->saveChanges();

                $hiLoKeyGenerator = new HiLoIdGenerator("users", $store, $store->getDatabase(), $store->getConventions()->getIdentityPartsSeparator());

                $ids = [];
                $ids[] = $hiLoKeyGenerator->nextId();

                $hiloDoc->setMax(12);
                $session->store($hiloDoc, "Raven/Hilo/users", null);
                $session->saveChanges();

                for ($i = 0; $i < 128; $i++) {
                    $nextId = $hiLoKeyGenerator->nextId();
                    $this->assertNotContains($nextId, $ids);
                    $ids[] = $nextId;
                }

                $this->assertEquals(count($ids), count(array_unique($ids)));
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testHiLoMultiDb(): void
    {
        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                $hiloDoc = new HiloDoc();
                $hiloDoc->setMax(64);
                $session->store($hiloDoc, "Raven/Hilo/users");

                $productsHilo = new HiloDoc();
                $productsHilo->setMax(128);
                $session->store($productsHilo, "Raven/Hilo/products");

                $session->saveChanges();

                $multiDbHilo = new MultiDatabaseHiLoIdGenerator($store);
                $generateDocumentKey = $multiDbHilo->generateDocumentId(null, new User());
                $this->assertEquals("users/65-A", $generateDocumentKey);

                $generateDocumentKey = $multiDbHilo->generateDocumentId(null, new Product());
                $this->assertEquals("products/129-A", $generateDocumentKey);

            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    // @todo: investigate this test, priority low
    public function testCapacityShouldDouble(): void
    {
        $this->markTestSkipped("test disabled because server doesn't respond with expected range");

        $store = $this->getDocumentStore();
        try {

            $hiLoIdGenerator = new HiLoIdGenerator("users", $store, $store->getDatabase(), $store->getConventions()->getIdentityPartsSeparator());

            $session = $store->openSession();
            try {
                $hiloDoc = new HiloDoc();
                $hiloDoc->setMax(64);
                $session->store($hiloDoc, "Raven/Hilo/users");
                $session->saveChanges();

                for ($i = 0; $i < 32; $i++) {
                    $hiLoIdGenerator->generateDocumentId(new User());
                }
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $hiloDoc = $session->load(HiloDoc::class, "Raven/Hilo/users");
                $max = $hiloDoc->getMax();
                $this->assertEquals(96, $max);

                //we should be receiving a range of 64 now
                $hiLoIdGenerator->generateDocumentId(new User());
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $hiloDoc = $session->load(HiloDoc::class, "Raven/Hilo/users");
                $max = $hiloDoc->getMax();

                // test fails here, we receive 128, not 160 from server as max - server doesn't respond with expected range
                $this->assertEquals(160, $max);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testReturnUnusedRangeOnClose(): void
    {
        $store = $this->getDocumentStore();
        try {

            $newStore = new DocumentStore();
            $newStore->setUrls($store->getUrls());
            $newStore->setDatabase($store->getDatabase());

            $newStore->initialize();

            $session = $newStore->openSession();
            try {
                $hiloDoc = new HiloDoc();
                $hiloDoc->setMax(32);
                $session->store($hiloDoc, "Raven/Hilo/users");

                $session->saveChanges();

                $session->store(new User());
                $session->store(new User());

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $newStore->close(); //on document store close, hilo-return should be called

            $newStore = new DocumentStore();
            $newStore->setUrls($store->getUrls());
            $newStore->setDatabase($store->getDatabase());

            $newStore->initialize();

            $session = $newStore->openSession();
            try {
                $hiloDoc = $session->load(HiloDoc::class, "Raven/Hilo/users");
                $max = $hiloDoc->getMax();
                $this->assertEquals(34, $max);
            } finally {
                $session->close();
            }

            $newStore->close(); //on document store close, hilo-return should be called
        } finally {
            $store->close();
        }
    }

    // we already are not implementing async call at the moment, so this test is not to be implemented at the moment
    public function atestDoesNotGetAnotherRangeWhenDoingParallelRequests(): void
    {
        $store = $this->getDocumentStore();
        try {
//            $parallelLevel = 32;

//            List<User> users = IntStream.range(0, parallelLevel).mapToObj(x -> new User()).collect(Collectors.toList());

//            CompletableFuture<Void>[] futures = IntStream.range(0, parallelLevel).mapToObj(x -> CompletableFuture.runAsync(() -> {
//                User user = users.get(x);
//                IDocumentSession session = $store->openSession();
//                $session->store(user);
//                $session->saveChanges();
//            })).toArray(CompletableFuture[]::new);
//
//            CompletableFuture.allOf(futures).get();
//
//            users.stream()
//                    .map(User::getId)
//                    .map(id -> id.split("/")[1])
//                    .map(x -> x.split("-")[0])
//                    .forEach(numericPart -> {
//                        assertThat(Integer.valueOf(numericPart))
//                                .isLessThan(33);
//                    });

        } finally {
            $store->close();
        }
    }
}
