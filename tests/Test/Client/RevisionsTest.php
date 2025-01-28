<?php

namespace tests\RavenDB\Test\Client;

use DateTime;
use Exception;
use RavenDB\Constants\DocumentsMetadata;
use RavenDB\Constants\PhpClient;
use RavenDB\Documents\Commands\GetRevisionsBinEntryCommand;
use RavenDB\Documents\Operations\GetStatisticsOperation;
use RavenDB\Documents\Operations\Revisions\ConfigureRevisionsOperation;
use RavenDB\Documents\Operations\Revisions\GetRevisionsOperation;
use RavenDB\Documents\Operations\Revisions\GetRevisionsOperationParameters;
use RavenDB\Documents\Operations\Revisions\RevisionsCollectionConfiguration;
use RavenDB\Documents\Operations\Revisions\RevisionsConfiguration;
use RavenDB\Documents\Operations\Revisions\RevisionsResult;
use RavenDB\Exceptions\RavenException;
use tests\RavenDB\Infrastructure\Entity\Company;
use tests\RavenDB\Infrastructure\Entity\User;
use tests\RavenDB\Infrastructure\TestRunGuard;
use tests\RavenDB\RemoteTestBase;
use Throwable;

class RevisionsTest extends RemoteTestBase
{
    public function testRevisions(): void
    {
        TestRunGuard::disableTestIfLicenseNotAvailableForV6($this);

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
        TestRunGuard::disableTestForRaven52($this);
        TestRunGuard::disableTestForRaven6AndLater($this);

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

            $revisionsBinEntryCommand = new GetRevisionsBinEntryCommand(0, 20);
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
        TestRunGuard::disableTestIfLicenseNotAvailableForV6($this);

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

    public function testCanGetNonExistingRevisionsByChangeVectorAsyncLazily(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $lazy = $session->advanced()->revisions()->lazily()->get(User::class, "dummy");
                $user = $lazy->getValue();

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
                $this->assertNull($user);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanGetRevisionsByChangeVectorsLazily(): void
    {
        TestRunGuard::disableTestIfLicenseNotAvailableForV6($this);

        $store = $this->getDocumentStore();
        try {
            $id = "users/1";
            $this->setupRevisions($store, false, 123);
            $session = $store->openSession();
            try {
                $user = new User();
                $user->setName("Omer");
                $session->store($user, $id);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            for ($i = 0; $i < 10; $i++) {
                $session = $store->openSession();
                try {
                    $user = $session->load(Company::class, $id);
                    $user->setName("Omer" . $i);
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

                $changeVectors2 = array_map(function ($x) {
                    return $x->getString(DocumentsMetadata::CHANGE_VECTOR);
                }, $revisionsMetadata);

                $revisionsLazy = $session->advanced()->revisions()->lazily()->get(User::class, $changeVectors);
                $revisionsLazy2 = $session->advanced()->revisions()->lazily()->get(User::class, $changeVectors2);

                $lazyResult = $revisionsLazy->getValue();
                $revisions = $session->advanced()->revisions()->get(User::class, $changeVectors);

                $this->assertEquals(3, $session->advanced()->getNumberOfRequests());
                $this->assertEquals(array_keys($revisions), array_keys($lazyResult));

            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanGetForLazily(): void
    {
        TestRunGuard::disableTestIfLicenseNotAvailableForV6($this);

        $store = $this->getDocumentStore();
        try {
            $id = "users/1";
            $id2 = "users/2";

            $this->setupRevisions($store, false, 123);

            $session = $store->openSession();
            try {
                $user1 = new User();
                $user1->setName("Omer");
                $session->store($user1, $id);

                $user2 = new User();
                $user2->setName("Rhinos");
                $session->store($user2, $id2);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            for ($i = 0; $i < 10; $i++) {
                $session = $store->openSession();
                try {
                    $user = $session->load(Company::class, $id);
                    $user->setName("Omer" . $i);
                    $session->saveChanges();
                } finally {
                    $session->close();
                }
            }

            $session = $store->openSession();
            try {
                $revision = $session->advanced()->revisions()->getFor(User::class, "users/1");
                $revisionsLazily = $session->advanced()->revisions()->lazily()->getFor(User::class, "users/1");
                $session->advanced()->revisions()->lazily()->getFor(User::class, "users/2");

                $revisionsLazilyResult = $revisionsLazily->getValue();

                $this->assertEquals(
                    implode(',', array_map(function ($x) {
                        return $x->getName();
                    }, $revision)),
                    implode(',', array_map(function ($x) {
                        return $x->getName();
                    }, $revisionsLazilyResult))
                );

                $this->assertEquals(
                    implode(',', array_map(function ($x) {
                        return $x->getId();
                    }, $revision)),
                    implode(',', array_map(function ($x) {
                        return $x->getId();
                    }, $revisionsLazilyResult))
                );

                $this->assertEquals(2, $session->advanced()->getNumberOfRequests());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanGetRevisionsByIdAndTimeLazily(): void
    {
        TestRunGuard::disableTestIfLicenseNotAvailableForV6($this);

        $store = $this->getDocumentStore();
        try {
            $id = "users/1";
            $id2 = "users/2";

            $this->setupRevisions($store, false, 123);

            $session = $store->openSession();
            try {
                $user1 = new User();
                $user1->setName("Omer");
                $session->store($user1, $id);

                $user2 = new User();
                $user2->setName("Rhinos");
                $session->store($user2, $id2);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $revision = $session->advanced()->lazily()->load(User::class, 'users/1');
                $doc = $revision->getValue();
                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $revision = $session->advanced()->revisions()->getBeforeDate(User::class, "users/1", new DateTime());

                $revisionLazily = $session->advanced()->revisions()->lazily()->getBeforeDate(User::class, "users/1", new DateTime());
                $session->advanced()->revisions()->lazily()->getBeforeDate(User::class, "users/2", new DateTime());

                $revisionLazilyResult = $revisionLazily->getValue();

                $this->assertEquals($revisionLazilyResult->getId(), $revision->getId());
                $this->assertEquals($revisionLazilyResult->getName(), $revisionLazilyResult->getName());
                $this->assertEquals(2, $session->advanced()->getNumberOfRequests());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanGetMetadataForLazily(): void
    {
        TestRunGuard::disableTestIfLicenseNotAvailableForV6($this);

        $store = $this->getDocumentStore();
        try {
            $id = "users/1";
            $id2 = "users/2";

            $this->setupRevisions($store, false, 123);

            $session = $store->openSession();
            try {
                $user1 = new User();
                $user1->setName("Omer");
                $session->store($user1, $id);

                $user2 = new User();
                $user2->setName("Rhinos");
                $session->store($user2, $id2);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            for ($i = 0; $i < 10; $i++) {
                $session = $store->openSession();
                try {
                    $user = $session->load(Company::class, $id);
                    $user->setName("Omer" . $i);
                    $session->saveChanges();
                } finally {
                    $session->close();
                }
            }

            $session = $store->openSession();
            try {
                $revisionsMetadata = $session->advanced()->revisions()->getMetadataFor($id);
                $revisionsMetaDataLazily = $session->advanced()->revisions()->lazily()->getMetadataFor($id);
                $revisionsMetaDataLazily2 = $session->advanced()->revisions()->lazily()->getMetadataFor($id2);
                $revisionsMetaDataLazilyResult = $revisionsMetaDataLazily->getValue();


                $this->assertEquals(
                    implode(',', array_map(function ($x) use ($id) {
                        return $x->getString($id);
                    }, $revisionsMetadata)),
                    implode(',', array_map(function ($x) use ($id) {
                        return $x->getString($id);
                    }, $revisionsMetaDataLazilyResult))
                );

                $this->assertEquals(2, $session->advanced()->getNumberOfRequests());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanGetRevisionsByChangeVectorLazily(): void
    {
        TestRunGuard::disableTestIfLicenseNotAvailableForV6($this);

        $store = $this->getDocumentStore();
        try {

            $id = "users/1";
            $id2 = "users/2";

            $this->setupRevisions($store, false, 123);

            $session = $store->openSession();
            try {
                $user1 = new User();
                $user1->setName("Omer");
                $session->store($user1, $id);

                $user2 = new User();
                $user2->setName("Rhinos");
                $session->store($user2, $id2);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            for ($i = 0; $i < 10; $i++) {
                $session = $store->openSession();
                try {
                    $user = $session->load(Company::class, $id);
                    $user->setName("Omer" . $i);
                    $session->saveChanges();
                } finally {
                    $session->close();
                }
            }

            $stats = $store->maintenance()->send(new GetStatisticsOperation());
            $dbId = $stats->getDatabaseId();

            $cv = "A:23-" . $dbId;
            $cv2 = "A:3-" . $dbId;

            $session = $store->openSession();
            try {
                $revisions = $session->advanced()->revisions()->get(User::class, $cv);
                $revisionsLazily = $session->advanced()->revisions()->lazily()->get(User::class, $cv);
                $revisionsLazily1 = $session->advanced()->revisions()->lazily()->get(User::class, $cv2);

                $revisionsLazilyValue = $revisionsLazily->getValue();

                $this->assertEquals(2, $session->advanced()->getNumberOfRequests());
                $this->assertEquals($revisions->getId(), $revisionsLazilyValue->getId());
                $this->assertEquals($revisions->getName(), $revisionsLazilyValue->getName());
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $revisions = $session->advanced()->revisions()->get(User::class, $cv);
                $revisionsLazily = $session->advanced()->revisions()->lazily()->get(User::class, $cv);
                $revisionsLazilyValue = $revisionsLazily->getValue();

                $this->assertEquals(2, $session->advanced()->getNumberOfRequests());
                $this->assertEquals($revisions->getId(), $revisionsLazilyValue->getId());
                $this->assertEquals($revisions->getName(), $revisionsLazilyValue->getName());
            } finally {
                $session->close();
            }

        } finally {
            $store->close();
        }
    }

    public function testCanGetAllRevisionsForDocument_UsingStoreOperation(): void
    {
        TestRunGuard::disableTestIfLicenseNotAvailableForV6($this);

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
        TestRunGuard::disableTestIfLicenseNotAvailableForV6($this);

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
        TestRunGuard::disableTestIfLicenseNotAvailableForV6($this);

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
        TestRunGuard::disableTestIfLicenseNotAvailableForV6($this);

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
