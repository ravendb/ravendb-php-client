<?php

namespace tests\RavenDB\Test\Client\Issues;

use RavenDB\Documents\DocumentStore;
use tests\RavenDB\RemoteTestBase;
use tests\RavenDB\Test\Client\Issues\Entity\Animal;
use tests\RavenDB\Test\Client\Issues\Entity\Animal_Index;

class RavenDB_5669Test extends RemoteTestBase
{
    public function testWorkingTestWithDifferentSearchTermOrder(): void
    {
        $store = $this->getDocumentStore();
        try {
            $store->executeIndex(new Animal_Index());

            $this->storeAnimals($store);

            $session = $store->openSession();
            try {
                $query = $session->advanced()->documentQuery(Animal::class, Animal_Index::class);

                $query->openSubclause();

                $query = $query->whereEquals("type", "Cat");
                $query = $query->orElse();
                $query = $query->search("name", "Peter*");
                $query = $query->andAlso();
                $query = $query->search("name", "Pan*");

                $query->closeSubclause();

                $results = $query->toList();
                $this->assertCount(1, $results);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testWorkingTestWithSubclause(): void
    {
        $store = $this->getDocumentStore();
        try {
            $store->executeIndex(new Animal_Index());

            $this->storeAnimals($store);

            $session = $store->openSession();
            try {
                $query = $session->advanced()->documentQuery(Animal::class, Animal_Index::class);

                $query->openSubclause();

                $query = $query->whereEquals("type", "Cat");
                $query = $query->orElse();

                $query->openSubclause();

                $query = $query->search("name", "Pan*");
                $query = $query->andAlso();
                $query = $query->search("name", "Peter*");
                $query = $query->closeSubclause();

                $query->closeSubclause();

                $results = $query->toList();
                $this->assertCount(1, $results);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    private function storeAnimals(DocumentStore $store): void
    {
        $session = $store->openSession();
        try {
            $animal1 = new Animal();
            $animal1->setName("Peter Pan");
            $animal1->setType("Dog");

            $animal2 = new Animal();
            $animal2->setName("Peter Poo");
            $animal2->setType("Dog");


            $animal3 = new Animal();
            $animal3->setName("Peter Foo");
            $animal3->setType("Dog");

            $session->store($animal1);
            $session->store($animal2);
            $session->store($animal3);
            $session->saveChanges();
        } finally {
            $session->close();
        }

        $this->waitForIndexing($store, $store->getDatabase());
    }
}
