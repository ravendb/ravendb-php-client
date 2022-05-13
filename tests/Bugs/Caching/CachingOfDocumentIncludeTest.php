<?php

namespace tests\RavenDB\Bugs\Caching;

use tests\RavenDB\Bugs\Caching\Entity\User;
use tests\RavenDB\RemoteTestBase;

class CachingOfDocumentIncludeTest extends RemoteTestBase
{
    public function atest_can_cache_document_with_includes(): void
    {
        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Ayende");
                $session->store($user);

                $partner = new User();
                $partner->setPartnerId("users/1-A");
                $session->store($partner);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $session->include("partnerId")
                        ->load(User::class, "users/2-A");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $session->include("partnerId")
                        ->load(User::class, "users/2-A");

                $this->assertEquals(1,$session->advanced()->getRequestExecutor()->getCache()->getNumberOfItems());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

//    public void can_avoid_using_server_for_load_with_include_if_everything_is_in_session_cacheAsync() throws Exception {
//        try (IDocumentStore store = getDocumentStore()) {
//            try (IDocumentSession session = store.openSession()) {
//                User user = new User();
//                user.setName("Ayende");
//                session.store(user);
//
//                User partner = new User();
//                partner.setPartnerId("users/1-A");
//                session.store(partner);
//
//                session.saveChanges();
//            }
//
//            try (IDocumentSession session = store.openSession()) {
//                User user = session.load(User.class, "users/2-A");
//
//                session.load(User.class, user.getPartnerId());
//
//                int old = session.advanced().getNumberOfRequests();
//                User newUser = session.include("partnerId")
//                        .load(User.class, "users/2-A");
//
//                assertThat(session.advanced().getNumberOfRequests())
//                        .isEqualTo(old);
//            }
//        }
//    }
//
//    @Test
//    public void can_avoid_using_server_for_load_with_include_if_everything_is_in_session_cacheLazy() throws Exception {
//        try (IDocumentStore store = getDocumentStore()) {
//            try (IDocumentSession session = store.openSession()) {
//                User user = new User();
//                user.setName("Ayende");
//                session.store(user);
//
//                User partner = new User();
//                partner.setPartnerId("users/1-A");
//                session.store(partner);
//
//                session.saveChanges();
//            }
//
//            try (IDocumentSession session = store.openSession()) {
//                session.advanced().lazily().load(User.class, "users/2-A");
//                session.advanced().lazily().load(User.class, "users/1-A");
//                session.advanced().eagerly().executeAllPendingLazyOperations();
//
//                int old = session.advanced().getNumberOfRequests();
//
//                Lazy<User> result1 = session.advanced().lazily()
//                        .include("partnerId")
//                        .load(User.class, "users/2-A");
//
//                assertThat(result1.getValue())
//                        .isNotNull();
//
//                assertThat(session.advanced().getNumberOfRequests())
//                        .isEqualTo(old);
//            }
//        }
//    }
//
//    @Test
//    public void can_avoid_using_server_for_load_with_include_if_everything_is_in_session_cache() throws Exception {
//        try (IDocumentStore store = getDocumentStore()) {
//            try (IDocumentSession session = store.openSession()) {
//                User user = new User();
//                user.setName("Ayende");
//                session.store(user);
//
//                User partner = new User();
//                partner.setPartnerId("users/1-A");
//                session.store(partner);
//
//                session.saveChanges();
//            }
//
//            try (IDocumentSession session = store.openSession()) {
//                User user = session.load(User.class, "users/2-A");
//
//                session.load(User.class, user.getPartnerId());
//
//                int old = session.advanced().getNumberOfRequests();
//
//                User res = session.include("partnerId")
//                        .load(User.class, "users/2-A");
//
//                assertThat(session.advanced().getNumberOfRequests())
//                        .isEqualTo(old);
//            }
//        }
//    }
//
//    @Test
//    public void can_avoid_using_server_for_multiload_with_include_if_everything_is_in_session_cache() throws Exception {
//        try (IDocumentStore store = getDocumentStore()) {
//            try (final IDocumentSession session = store.openSession()) {
//                Consumer<String> storeUser = name -> {
//                    User user = new User();
//                    user.setName(name);
//                    session.store(user);
//                };
//
//                storeUser.accept("Additional");
//                storeUser.accept("Ayende");
//                storeUser.accept("Michael");
//                storeUser.accept("Fitzchak");
//                storeUser.accept("Maxim");
//
//                User withPartner = new User();
//                withPartner.setPartnerId("users/1-A");
//                session.store(withPartner);
//                session.saveChanges();
//            }
//
//            try (IDocumentSession session = store.openSession()) {
//                User u2 = session.load(User.class, "users/2-A");
//                User u6 = session.load(User.class, "users/6-A");
//
//                ArrayList<String> inp = new ArrayList<>();
//                inp.add("users/1-A");
//                inp.add("users/2-A");
//                inp.add("users/3-A");
//                inp.add("users/4-A");
//                inp.add("users/5-A");
//                inp.add("users/6-A");
//                Map<String, User> u4 = session.load(User.class, inp);
//
//                session.load(User.class, u6.getPartnerId());
//
//                int old = session.advanced().getNumberOfRequests();
//
//                Map<String, User> res = session.include("partnerId")
//                        .load(User.class, "users/2-A", "users/3-A", "users/6-A");
//
//                assertThat(session.advanced().getNumberOfRequests())
//                        .isEqualTo(old);
//
//            }
//        }
//    }
//
//    @Test
//    public void can_include_nested_paths() throws Exception {
//        try (IDocumentStore store = getDocumentStore()) {
//            Order order = new Order();
//
//            OrderLine orderLine1 = new OrderLine();
//            orderLine1.setProduct("products/1-A");
//            orderLine1.setProductName("phone");
//
//            OrderLine orderLine2 = new OrderLine();
//            orderLine2.setProduct("products/2-A");
//            orderLine2.setProductName("mouse");
//
//            order.setLines(Arrays.asList(orderLine1, orderLine2));
//
//            Product product1 = new Product();
//            product1.setId("products/1-A");
//            product1.setName("phone");
//
//            Product product2 = new Product();
//            product2.setId("products/2-A");
//            product2.setName("mouse");
//
//            try (IDocumentSession session = store.openSession()) {
//                session.store(order, "orders/1-A");
//                session.store(product1);
//                session.store(product2);
//                session.saveChanges();
//            }
//
//            try (IDocumentSession session = store.openSession()) {
//                assertThat(session.advanced().getNumberOfRequests())
//                        .isZero();
//                List<Order> orders = session.query(Order.class)
//                        .include(x -> x.includeDocuments("lines[].product"))
//                        .toList();
//
//                assertThat(session.advanced().getNumberOfRequests())
//                        .isOne();
//
//                Product product = session.load(Product.class, orders.get(0).getLines().get(0).getProduct());
//                assertThat(session.advanced().getNumberOfRequests())
//                        .isOne();
//            }
//
//            try (IDocumentSession session = store.openSession()) {
//                assertThat(session.advanced().getNumberOfRequests())
//                        .isZero();
//                List<Order> orders = session.query(Order.class)
//                        .include(x -> x.includeDocuments("lines.product"))
//                        .toList();
//
//                assertThat(session.advanced().getNumberOfRequests())
//                        .isOne();
//
//                Product product = session.load(Product.class, orders.get(0).getLines().get(0).getProduct());
//                assertThat(session.advanced().getNumberOfRequests())
//                        .isOne();
//            }
//        }
//    }
}
