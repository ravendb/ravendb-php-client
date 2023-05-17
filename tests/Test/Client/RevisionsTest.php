<?php

namespace tests\RavenDB\Test\Client;

use Exception;
use RavenDB\Constants\DocumentsMetadata;
use RavenDB\Constants\PhpClient;
use RavenDB\Documents\Commands\GetRevisionsBinEntryCommand;
use RavenDB\Documents\Operations\Revisions\ConfigureRevisionsOperation;
use RavenDB\Documents\Operations\Revisions\GetRevisionsOperation;
use RavenDB\Documents\Operations\Revisions\GetRevisionsOperationParameters;
use RavenDB\Documents\Operations\Revisions\RevisionsCollectionConfiguration;
use RavenDB\Documents\Operations\Revisions\RevisionsConfiguration;
use RavenDB\Documents\Operations\Revisions\RevisionsResult;
use RavenDB\Exceptions\RavenException;
use tests\RavenDB\Infrastructure\Entity\Company;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\RemoteTestBase;
use Throwable;

class RevisionsTest extends RemoteTestBase
{
    public function testRevisions(): void
    {
        $store = $this->getDocumentStore();
        try {
            $this->setupRevisions($store, false, 4);

            for ($i = 0; $i < 4; $i++) {
                $session = $store->openSession();
                try {
                    $user = new User();
                    $user->setName("user" . ($i + 1));
                    $session->store($user, "users/1");
                    $session->saveChanges();
                } finally {
                    $session->close();
                }
            }

            $session = $store->openSession();
            try {
                $allRevisions = $session->advanced()->revisions()->getFor(User::class, "users/1");
                $this->assertCount(4, $allRevisions);

                $names = array_map(function ($x) {
                    return $x->getName();
                }, $allRevisions);

                $this->assertEquals(["user4", "user3", "user2", "user1"], $names);

                $revisionsSkipFirst = $session->advanced()->revisions()->getFor(User::class, "users/1", 1);
                $this->assertCount(3, $revisionsSkipFirst);

                $names = array_map(function ($x) {
                    return $x->getName();
                }, $revisionsSkipFirst);

                $this->assertEquals(["user3", "user2", "user1"], $names);

                $revisionsSkipFirstTakeTwo = $session->advanced()->revisions()->getFor(User::class, "users/1", 1, 2);
                $this->assertCount(2, $revisionsSkipFirstTakeTwo);

                $names = array_map(function ($x) {
                    return $x->getName();
                }, $revisionsSkipFirstTakeTwo);

                $this->assertEquals(["user3", "user2"], $names);

                $allMetadata = $session->advanced()->revisions()->getMetadataFor("users/1");
                $this->assertCount(4, $allMetadata);

                $metadataSkipFirst = $session->advanced()->revisions()->getMetadataFor("users/1", 1);
                $this->assertCount(3, $metadataSkipFirst);

                $metadataSkipFirstTakeTwo = $session->advanced()->revisions()->getMetadataFor("users/1", 1, 2);
                $this->assertCount(2, $metadataSkipFirstTakeTwo);

                $user = $session->advanced()->revisions()->get(User::class, $metadataSkipFirst[0]->get(DocumentsMetadata::CHANGE_VECTOR));
                $this->assertEquals("user3", $user->getName());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanListRevisionsBin(): void
    {
        $store = $this->getDocumentStore();
        try {
            $this->setupRevisions($store, false, 4);

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("user1");
                $session->store($user, "users/1");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $session->delete("users/1");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $revisionsBinEntryCommand = new GetRevisionsBinEntryCommand(PhpClient::INT_MAX_VALUE, 20);
            $store->getRequestExecutor()->execute($revisionsBinEntryCommand);

            $result = $revisionsBinEntryCommand->getResult();
            $this->assertCount(1, $result['Results']);

            $this->assertEquals("users/1", strval($result['Results'][0]["@metadata"]["@id"]));
        } finally {
            $store->close();
        }
    }

    public function testCanGetRevisionsByChangeVectors(): void
    {
        $store = $this->getDocumentStore();
        try {
            $id = "users/1";

            $this->setupRevisions($store, false, 100);

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Fitzchak");
                $session->store($user, $id);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            for ($i = 0; $i < 10; $i++) {
                $session = $store->openSession();
                try {
                    $user = $session->load(Company::class, $id);
                    $user->setName("Fitzchak" . $i);
                    $session->saveChanges();
                } finally {
                    $session->close();
                }
            }

            $session = $store->openSession();
            try {
                $revisionsMetadata = $session->advanced()->revisions()->getMetadataFor($id);
                $this->assertCount(11, $revisionsMetadata);

                $changeVectors = array_map(function ($x) {
                    return $x->getString(DocumentsMetadata::CHANGE_VECTOR);
                }, $revisionsMetadata);

                $changeVectors[] = "NotExistsChangeVector";

                $revisions = $session->advanced()->revisions()->get(User::class, $changeVectors);
                $this->assertNull($revisions["NotExistsChangeVector"]);

                $this->assertNull($session->advanced()->revisions()->get(User::class, "NotExistsChangeVector"));
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCollectionCaseSensitiveTest1(): void
    {
        $store = $this->getDocumentStore();
        try {
            $id = "user/1";
            $configuration = new RevisionsConfiguration();

            $collectionConfiguration = new RevisionsCollectionConfiguration();
            $collectionConfiguration->setDisabled(false);

            $collection = [
                "uSErs" => $collectionConfiguration
            ];
            $configuration->setCollections($collection);

            $store->maintenance()->send(new ConfigureRevisionsOperation($configuration));

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("raven");
                $session->store($user, $id);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            for ($i = 0; $i < 10; $i++) {
                $session = $store->openSession();
                try {
                    $user = $session->load(Company::class, $id);
                    $user->setName("raven " . $i);
                    $session->saveChanges();
                } finally {
                    $session->close();
                }
            }

            $session = $store->openSession();
            try {
                $revisionsMetadata = $session->advanced()->revisions()->getMetadataFor($id);
                $this->assertCount(11, $revisionsMetadata);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCollectionCaseSensitiveTest2(): void
    {
        $store = $this->getDocumentStore();
        try {
            $id = "uSEr/1";
            $configuration = new RevisionsConfiguration();

            $collectionConfiguration = new RevisionsCollectionConfiguration();
            $collectionConfiguration->setDisabled(false);

            $collection = [
                "users" => $collectionConfiguration
            ];
            $configuration->setCollections($collection);

            $store->maintenance()->send(new ConfigureRevisionsOperation($configuration));

            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("raven");
                $session->store($user, $id);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            for ($i = 0; $i < 10; $i++) {
                $session = $store->openSession();
                try {
                    $user = $session->load(Company::class, $id);
                    $user->setName("raven " . $i);
                    $session->saveChanges();
                } finally {
                    $session->close();
                }
            }

            $session = $store->openSession();
            try {
                $revisionsMetadata = $session->advanced()->revisions()->getMetadataFor($id);
                $this->assertCount(11, $revisionsMetadata);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCollectionCaseSensitiveTest3(): void
    {
        $store = $this->getDocumentStore();
        try {
            $configuration = new RevisionsConfiguration();

            $c1 = new RevisionsCollectionConfiguration();
            $c1->setDisabled(false);

            $c2 = new RevisionsCollectionConfiguration();
            $c2->setDisabled(false);

            $collection = [
                "users" => $c1,
                "USERS" => $c2
            ];
            $configuration->setCollections($collection);

            try {
                $store->maintenance()->send(new ConfigureRevisionsOperation($configuration));

                throw new Exception('It should throw exception before reaching this code');
            } catch (Throwable $exception) {
                $this->assertInstanceOf(RavenException::class, $exception);
            }
        } finally {
            $store->close();
        }
    }

//    public function testCanGetNonExistingRevisionsByChangeVectorAsyncLazily(): void
//    {
//        $store = $this->getDocumentStore();
//        try {
//            $session = $store->openSession();
//            try {
//                $lazy = $session->advanced()->revisions()->lazily()->get(User::class, "dummy");
//                $user = $lazy->getValue();

//                assertThat(session.advanced().getNumberOfRequests())
//                        .isEqualTo(1);
//                assertThat(user)
//                        .isNull();
//            } finally {
//                $session->close();
//            }
//        } finally {
//            $store->close();
//        }
//    }

//    public function testCanGetRevisionsByChangeVectorsLazily(): void {
//        $store = $this->getDocumentStore();
//        try {
//            $id = "users/1";
//            $this->setupRevisions($store, false, 123);
//            $session = $store->openSession();
//            try {
//                $user = new User();
//                $user->setName("Omer");
//                $session->store($user, id);
//                $session->saveChanges();
//            } finally {
//                $session->close();
//            }
//
//            for ($i = 0; $i < 10; $i++) {
//                $session = $store->openSession();
//                try {
//                    $user = $session->load(Company::class, id);
//                    $user->setName("Omer" + i);
//                    $session->saveChanges();
//                } finally {
//                    $session->close();
//                }
//            }
//
//            $session = $store->openSession();
//            try {
//                $revisionsMetadata = $session->advanced()->revisions()->getMetadataFor(id);
//                assertThat($revisionsMetadata)
//                        .hasSize(11);
//
//                String[] changeVectors = revisionsMetadata
//                        .stream()
//                        .map(x -> x.getString(Constants.Documents.Metadata.CHANGE_VECTOR))
//                        .toArray(String[]::new);
//                String[] changeVectors2 = revisionsMetadata
//                        .stream()
//                        .map(x -> x.getString(Constants.Documents.Metadata.CHANGE_VECTOR))
//                        .toArray(String[]::new);
//
//                Lazy<Map<String, User>> revisionsLazy = $session->advanced()->revisions().lazily().get(User::class, changeVectors);
//                Lazy<Map<String, User>> revisionsLazy2 = $session->advanced()->revisions().lazily().get(User::class, changeVectors2);
//
//                Map<String, User> lazyResult = revisionsLazy.getValue();
//                Map<String, User> revisions = $session->advanced()->revisions().get(User::class, changeVectors);
//
//                assertThat(session.advanced().getNumberOfRequests())
//                        .isEqualTo(3);
//                assertThat(lazyResult.keySet())
//                        .isEqualTo(revisions.keySet());
//            } finally {
//                $session->close();
//            }
//        } finally {
//            $store->close();
//        }
//    }
//
//    @Test
//    public function testCanGetForLazily(): void {
//        $store = $this->getDocumentStore();
//        try {
//            $id = "users/1";
//            $id2 = "users/2";
//
//            $this->setupRevisions($store, false, 123);
//
//            $session = $store->openSession();
//            try {
//                $user1 = new User();
//                user1->setName("Omer");
//                $session->store($user1, id);
//
//                $user2 = new User();
//                user2->setName("Rhinos");
//                $session->store($user2, id2);
//
//                $session->saveChanges();
//            } finally {
//                $session->close();
//            }
//
//            for ($i = 0; $i < 10; $i++) {
//                $session = $store->openSession();
//                try {
//                    $user = $session->load(Company::class, id);
//                    $user->setName("Omer" + i);
//                    $session->saveChanges();
//                } finally {
//                    $session->close();
//                }
//            }
//
//            $session = $store->openSession();
//            try {
//                List<User> revision = $session->advanced()->revisions()->getFor(User::class, "users/1");
//                Lazy<List<User>> revisionsLazily = $session->advanced()->revisions().lazily()->getFor(User::class, "users/1");
//                $session->advanced()->revisions().lazily()->getFor(User::class, "users/2");
//
//                List<User> revisionsLazilyResult = revisionsLazily.getValue();
//
//                assertThat(revision.stream().map(User::getName).collect(Collectors.joining(",")))
//                        .isEqualTo(revisionsLazilyResult.stream().map(User::getName).collect(Collectors.joining(",")));
//                assertThat(revision.stream().map(User::getId).collect(Collectors.joining(",")))
//                        .isEqualTo(revisionsLazilyResult.stream().map(User::getId).collect(Collectors.joining(",")));
//
//                assertThat(session.advanced().getNumberOfRequests())
//                        .isEqualTo(2);
//            } finally {
//                $session->close();
//            }
//        } finally {
//            $store->close();
//        }
//    }
//
//    @Test
//    public function testCanGetRevisionsByIdAndTimeLazily(): void {
//        $store = $this->getDocumentStore();
//        try {
//            $id = "users/1";
//            $id2 = "users/2";
//
//            $this->setupRevisions($store, false, 123);
//
//            $session = $store->openSession();
//            try {
//                $user1 = new User();
//                user1->setName("Omer");
//                $session->store($user1, id);
//
//                $user2 = new User();
//                user2->setName("Rhinos");
//                $session->store($user2, id2);
//
//                $session->saveChanges();
//            } finally {
//                $session->close();
//            }
//
//            for ($i = 0; $i < 10; $i++) {
//                $session = $store->openSession();
//                try {
//                    $user = $session->load(Company::class, id);
//                    $user->setName("Omer" + i);
//                    $session->saveChanges();
//                } finally {
//                    $session->close();
//                }
//            }
//
//            $session = $store->openSession();
//            try {
//                User revision = $session->advanced()->revisions().get(User::class, "users/1", new Date());
//
//                Lazy<User> revisionLazily = $session->advanced()->revisions().lazily().get(User::class, "users/1", new Date());
//                $session->advanced()->revisions().lazily().get(User::class, "users/2", new Date());
//
//                User revisionLazilyResult = revisionLazily.getValue();
//
//                assertThat(revision.getId())
//                        .isEqualTo(revisionLazilyResult.getId());
//                assertThat(revisionLazilyResult.getName())
//                        .isEqualTo(revisionLazilyResult.getName());
//                assertThat(session.advanced().getNumberOfRequests())
//                        .isEqualTo(2);
//            } finally {
//                $session->close();
//            }
//        } finally {
//            $store->close();
//        }
//    }
//
//    @Test
//    public function testCanGetMetadataForLazily(): void {
//        $store = $this->getDocumentStore();
//        try {
//            $id = "users/1";
//            $id2 = "users/2";
//
//            $this->setupRevisions($store, false, 123);
//
//            $session = $store->openSession();
//            try {
//                $user1 = new User();
//                user1->setName("Omer");
//                $session->store($user1, id);
//
//                $user2 = new User();
//                user2->setName("Rhinos");
//                $session->store($user2, id2);
//
//                $session->saveChanges();
//            } finally {
//                $session->close();
//            }
//
//            for ($i = 0; $i < 10; $i++) {
//                $session = $store->openSession();
//                try {
//                    $user = $session->load(Company::class, id);
//                    $user->setName("Omer" + i);
//                    $session->saveChanges();
//                } finally {
//                    $session->close();
//                }
//            }
//
//            $session = $store->openSession();
//            try {
//                $revisionsMetadata = $session->advanced()->revisions()->getMetadataFor(id);
//                Lazy<List<MetadataAsDictionary>> revisionsMetaDataLazily = $session->advanced()->revisions().lazily()->getMetadataFor(id);
//                Lazy<List<MetadataAsDictionary>> revisionsMetaDataLazily2 = $session->advanced()->revisions().lazily()->getMetadataFor(id2);
//                List<MetadataAsDictionary> revisionsMetaDataLazilyResult = revisionsMetaDataLazily.getValue();
//
//                assertThat(revisionsMetadata.stream().map(x -> x.getString("@id")).collect(Collectors.joining(",")))
//                        .isEqualTo(revisionsMetaDataLazilyResult.stream().map(x -> x.getString("@id")).collect(Collectors.joining(",")));
//
//                assertThat(session.advanced().getNumberOfRequests())
//                        .isEqualTo(2);
//            } finally {
//                $session->close();
//            }
//        } finally {
//            $store->close();
//        }
//    }
//
//    @Test
//    public function testCanGetRevisionsByChangeVectorLazily(): void {
//        $store = $this->getDocumentStore();
//        try {
//
//
//            $id = "users/1";
//            $id2 = "users/2";
//
//            $this->setupRevisions($store, false, 123);
//
//            $session = $store->openSession();
//            try {
//                $user1 = new User();
//                user1->setName("Omer");
//                $session->store($user1, id);
//
//                $user2 = new User();
//                user2->setName("Rhinos");
//                $session->store($user2, id2);
//
//                $session->saveChanges();
//            } finally {
//                $session->close();
//            }
//
//            for ($i = 0; $i < 10; $i++) {
//                $session = $store->openSession();
//                try {
//                    $user = $session->load(Company::class, id);
//                    $user->setName("Omer" + i);
//                    $session->saveChanges();
//                } finally {
//                    $session->close();
//                }
//            }
//
//            DatabaseStatistics stats = $store->maintenance()->send(new GetStatisticsOperation());
//            String dbId = stats.getDatabaseId();
//
//            String cv = "A:23-" + dbId;
//            String cv2 = "A:3-" + dbId;
//
//            $session = $store->openSession();
//            try {
//                User revisions = $session->advanced()->revisions().get(User::class, cv);
//                Lazy<User> revisionsLazily = $session->advanced()->revisions().lazily().get(User::class, cv);
//                Lazy<User> revisionsLazily1 = $session->advanced()->revisions().lazily().get(User::class, cv2);
//
//                User revisionsLazilyValue = revisionsLazily.getValue();
//
//                assertThat(session.advanced().getNumberOfRequests())
//                        .isEqualTo(2);
//                assertThat(revisionsLazilyValue.getId())
//                        .isEqualTo(revisions.getId());
//                assertThat(revisionsLazilyValue.getName())
//                        .isEqualTo(revisions.getName());
//            } finally {
//                $session->close();
//            }
//        } finally {
//            $store->close();
//        }
//    }

    public function testCanGetAllRevisionsForDocument_UsingStoreOperation(): void
    {
        $company = new Company();
        $company->setName("Company Name");
        $store = $this->getDocumentStore();
        try {
            $this->setupRevisions($store, false, 123);

            $session = $store->openSession();
            try {
                $session->store($company);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $company3 = $session->load(Company::class, $company->getId());
                $company3->setName("Hibernating Rhinos");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $revisionsResult = $store->operations()->send(new GetRevisionsOperation(Company::class, $company->getId()));

            $this->assertEquals(2, $revisionsResult->getTotalResults());

            $companiesRevisions = $revisionsResult->getResults();
            $this->assertCount(2, $companiesRevisions);
            $this->assertEquals("Hibernating Rhinos", $companiesRevisions[0]->getName());
            $this->assertEquals("Company Name", $companiesRevisions[1]->getName());
        } finally {
            $store->close();
        }
    }

    public function testCanGetRevisionsWithPaging_UsingStoreOperation(): void
    {
        $store = $this->getDocumentStore();
        try {
            $this->setupRevisions($store, false, 123);

            $id = "companies/1";

            $session = $store->openSession();
            try {
                $session->store(new Company(), $id);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $company2 = $session->load(Company::class, $id);
                $company2->setName("Hibernating");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $company3 = $session->load(Company::class, $id);
                $company3->setName("Hibernating Rhinos");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            for ($i = 0; $i < 10; $i++) {
                $session = $store->openSession();
                try {
                    $company = $session->load(Company::class, $id);
                    $company->setName("HR" . $i);
                    $session->saveChanges();
                } finally {
                    $session->close();
                }
            }

            $parameters = new GetRevisionsOperationParameters();
            $parameters->setId($id);
            $parameters->setStart(10);

            /** @var RevisionsResult $revisionsResult */
            $revisionsResult = $store->operations()->send(new GetRevisionsOperation(Company::class, $parameters));

            $this->assertEquals(13, $revisionsResult->getTotalResults());

            $companiesRevisions = $revisionsResult->getResults();
            $this->assertCount(3, $companiesRevisions);

            $this->assertEquals("Hibernating Rhinos", $companiesRevisions[0]->getName());
            $this->assertEquals("Hibernating", $companiesRevisions[1]->getName());
            $this->assertNull($companiesRevisions[2]->getName());
        } finally {
            $store->close();
        }
    }

    public function testCanGetRevisionsWithPaging2_UsingStoreOperation(): void
    {
        $store = $this->getDocumentStore();
        try {
            $this->setupRevisions($store, false, 100);

            $id = "companies/1";

            $session = $store->openSession();
            try {
                $session->store(new Company(), $id);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            for ($i = 0; $i < 99; $i++) {
                $session = $store->openSession();
                try {
                    $company = $session->load(Company::class, $id);
                    $company->setName("HR" . $i);
                    $session->saveChanges();
                } finally {
                    $session->close();
                }
            }

            $revisionsResult = $store->operations()->send(new GetRevisionsOperation(Company::class, $id, 50, 10));


            $this->assertEquals(100, $revisionsResult->getTotalResults());

            $companiesRevisions = $revisionsResult->getResults();
            $this->assertCount(10, $companiesRevisions);

            $count = 0;
            for ($i = 48; $i > 38; $i--) {
                $this->assertEquals("HR" . $i, $companiesRevisions[$count++]->getName());
            }
        } finally {
            $store->close();
        }
    }

    public function testCanGetRevisionsCountFor(): void
    {
        $company = new Company();
        $company->setName("Company Name");

        $store = $this->getDocumentStore();
        try {
            $this->setupRevisions($store, false, 100);

            $session = $store->openSession();
            try {
                $session->store($company);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $company2 = $session->load(Company::class, $company->getId());
                $company2->setAddress1("Israel");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $company3 = $session->load(Company::class, $company->getId());
                $company3->setName("Hibernating Rhinos");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $companiesRevisionsCount = $session->advanced()->revisions()->getCountFor($company->getId());
                $this->assertEquals(3, $companiesRevisionsCount);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
