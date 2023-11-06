<?php

namespace tests\RavenDB\Bugs\_SimpleMultiMapTest;

use RavenDB\Documents\Indexes\IndexDefinition;
use RavenDB\Documents\Operations\Indexes\GetIndexOperation;
use RavenDB\Type\Duration;
use tests\RavenDB\RemoteTestBase;

class SimpleMultiMapTest extends RemoteTestBase
{
    public function testCanCreateMultiMapIndex(): void
    {
        $store = $this->getDocumentStore();
        try {
            (new CatsAndDogs())->execute($store);

            /** @var IndexDefinition  $indexDefinition */
            $indexDefinition = $store->maintenance()->send(new GetIndexOperation("CatsAndDogs"));
            $this->assertCount(2, $indexDefinition->getMaps());
        } finally {
            $store->close();
        }
    }

    public function testCanQueryUsingMultiMap(): void
    {
        $store = $this->getDocumentStore();
        try {
            (new CatsAndDogs())->execute($store);

            $session = $store->openSession();
            try {
                $cat = new Cat();
                $cat->setName("Tom");

                $dog = new Dog();
                $dog->setName("Oscar");

                $session->store($cat);
                $session->store($dog);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $haveNames = $session->query(IHaveName::class, CatsAndDogs::class)
                        ->waitForNonStaleResults(Duration::ofSeconds(10))
                        ->orderBy("name")
                        ->toList();

                $this->assertCount(2, $haveNames);

                $this->assertInstanceOf(Dog::class, $haveNames[0]);
                $this->assertInstanceOf(Cat::class, $haveNames[1]);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
