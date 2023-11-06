<?php

namespace tests\RavenDB;

use RavenDB\Documents\DocumentStoreInterface;
use tests\RavenDB\Infrastructure\Graph\Dog;
use tests\RavenDB\Infrastructure\Graph\Entity;
use tests\RavenDB\Infrastructure\Graph\Genre;
use tests\RavenDB\Infrastructure\Graph\Movie;
use tests\RavenDB\Infrastructure\Graph\User;
use tests\RavenDB\Infrastructure\Graph\UserRating;

class SamplesTestBase
{
    private ?RemoteTestBase $parent = null;

    public function __construct(?RemoteTestBase $parent)
    {
        $this->parent = $parent;
    }

    public function createSimpleData(DocumentStoreInterface $store): void
    {
        $session = $store->openSession();
        try {
            $entityA = new Entity();
            $entityA->setId("entity/1");
            $entityA->setName("A");

            $entityB = new Entity();
            $entityB->setId("entity/2");
            $entityB->setName("B");

            $entityC = new Entity();
            $entityC->setId("entity/3");
            $entityC->setName("C");

            $session->store($entityA);
            $session->store($entityB);
            $session->store($entityC);

            $entityA->setReferences($entityB->getId());
            $entityB->setReferences($entityC->getId());
            $entityC->setReferences($entityA->getId());

            $session->saveChanges();
        } finally {
            $session->close();
        }
    }

    public function createDogDataWithoutEdges(DocumentStoreInterface $store): void
    {
        $session = $store->openSession();
        try {
            $arava = new Dog();
            $arava->setName("Arava");

            $oscar = new Dog();
            $oscar->setName("Oscar");

            $pheobe = new Dog();
            $pheobe->setName("Pheobe");

            $session->store($arava);
            $session->store($oscar);
            $session->store($pheobe);

            $session->saveChanges();
        } finally {
            $session->close();
        }
    }

    public function createDataWithMultipleEdgesOfTheSameType(DocumentStoreInterface $store): void
    {
        $session = $store->openSession();
        try {
            $arava = new Dog();
            $arava->setName("Arava");

            $oscar = new Dog();
            $oscar->setName("Oscar");

            $pheobe = new Dog();
            $pheobe->setName("Pheobe");

            $session->store($arava);
            $session->store($oscar);
            $session->store($pheobe);

            //dogs/1 => dogs/2
            $arava->setLikes([ $oscar->getId() ]);
            $arava->setDislikes([ $pheobe->getId() ]);

            //dogs/2 => dogs/2,dogs/3 (cycle!)
            $oscar->setLikes([ $oscar->getId(), $pheobe->getId() ]);
            $oscar->setDislikes([]);

            //dogs/3 => dogs/2
            $pheobe->setLikes([ $oscar->getId() ]);
            $pheobe->setDislikes([ $arava->getId() ]);

            $session->saveChanges();
        } finally {
            $session->close();
        }
    }

    public function createMoviesData(DocumentStoreInterface $store): void
    {
        $session = $store->openSession();
        try {
            $scifi = new Genre();
            $scifi->setId("genres/1");
            $scifi->setName("Sci-Fi");

            $fantasy = new Genre();
            $fantasy->setId("genres/2");
            $fantasy->setName("Fantasy");

            $adventure = new Genre();
            $adventure->setId("genres/3");
            $adventure->setName("Adventure");

            $session->store($scifi);
            $session->store($fantasy);
            $session->store($adventure);

            $starwars = new Movie();
            $starwars->setId("movies/1");
            $starwars->setName("Star Wars Ep.1");
            $starwars->setGenres([ "genres/1", "genres/2" ]);

            $firefly = new Movie();
            $firefly->setId("movies/2");
            $firefly->setName("Firefly Serenity");
            $firefly->setGenres([ "genres/2", "genres/3" ]);

            $indianaJones = new Movie();
            $indianaJones->setId("movies/3");
            $indianaJones->setName("Indiana Jones and the Temple Of Doom");
            $indianaJones->setGenres([ "genres/3" ]);

            $session->store($starwars);
            $session->store($firefly);
            $session->store($indianaJones);

            $user1 = new User();
            $user1->setId("users/1");
            $user1->setName("Jack");

            $rating11 = new UserRating();
            $rating11->setMovie("movies/1");
            $rating11->setScore(5);

            $rating12 = new UserRating();
            $rating12->setMovie("movies/2");
            $rating12->setScore(7);

            $user1->setHasRated([ $rating11, $rating12 ]);
            $session->store($user1);

            $user2 = new User();
            $user2->setId("users/2");
            $user2->setName("Jill");

            $rating21 = new UserRating();
            $rating21->setMovie("movies/2");
            $rating21->setScore(7);

            $rating22 = new UserRating();
            $rating22->setMovie("movies/3");
            $rating22->setScore(9);

            $user2->setHasRated([ $rating21, $rating22 ]);

            $session->store($user2);

            $user3 = new User();
            $user3->setId("users/3");
            $user3->setName("Bob");

            $rating31 = new UserRating();
            $rating31->setMovie("movies/3");
            $rating31->setScore(5);

            $user3->setHasRated([ $rating31 ]);

            $session->store($user3);

            $session->saveChanges();
        } finally {
            $session->close();
        }
    }
}
