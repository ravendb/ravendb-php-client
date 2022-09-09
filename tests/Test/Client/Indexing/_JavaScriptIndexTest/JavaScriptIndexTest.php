<?php

namespace tests\RavenDB\Test\Client\Indexing\_JavaScriptIndexTest;

use tests\RavenDB\RemoteTestBase;

class JavaScriptIndexTest extends RemoteTestBase
{
//    public function canUseJavaScriptIndex(): void {
//        try (IDocumentStore store = getDocumentStore()) {
//            store.executeIndex(new UsersByName());
//
//            try (IDocumentSession session = store.openSession()) {
//                User user = new User();
//                user.setName("Brendan Eich");
//
//                $session->store(user);
//                $session->saveChanges();
//            }
//
//            waitForIndexing(store);
//
//            try (IDocumentSession session = store.openSession()) {
//                User single = $session->query(User.class, index("UsersByName"))
//                        .whereEquals("name", "Brendan Eich")
//                        .single();
//
//                assertThat(single)
//                        .isNotNull();
//            }
//        }
//    }
//
//    public static class UsersByName extends AbstractJavaScriptIndexCreationTask {
//        public UsersByName() {
//            setMaps(Sets.newSet("map('Users', function (u) { return { name: u.name, count: 1 } })"));
//        }
//    }
//
//    @Test
//    public function canUseJavaScriptIndexWithAdditionalSources(): void {
//        try (IDocumentStore store = getDocumentStore()) {
//            store.executeIndex(new UsersByNameWithAdditionalSources());
//
//            try (IDocumentSession session = store.openSession()) {
//                User user = new User();
//                user.setName("Brendan Eich");
//                $session->store(user);
//                $session->saveChanges();
//
//                waitForIndexing(store);
//
//                $session->query(User.class, index("UsersByNameWithAdditionalSources"))
//                        .whereEquals("name", "Mr. Brendan Eich")
//                        .single();
//
//            }
//        }
//    }
//
//    @Test
//    public function canIndexMapReduceWithFanout(): void {
//        try (IDocumentStore store = getDocumentStore()) {
//            store.executeIndex(new FanoutByNumbersWithReduce());
//
//            try (IDocumentSession session = store.openSession()) {
//                Fanout fanout1 = new Fanout();
//                fanout1.setFoo("Foo");
//                fanout1.setNumbers(new int[] { 4, 6, 11, 9 });
//
//                Fanout fanout2 = new Fanout();
//                fanout2.setFoo("Bar");
//                fanout2.setNumbers(new int[] { 3, 8, 5, 17 });
//
//                $session->store(fanout1);
//                $session->store(fanout2);
//                $session->saveChanges();
//
//                waitForIndexing(store);
//
//                $session->query(FanoutByNumbersWithReduce.Result.class, index("FanoutByNumbersWithReduce"))
//                        .whereEquals("sum", 33)
//                        .single();
//
//            }
//        }
//    }
//
//    @Test
//    public function canUseJavaScriptIndexWithDynamicFields(): void {
//        try (IDocumentStore store = getDocumentStore()) {
//            store.executeIndex(new UsersByNameAndAnalyzedName());
//
//            try (IDocumentSession session = store.openSession()) {
//                User user = new User();
//                user.setName("Brendan Eich");
//                $session->store(user);
//                $session->saveChanges();
//
//                waitForIndexing(store);
//
//                $session->query(User.class, index("UsersByNameAndAnalyzedName"))
//                        .selectFields(UsersByNameAndAnalyzedName.Result.class)
//                        .search("analyzedName", "Brendan")
//                        .single();
//            }
//        }
//    }
//
//    @Test
//    public function canUseJavaScriptMultiMapIndex(): void {
//        try (IDocumentStore store = getDocumentStore()) {
//            store.executeIndex(new UsersAndProductsByName());
//
//            try (IDocumentSession session = store.openSession()) {
//                User user = new User();
//                user.setName("Brendan Eich");
//                $session->store(user);
//
//                Product product = new Product();
//                product.setName("Shampoo");
//                product.setAvailable(true);
//                $session->store(product);
//
//                $session->saveChanges();
//
//                waitForIndexing(store);
//
//                $session->query(User.class, index("UsersAndProductsByName"))
//                        .whereEquals("name", "Brendan Eich")
//                        .single();
//            }
//        }
//    }
//
//    @Test
//    public function canUseJavaScriptMapReduceIndex(): void {
//        try (IDocumentStore store = getDocumentStore()) {
//            store.executeIndex(new UsersAndProductsByNameAndCount());
//
//            try (IDocumentSession session = store.openSession()) {
//                User user = new User();
//                user.setName("Brendan Eich");
//                $session->store(user);
//
//                Product product = new Product();
//                product.setName("Shampoo");
//                product.setAvailable(true);
//                $session->store(product);
//
//                $session->saveChanges();
//
//                waitForIndexing(store);
//
//                $session->query(User.class, index("UsersAndProductsByNameAndCount"))
//                        .whereEquals("name", "Brendan Eich")
//                        .single();
//            }
//        }
//    }

    public function testOutputReduceToCollection(): void
    {
        $store = $this->getDocumentStore();
        try {
            $store->executeIndex(new Products_ByCategory());

            $session = $store->openSession();
            try {
                $category1 = new Category();
                $category1->setName("Beverages");
                $session->store($category1, "categories/1-A");

                $category2 = new Category();
                $category2->setName("Seafood");
                $session->store($category2, "categories/2-A");

                $session->store(Product2::create("categories/1-A", "Lakkalikööri", 13));
                $session->store(Product2::create("categories/1-A", "Original Frankfurter", 16));
                $session->store(Product2::create("categories/2-A", "Röd Kaviar", 18));

                $session->saveChanges();

                $this->waitForIndexing($store);

                $res = $session->query(Products_ByCategoryResult::class, "Products/ByCategory")
                        ->toList();

                $res2 = $session->query(CategoryCount::class)
                        ->toList();

                $this->assertEquals(count($res2), count($res));
                $this->assertGreaterThan(0, count($res2));
                $this->assertGreaterThan(0, count($res));
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

//    public static class UsersByNameWithAdditionalSources extends AbstractJavaScriptIndexCreationTask {
//        public UsersByNameWithAdditionalSources() {
//            setMaps(Sets.newSet("map('Users', function(u) { return { name: mr(u.name)}; })"));
//
//            HashMap<String, String> additionalSources = new HashMap<>();
//            additionalSources.put("The Script", "function mr(x) { return 'Mr. ' + x; }");
//            setAdditionalSources(additionalSources);
//        }
//    }
//
//    public static class FanoutByNumbersWithReduce extends AbstractJavaScriptIndexCreationTask {
//        public FanoutByNumbersWithReduce() {
//            setMaps(Sets.newSet("map('Fanouts', function (f){\n" +
//                    "                                var result = [];\n" +
//                    "                                for(var i = 0; i < f.numbers.length; i++)\n" +
//                    "                                {\n" +
//                    "                                    result.push({\n" +
//                    "                                        foo: f.foo,\n" +
//                    "                                        sum: f.numbers[i]\n" +
//                    "                                    });\n" +
//                    "                                }\n" +
//                    "                                return result;\n" +
//                    "                                })"));
//
//
//            setReduce("groupBy(f => f.foo).aggregate(g => ({  foo: g.key, sum: g.values.reduce((total, val) => val.sum + total,0) }))");
//        }
//
//        public static class Result {
//            private String foo;
//            private int sum;
//
//            public String getFoo() {
//                return foo;
//            }
//
//            public function setFoo(String foo) {
//                this.foo = foo;
//            }
//
//            public int getSum() {
//                return sum;
//            }
//
//            public function setSum(int sum) {
//                this.sum = sum;
//            }
//        }
//    }
//
//    public static class UsersByNameAndAnalyzedName extends AbstractJavaScriptIndexCreationTask {
//        public UsersByNameAndAnalyzedName() {
//            setMaps(Sets.newSet("map('Users', function (u){\n" +
//                    "                                    return {\n" +
//                    "                                        name: u.name,\n" +
//                    "                                        _: {$value: u.name, $name:'analyzedName', $options: { indexing: 'Search', storage: true}}\n" +
//                    "                                    };\n" +
//                    "                                })"));
//
//            HashMap<String, IndexFieldOptions> fieldOptions = new HashMap<>();
//            setFields(fieldOptions);
//
//            IndexFieldOptions indexFieldOptions = new IndexFieldOptions();
//            indexFieldOptions.setIndexing(FieldIndexing.SEARCH);
//            indexFieldOptions.setAnalyzer("StandardAnalyzer");
//            fieldOptions.put(Constants.Documents.Indexing.Fields.ALL_FIELDS, indexFieldOptions);
//
//        }
//
//        public static class Result {
//            private String analyzedName;
//
//            public String getAnalyzedName() {
//                return analyzedName;
//            }
//
//            public function setAnalyzedName(String analyzedName) {
//                this.analyzedName = analyzedName;
//            }
//        }
//    }
//
//    public static class UsersAndProductsByName extends AbstractJavaScriptIndexCreationTask {
//        public UsersAndProductsByName() {
//            setMaps(Sets.newSet("map('Users', function (u){ return { name: u.name, count: 1};})", "map('Products', function (p){ return { name: p.name, count: 1};})"));
//        }
//    }
//
//    public static class UsersAndProductsByNameAndCount extends AbstractJavaScriptIndexCreationTask {
//        public UsersAndProductsByNameAndCount() {
//            setMaps(Sets.newSet("map('Users', function (u){ return { name: u.name, count: 1};})", "map('Products', function (p){ return { name: p.name, count: 1};})"));
//            setReduce("groupBy( x =>  x.name )\n" +
//                    "                                .aggregate(g => {return {\n" +
//                    "                                    name: g.key,\n" +
//                    "                                    count: g.values.reduce((total, val) => val.count + total,0)\n" +
//                    "                               };})");
//        }
//    }

}
