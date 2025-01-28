<?php

namespace tests\RavenDB\Test\Client\MoreLikeThis\_MoreLikeThisTest;

use RavenDB\Documents\DocumentStoreInterface;
use RavenDB\Documents\Queries\MoreLikeThis\MoreLikeThisOptions;
use RavenDB\Documents\Queries\MoreLikeThis\MoreLikeThisStopWords;
use tests\RavenDB\Infrastructure\TestRunGuard;
use tests\RavenDB\RemoteTestBase;

class MoreLikeThisTest extends RemoteTestBase
{
    private static function getLorem(int $numWords): string
    {
        $theLorem = "Morbi nec purus eu libero interdum laoreet Nam metus quam posuere in elementum eget egestas eget justo Aenean orci ligula ullamcorper nec convallis non placerat nec lectus Quisque convallis porta suscipit Aliquam sollicitudin ligula sit amet libero cursus egestas Maecenas nec mauris neque at faucibus justo Fusce ut orci neque Nunc sodales pulvinar lobortis Praesent dui tellus fermentum sed faucibus nec faucibus non nibh Vestibulum adipiscing porta purus ut varius mi pulvinar eu Nam sagittis sodales hendrerit Vestibulum et tincidunt urna Fusce lacinia nisl at luctus lobortis lacus quam rhoncus risus a posuere nulla lorem at nisi Sed non erat nisl Cras in augue velit a mattis ante Etiam lorem dui elementum eget facilisis vitae viverra sit amet tortor Suspendisse potenti Nunc egestas accumsan justo viverra viverra Sed faucibus ullamcorper mauris ut pharetra ligula ornare eget Donec suscipit luctus rhoncus Pellentesque eget justo ac nunc tempus consequat Nullam fringilla egestas leo Praesent condimentum laoreet magna vitae luctus sem cursus sed Mauris massa purus suscipit ac malesuada a accumsan non neque Proin et libero vitae quam ultricies rhoncus Praesent urna neque molestie et suscipit vestibulum iaculis ac nulla Integer porta nulla vel leo ullamcorper eu rhoncus dui semper Donec dictum dui";

        $loremArray = explode(" ", $theLorem);

        $output = '';

        for ($i = 0; $i < $numWords; $i++) {
            $output .= $loremArray[ rand(0, count($loremArray)-1)] . " ";
        }
        return $output;
    }

    private static function getDataList(): array
    {
        $items = [];

        $items[] = new Data("This is a test. Isn't it great? I hope I pass my test!");
        $items[] = new Data("I have a test tomorrow. I hate having a test");
        $items[] = new Data("Cake is great.");
        $items[] = new Data("This document has the word test only once");
        $items[] = new Data("test");
        $items[] = new Data("test");
        $items[] = new Data("test");
        $items[] = new Data("test");

        return $items;
    }

    public function testCanGetResultsUsingTermVectors(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                (new DataIndex(true, false))->execute($store);

                $list = self::getDataList();
                foreach ($list as $item) {
                    $session->store($item);
                }
                $session->saveChanges();

                $id = $session->advanced()->getDocumentId($list[0]);
                $this->waitForIndexing($store);
            } finally {
                $session->close();
            }

            $this->assertMoreLikeThisHasMatchesFor(Data::class, DataIndex::class, $store, $id);
        } finally {
            $store->close();
        }
    }

    public function testCanGetResultsUsingTermVectorsLazy(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                (new DataIndex(true, false))->execute($store);

                $list = $this->getDataList();
                foreach ($list as $item) {
                    $session->store($item);
                }
                $session->saveChanges();

                $id = $session->advanced()->getDocumentId($list[0]);
                $this->waitForIndexing($store);
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $options = new MoreLikeThisOptions();
                $options->setFields([ "body" ]);
                $lazyLst = $session->query(Data::class, DataIndex::class)
                        ->moreLikeThis(function($f) use ($id, $options) { return $f->usingDocument(function($b) use ($id) { return $b->whereEquals("id()", $id);})->withOptions($options); })
                        ->lazily();

                $list = $lazyLst->getValue();

                $this->assertNotEmpty($list);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanGetResultsUsingTermVectorsWithDocumentQuery(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                (new DataIndex(true, false))->execute($store);

                $list = $this->getDataList();
                foreach ($list as $item) {
                    $session->store($item);
                }
                $session->saveChanges();

                $id = $session->advanced()->getDocumentId($list[0]);
                $this->waitForIndexing($store);
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {

                $options = new MoreLikeThisOptions();
                $options->setFields([ "body" ]);

                $list = $session->query(Data::class, DataIndex::class)
                        ->moreLikeThis(function($f) use ($id, $options) { return $f->usingDocument(function($x) use ($id) { return $x->whereEquals("id()", $id);})->withOptions($options); })
                        ->toList();

                $this->assertNotEmpty($list);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanGetResultsUsingStorage(): void
    {
        $store = $this->getDocumentStore();
        try {
            $id = "";

            $session = $store->openSession();
            try {
                (new DataIndex(false, true))->execute($store);

                $list = $this->getDataList();
                foreach ($list as $item) {
                    $session->store($item);
                }
                $session->saveChanges();

                $id = $session->advanced()->getDocumentId($list[0]);
                $this->waitForIndexing($store);
            } finally {
                $session->close();
            }

            $this->assertMoreLikeThisHasMatchesFor(Data::class, DataIndex::class, $store, $id);
        } finally {
            $store->close();
        }
    }

    public function testCanGetResultsUsingTermVectorsAndStorage(): void
    {
        $store = $this->getDocumentStore();
        try {
            $id = "";

            $session = $store->openSession();
            try {
                (new DataIndex(true, true))->execute($store);

                $list = $this->getDataList();
                foreach ($list as $item) {
                    $session->store($item);
                }
                $session->saveChanges();

                $id = $session->advanced()->getDocumentId($list[0]);
                $this->waitForIndexing($store);
            } finally {
                $session->close();
            }

            $this->assertMoreLikeThisHasMatchesFor(Data::class, DataIndex::class, $store, $id);
        } finally {
            $store->close();
        }
    }

    public function test_With_Lots_Of_Random_Data(): void
    {
        $store = $this->getDocumentStore();
        try {
            $key = "data/1-A";

            $session = $store->openSession();
            try {
                (new DataIndex())->execute($store);

                for ($i = 0; $i < 100; $i++) {
                    $data = new Data();
                    $data->setBody($this->getLorem(200));
                    $session->store($data);
                }

                $session->saveChanges();

                $this->waitForIndexing($store);
            } finally {
                $session->close();
            }

            $this->assertMoreLikeThisHasMatchesFor(Data::class, DataIndex::class, $store, $key);
        } finally {
            $store->close();
        }
    }

    public function test_do_Not_Pass_FieldNames(): void
    {
        $store = $this->getDocumentStore();
        try {
            $key = "data/1-A";

            $session = $store->openSession();
            try {
                (new DataIndex())->execute($store);

                for ($i = 0; $i < 10; $i++) {
                    $data = new Data();
                    $data->setBody("Body" . $i);
                    $data->setWhitespaceAnalyzerField("test test");
                    $session->store($data);
                }

                $session->saveChanges();
                $this->waitForIndexing($store);
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $list = $session->query(Data::class, DataIndex::class)
                        ->moreLikeThis(function($f) use ($key) { return $f->usingDocument(function($x) use ($key) { return $x->whereEquals("id()", $key); }); })
                        ->toList();

                $this->assertNotEmpty($list);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function test_each_Field_Should_Use_Correct_Analyzer(): void
    {
        $store = $this->getDocumentStore();
        try {
            $key1 = "data/1-A";

            $session = $store->openSession();
            try {
                (new DataIndex())->execute($store);

                for ($i = 0; $i < 10; $i++) {
                    $data = new Data();
                    $data->setWhitespaceAnalyzerField("bob@hotmail.com hotmail");
                    $session->store($data);
                }

                $session->saveChanges();
                $this->waitForIndexing($store);
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $options = new MoreLikeThisOptions();
                $options->setMinimumTermFrequency(2);
                $options->setMinimumDocumentFrequency(5);

                $list = $session->query(Data::class, DataIndex::class)
                        ->moreLikeThis(function($f) use ($key1, $options) { return $f->usingDocument(function($x) use ($key1) { return $x->whereEquals("id()", $key1); })->withOptions($options); })
                        ->toList();

                $this->assertEmpty($list);
            } finally {
                $session->close();
            }

            $key2 = "data/11-A";

            $session = $store->openSession();
            try {
                (new DataIndex())->execute($store);

                for ($i = 0; $i < 10; $i++) {
                    $data = new Data();
                    $data->setWhitespaceAnalyzerField("bob@hotmail.com bob@hotmail.com");
                    $session->store($data);
                }

                $session->saveChanges();
                $this->waitForIndexing($store);
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $list = $session->query(Data::class, DataIndex::class)
                        ->moreLikeThis(function($f) use ($key2) { return $f->usingDocument(function($x) use ($key2) { return $x->whereEquals("id()", $key2); }); })
                        ->toList();

                $this->assertNotEmpty($list);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function test_can_Use_Min_Doc_Freq_Param(): void
    {
        $store = $this->getDocumentStore();
        try {
            $key = "data/1-A";

            $session = $store->openSession();
            try {
                (new DataIndex())->execute($store);

                $factory = function($text) use ($session) {
                    $data = new Data();
                    $data->setBody($text);
                    $session->store($data);
                };

                $factory("This is a test. Isn't it great? I hope I pass my test!");
                $factory("I have a test tomorrow. I hate having a test");
                $factory("Cake is great.");
                $factory("This document has the word test only once");

                $session->saveChanges();

                $this->waitForIndexing($store);
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $options = new MoreLikeThisOptions();
                $options->setFields([ "body" ]);
                $options->setMinimumDocumentFrequency(2);
                $list = $session->query(Data::class, DataIndex::class)
                        ->moreLikeThis(function($f) use ($key, $options) { return $f->usingDocument(function($x) use ($key) { return $x->whereEquals("id()", $key); })->withOptions($options); })
                        ->toList();

                $this->assertNotEmpty($list);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function test_can_Use_Boost_Param(): void
    {
        TestRunGuard::disableTestForRaven6AndLater($this);

        $store = $this->getDocumentStore();
        try {
            $key = "data/1-A";

            $session = $store->openSession();
            try {
                (new DataIndex())->execute($store);

                $factory = function($text) use ($session) {
                    $data = new Data();
                    $data->setBody($text);
                    $session->store($data);
                };

                $factory("This is a test. it is a great test. I hope I pass my great test!");
                $factory("Cake is great.");
                $factory("I have a test tomorrow.");

                $session->saveChanges();

                $this->waitForIndexing($store);
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $options = new MoreLikeThisOptions();

                $options->setFields([ "body" ]);
                $options->setMinimumWordLength(3);
                $options->setMinimumDocumentFrequency(1);
                $options->setMinimumTermFrequency(2);
                $options->setBoost(true);

                $list = $session->query(Data::class, DataIndex::class)
                        ->moreLikeThis(function($f) use ($options, $key) { return $f->usingDocument(function($x) use ($key) { return $x->whereEquals("id()", $key); })->withOptions($options); })
                        ->toList();

                $this->assertNotEmpty($list);

                $this->assertEquals("I have a test tomorrow.", $list[0]->getBody());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function test_can_Use_Stop_Words(): void
    {
        $store = $this->getDocumentStore();
        try {
            $key = "data/1-A";

            (new DataIndex())->execute($store);

            $session = $store->openSession();
            try {
                $factory = function($text) use ($session) {
                    $data = new Data();
                    $data->setBody($text);
                    $session->store($data);
                };

                $factory("This is a test. Isn't it great? I hope I pass my test!");
                $factory("I should not hit this document. I hope");
                $factory("Cake is great.");
                $factory("This document has the word test only once");
                $factory("test");
                $factory("test");
                $factory("test");
                $factory("test");

                $stopWords = new MoreLikeThisStopWords();
                $stopWords->setId("Config/Stopwords");
                $stopWords->setStopWords([ "I", "A", "Be" ]);
                $session->store($stopWords);

                $session->saveChanges();
                $this->waitForIndexing($store);
            } finally {
                $session->close();
            }

            $indexName = (new DataIndex())->getIndexName();

            $session = $store->openSession();
            try {
                $options = new MoreLikeThisOptions();
                $options->setMinimumTermFrequency(2);
                $options->setMinimumDocumentFrequency(1);
                $options->setStopWordsDocumentId("Config/Stopwords");

                $list = $session->query(Data::class, DataIndex::class)
                        ->moreLikeThis(function($f) use ($options, $key) { return $f->usingDocument(function($x) use ($key) { return $x->whereEquals("id()", $key); })->withOptions($options); })
                        ->toList();

                $this->assertCount(5, $list);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanMakeDynamicDocumentQueries(): void
    {
        $store = $this->getDocumentStore();
        try {
            (new DataIndex())->execute($store);

            $session = $store->openSession();
            try {
                $list = $this->getDataList();

                foreach ($list as $item) {
                    $session->store($item);
                }
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $this->waitForIndexing($store);

            $session = $store->openSession();
            try {

                $options = new MoreLikeThisOptions();
                $options->setFields([ "body" ]);
                $options->setMinimumTermFrequency(1);
                $options->setMinimumDocumentFrequency(1);

                $list = $session->query(Data::class, DataIndex::class)
                        ->moreLikeThis(function($f) use ($options) { return $f->usingDocument("{ \"body\": \"A test\" }")->withOptions($options); })
                        ->toList();

                $this->assertCount(7, $list);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanMakeDynamicDocumentQueriesWithComplexProperties(): void
    {
        TestRunGuard::disableTestForRaven6AndLater($this);

        $store = $this->getDocumentStore();
        try {
            (new ComplexDataIndex())->execute($store);

            $session = $store->openSession();
            try {
                $complexProperty = new ComplexProperty();
                $complexProperty->setBody("test");

                $complexData = new ComplexData();
                $complexData->setProperty($complexProperty);

                $session->store($complexData);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $this->waitForIndexing($store);

            $session = $store->openSession();
            try {
                $options = new MoreLikeThisOptions();
                $options->setMinimumTermFrequency(1);
                $options->setMinimumDocumentFrequency(1);

                $list = $session->query(ComplexData::class, ComplexDataIndex::class)
                        ->moreLikeThis(function($f) use ($options) { return $f->usingDocument("{ \"Property\": { \"Body\": \"test\" } }")->withOptions($options); })
                        ->toList();

                $this->assertCount(1, $list);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    private static function assertMoreLikeThisHasMatchesFor(string $className, string $indexClass, DocumentStoreInterface $store, ?string $documentKey): void
    {
        $session = $store->openSession();
        try {
            $options = new MoreLikeThisOptions();
            $options->setFields([ "body" ]);
            $list = $session->query($className, $indexClass)
                    ->moreLikeThis(function($f) use($documentKey, $options) {
                        return $f->usingDocument(function($b) use($documentKey) {
                            return $b->whereEquals("id()", $documentKey);
                        })->withOptions($options);
                    })
                    ->toList();

            self::assertNotEmpty($list);
        } finally {
            $session->close();
        }
    }

}
