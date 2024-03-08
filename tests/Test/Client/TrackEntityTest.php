<?php

namespace tests\RavenDB\Test\Client;

use Exception;
use RavenDB\Exceptions\Documents\Session\NonUniqueObjectException;
use RavenDB\Exceptions\IllegalStateException;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RemoteTestBase;
use Throwable;

class TrackEntityTest extends RemoteTestBase
{
    public function testDeletingEntityThatIsNotTrackedShouldThrow(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                try {
                    $session->delete(new User());

                    throw new Exception('It should throw exception before reaching this code');
                } catch (Throwable $exception) {
                    $this->assertInstanceOf(IllegalStateException::class, $exception);
                    $this->assertStringEndsWith("is not associated with the session, cannot delete unknown entity instance", $exception->getMessage());
                }
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testLoadingDeletedDocumentShouldReturnNull(): void
    {
        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                $user1 = new User();
                $user1->setName("John");
                $user1->setId("users/1");

                $user2 = new User();
                $user2->setName("Jonathan");
                $user2->setId("users/2");

                $session->store($user1);
                $session->store($user2);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $session->delete("users/1");
                $session->delete("users/2");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $this->assertNull($session->load(User::class, "users/1"));
                $this->assertNull($session->load(User::class, "users/2"));
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

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

//    public void getTrackedEntities() throws Exception {
//        try (DocumentStore store = getDocumentStore()) {
//
//            String userId;
//            String companyId;
//
//            try (IDocumentSession session = store.openSession()) {
//                User user = new User();
//                user.setName("Grisha");
//
//                session.store(user);
//                userId = user.getId();
//
//                Company company = new Company();
//                company.setName("Hibernating Rhinos");
//                session.store(company);
//
//                companyId = company.getId();
//
//                Order order = new Order();
//                order.setEmployee(company.getId());
//                session.store(order);
//
//                Map<String, DocumentsById.EntityInfo> tracked = session.advanced().getTrackedEntities();
//
//                DocumentsById.EntityInfo value = tracked.get(userId);
//                assertThat(value)
//                        .isNotNull();
//
//                assertThat(value.getId())
//                        .isEqualTo(userId);
//                assertThat(value.getEntity())
//                        .isExactlyInstanceOf(User.class);
//
//                value = tracked.get(company.getId());
//                assertThat(value)
//                        .isNotNull();
//                assertThat(value.getId())
//                        .isEqualTo(companyId);
//                assertThat(value.getEntity())
//                        .isExactlyInstanceOf(Company.class);
//
//                value = tracked.get(order.getId());
//                assertThat(value)
//                        .isNotNull();
//                assertThat(value.getId())
//                        .isEqualTo(order.getId());
//
//                session.saveChanges();
//            }
//
//            try (IDocumentSession session = store.openSession()) {
//                session.delete(userId);
//                session.delete(companyId);
//
//                Map<String, DocumentsById.EntityInfo> tracked = session.advanced().getTrackedEntities();
//                assertThat(tracked)
//                        .hasSize(2);
//                assertThat(tracked.get(userId).isDeleted())
//                        .isTrue();
//                assertThat(tracked.get(companyId).isDeleted())
//                        .isTrue();
//            }
//
//            try (IDocumentSession session = store.openSession()) {
//                session.delete(userId);
//                session.delete(companyId);
//
//                Lazy<Map<String, User>> usersLazy = session.advanced().lazily().loadStartingWith(User.class, "u");
//                Map<String, User> users = usersLazy.getValue();
//                assertThat(users.entrySet().stream().findFirst().get().getValue())
//                        .isNull();
//
//                Company company = session.load(Company.class, companyId);
//                assertThat(company)
//                        .isNull();
//
//                Map<String, DocumentsById.EntityInfo> tracked = session.advanced().getTrackedEntities();
//                assertThat(tracked)
//                        .hasSize(2);
//                assertThat(tracked.get(userId).isDeleted())
//                        .isTrue();
//                assertThat(tracked.get(companyId).isDeleted())
//                        .isTrue();
//            }
//
//            try (IDocumentSession session = store.openSession()) {
//                User user = session.load(User.class, userId);
//                session.delete(user.getId());
//
//                Map<String, DocumentsById.EntityInfo> tracked = session.advanced().getTrackedEntities();
//                assertThat(tracked)
//                        .hasSize(1);
//                assertThat(tracked.get(userId).getId())
//                        .isEqualTo(userId);
//            }
//
//            try (IDocumentSession session = store.openSession()) {
//                User user = session.load(User.class, userId);
//                session.delete(user.getId().toUpperCase());
//                Map<String, DocumentsById.EntityInfo> tracked = session.advanced().getTrackedEntities();
//                assertThat(tracked)
//                        .hasSize(1);
//                assertThat(tracked.entrySet().stream().findFirst().get().getKey())
//                        .isEqualToIgnoringCase(userId);
//                assertThat(tracked.entrySet().stream().findFirst().get().getValue().isDeleted())
//                        .isTrue();
//            }
//
//            try (IDocumentSession session = store.openSession()) {
//                User user = session.load(User.class, userId);
//                session.delete(user);
//                Map<String, DocumentsById.EntityInfo> tracked = session.advanced().getTrackedEntities();
//                assertThat(tracked)
//                        .hasSize(1);
//                assertThat(tracked.entrySet().stream().findFirst().get().getKey())
//                        .isEqualToIgnoringCase(userId);
//                assertThat(tracked.entrySet().stream().findFirst().get().getValue().isDeleted())
//                        .isTrue();
//            }
//        }
//    }
}
