<?php

namespace tests\RavenDB\Test\Client;

use RavenDB\Exceptions\Documents\Session\NonUniqueObjectException;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RemoteTestBase;

class TrackEntityTest extends RemoteTestBase
{
//    public function deletingEntityThatIsNotTrackedShouldThrow(): void {
//        try (IDocumentStore store = getDocumentStore()) {
//
//            try (IDocumentSession session = store.openSession()) {
//                assertThatThrownBy(() -> session.delete(new User()))
//                .isExactlyInstanceOf(IllegalStateException.class)
//                .hasMessageEndingWith("is not associated with the session, cannot delete unknown entity instance");
//            }
//        }
//    }
//
//    @Test
//    public function loadingDeletedDocumentShouldReturnNull(): void {
//        try (IDocumentStore store = getDocumentStore()) {
//            try (IDocumentSession session = store.openSession()) {
//                User user1 = new User();
//                user1.setName("John");
//                user1.setId("users/1");
//
//                User user2 = new User();
//                user2.setName("Jonathan");
//                user2.setId("users/2");
//
//                session.store(user1);
//                session.store(user2);
//                session.saveChanges();
//            }
//
//            try (IDocumentSession session = store.openSession()) {
//                session.delete("users/1");
//                session.delete("users/2");
//                session.saveChanges();
//            }
//
//            try (IDocumentSession session = store.openSession()) {
//                assertThat(session.load(User.class, "users/1"))
//                        .isNull();
//                assertThat(session.load(User.class, "users/2"))
//                        .isNull();
//            }
//        }
//    }

    public function testStoringDocumentWithTheSameIdInTheSameSessionShouldThrow(): void
    {
        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setId("users/1");
                $user->setName("User1");

                $session->store($user);
                $session->saveChanges();

                $newUser = new User();
                $newUser->setName("User2");
                $newUser->setId("users/1");

                $this->expectException(NonUniqueObjectException::class);
                $this->expectExceptionMessage("Attempted to associate a different object with id 'users/1'");

               $session->store($newUser);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
