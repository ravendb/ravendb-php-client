<?php

namespace tests\RavenDB\Test;

use Exception;
use RavenDB\Documents\Operations\Revisions\ConfigureRevisionsOperation;
use RavenDB\Documents\Operations\Revisions\RevisionsCollectionConfiguration;
use RavenDB\Documents\Operations\Revisions\RevisionsConfiguration;
use RavenDB\Documents\Session\ForceRevisionStrategy;
use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Exceptions\RavenException;
use tests\RavenDB\Infrastructure\Entity\Company;
use tests\RavenDB\Infrastructure\TestRunGuard;
use tests\RavenDB\RemoteTestBase;
use Throwable;

class ForceRevisionCreationTest extends RemoteTestBase
{
    public function testForceRevisionCreationForSingleUnTrackedEntityByID(): void
    {
        $store = $this->getDocumentStore();
        try {
            $companyId = '';

            $session = $store->openSession();
            try {
                $company = new Company();
                $company->setName("HR");
                $session->store($company);

                $companyId = $company->getId();
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $session->advanced()->revisions()->forceRevisionCreationFor($companyId);
                $session->saveChanges();

                $revisionsCount = count($session->advanced()->revisions()->getFor(Company::class, $companyId));
                $this->assertEquals(1, $revisionsCount);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testForceRevisionCreationForMultipleUnTrackedEntitiesByID(): void
    {
        $store = $this->getDocumentStore();
        try {
            $companyId1 = '';
            $companyId2 = '';

            $session = $store->openSession();
            try {
                $company1 = new Company();
                $company1->setName("HR1");

                $company2 = new Company();
                $company2->setName("HR2");

                $session->store($company1);
                $session->store($company2);

                $companyId1 = $company1->getId();
                $companyId2 = $company2->getId();

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $session->advanced()->revisions()->forceRevisionCreationFor($companyId1);
                $session->advanced()->revisions()->forceRevisionCreationFor($companyId2);

                $session->saveChanges();

                $revisionsCount1 = count($session->advanced()->revisions()->getFor(Company::class, $companyId1));
                $revisionsCount2 = count($session->advanced()->revisions()->getFor(Company::class, $companyId2));

                $this->assertEquals(1, $revisionsCount1);
                $this->assertEquals(1, $revisionsCount2);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCannotForceRevisionCreationForUnTrackedEntityByEntity(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $company = new Company();
                $company->setName("HR");

                try {
                    $session->advanced()->revisions()->forceRevisionCreationFor($company);

                    throw new Exception('It should throw exception before reaching this code');
                } catch (Throwable $exception) {
                    $this->assertInstanceOf(IllegalStateException::class, $exception);
                    $this->assertStringContainsString("Cannot create a revision for the requested entity because it is Not tracked by the session", $exception->getMessage());
                };
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testForceRevisionCreationForNewDocumentByEntity(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $company = new Company();
                $company->setName("HR");
                $session->store($company);
                $session->saveChanges();

                $session->advanced()->revisions()->forceRevisionCreationFor($company);

                $revisionsCount = count($session->advanced()->revisions()->getFor(Company::class, $company->getId()));
                $this->assertEquals(0, $revisionsCount);

                $session->saveChanges();

                $revisionsCount = count($session->advanced()->revisions()->getFor(Company::class, $company->getId()));
                $this->assertEquals(1, $revisionsCount);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCannotForceRevisionCreationForNewDocumentBeforeSavingToServerByEntity(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $company = new Company();
                $company->setName("HR");
                $session->store($company);

                $session->advanced()->revisions()->forceRevisionCreationFor($company);

                try {
                    $session->saveChanges();

                    throw new Exception('It should throw exception before reaching this code');
                } catch (Throwable $exception) {
                    $this->assertInstanceOf(RavenException::class, $exception);
                    $this->assertStringContainsString("Can't force revision creation - the document was not saved on the server yet", $exception->getMessage());
                };

                $revisionsCount = count($session->advanced()->revisions()->getFor(Company::class, $company->getId()));
                $this->assertEquals(0, $revisionsCount);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testForceRevisionCreationForTrackedEntityWithNoChangesByEntity(): void
    {
        $store = $this->getDocumentStore();
        try {
            $companyId = "";

            $session = $store->openSession();
            try {
                // 1. store document
                $company = new Company();
                $company->setName("HR");
                $session->store($company);
                $session->saveChanges();

                $companyId = $company->getId();

                $revisionsCount = count($session->advanced()->revisions()->getFor(Company::class, $company->getId()));
                $this->assertEquals(0, $revisionsCount);
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                // 2. Load & Save without making changes to the document
                $company = $session->load(Company::class, $companyId);

                $session->advanced()->revisions()->forceRevisionCreationFor($company);
                $session->saveChanges();

                $revisionsCount = count($session->advanced()->revisions()->getFor(Company::class, $companyId));
                $this->assertEquals(1, $revisionsCount);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testForceRevisionCreationForTrackedEntityWithChangesByEntity(): void
    {
        $store = $this->getDocumentStore();
        try {
            $companyId = "";

            // 1. Store document
            $session = $store->openSession();
            try {
                $company = new Company();
                $company->setName("HR");
                $session->store($company);
                $session->saveChanges();

                $companyId = $company->getId();

                $revisionsCount = count($session->advanced()->revisions()->getFor(Company::class, $company->getId()));
                $this->assertEquals(0, $revisionsCount);
            } finally {
                $session->close();
            }

            // 2. Load, Make changes & Save
            $session = $store->openSession();
            try {
                $company = $session->load(Company::class, $companyId);
                $company->setName("HR V2");

                $session->advanced()->revisions()->forceRevisionCreationFor($company);
                $session->saveChanges();

                $revisions = $session->advanced()->revisions()->getFor(Company::class, $company->getId());
                $revisionsCount = count($revisions);

                $this->assertEquals(1, $revisionsCount);

                // Assert revision contains the value 'Before' the changes...
                // ('Before' is the default force revision creation strategy)
                $this->assertEquals("HR", $revisions[0]->getName());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testForceRevisionCreationForTrackedEntityWithChangesByID(): void
    {
        $store = $this->getDocumentStore();
        try {
            $companyId = "";

            // 1. Store document
            $session = $store->openSession();
            try {
                $company = new Company();
                $company->setName("HR");
                $session->store($company);
                $session->saveChanges();

                $companyId = $company->getId();

                $revisionsCount = count($session->advanced()->revisions()->getFor(Company::class, $company->getId()));
                $this->assertEquals(0, $revisionsCount);
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                // 2. Load, Make changes & Save
                $company = $session->load(Company::class, $companyId);
                $company->setName("HR V2");

                $session->advanced()->revisions()->forceRevisionCreationFor($company->getId());
                $session->saveChanges();

                $revisions = $session->advanced()->revisions()->getFor(Company::class, $company->getId());
                $revisionsCount = count($revisions);

                $this->assertEquals(1, $revisionsCount);

                // Assert revision contains the value 'Before' the changes...
                $this->assertEquals("HR", $revisions[0]->getName());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testForceRevisionCreationMultipleRequests(): void
    {
        $store = $this->getDocumentStore();
        try {
            $companyId = "";

            $session = $store->openSession();
            try {
                $company = new Company();
                $company->setName("HR");
                $session->store($company);
                $session->saveChanges();

                $companyId = $company->getId();

                $revisionsCount = count($session->advanced()->revisions()->getFor(Company::class, $company->getId()));
                $this->assertEquals(0, $revisionsCount);
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $session->advanced()->revisions()->forceRevisionCreationFor($companyId);

                $company = $session->load(Company::class, $companyId);
                $company->setName("HR V2");

                $session->advanced()->revisions()->forceRevisionCreationFor($company);
                // The above request should not throw - we ignore duplicate requests with SAME strategy

                try {
                    $session->advanced()->revisions()->forceRevisionCreationFor($company->getId(), ForceRevisionStrategy::none());
                    throw new Exception('It should throw exception before reaching this code');
                } catch (Throwable $exception) {
                    $this->assertInstanceOf(IllegalStateException::class, $exception);
                    $this->assertStringContainsString("A request for creating a revision was already made for document", $exception->getMessage());
                };

                $session->saveChanges();

                $revisions = $session->advanced()->revisions()->getFor(Company::class, $company->getId());
                $revisionsCount = count($revisions);

                $this->assertEquals(1, $revisionsCount);
                $this->assertEquals("HR", $revisions[0]->getName());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testForceRevisionCreationAcrossMultipleSessions(): void
    {
        $store = $this->getDocumentStore();
        try {
            $companyId = "";

            $session = $store->openSession();
            try {
                $company = new Company();
                $company->setName("HR");

                $session->store($company);
                $session->saveChanges();

                $companyId = $company->getId();
                $revisionsCount = count($session->advanced()->revisions()->getFor(Company::class, $company->getId()));
                $this->assertEquals(0, $revisionsCount);

                $session->advanced()->revisions()->forceRevisionCreationFor($company);
                $session->saveChanges();

                $revisionsCount = count($session->advanced()->revisions()->getFor(Company::class, $company->getId()));
                $this->assertEquals(1, $revisionsCount);

                // Verify that another 'force' request will not create another revision
                $session->advanced()->revisions()->forceRevisionCreationFor($company);
                $session->saveChanges();

                $revisionsCount = count($session->advanced()->revisions()->getFor(Company::class, $company->getId()));
                $this->assertEquals(1, $revisionsCount);
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $company = $session->load(Company::class, $companyId);
                $company->setName("HR V2");

                $session->advanced()->revisions()->forceRevisionCreationFor($company);
                $session->saveChanges();

                $revisions = $session->advanced()->revisions()->getFor(Company::class, $company->getId());
                $revisionsCount = count($revisions);


                $this->assertEquals(1, $revisionsCount);

                // Assert revision contains the value 'Before' the changes...
                $this->assertEquals("HR", $revisions[0]->getName());

                $session->advanced()->revisions()->forceRevisionCreationFor($company);
                $session->saveChanges();

                $revisions = $session->advanced()->revisions()->getFor(Company::class, $company->getId());
                $revisionsCount = count($revisions);

                $this->assertEquals(2, $revisionsCount);

                // Assert revision contains the value 'Before' the changes...
                $this->assertEquals("HR V2", $revisions[0]->getName());
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $company = $session->load(Company::class, $companyId);
                $company->setName("HR V3");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $session->advanced()->revisions()->forceRevisionCreationFor($companyId);
                $session->saveChanges();

                $revisions = $session->advanced()->revisions()->getFor(Company::class, $companyId);
                $revisionsCount = count($revisions);

                $this->assertEquals(3, $revisionsCount);
                $this->assertEquals("HR V3", $revisions[0]->getName());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testForceRevisionCreationWhenRevisionConfigurationIsSet(): void
    {
        TestRunGuard::disableTestIfLicenseNotAvailableForV6($this);

        $store = $this->getDocumentStore();
        try {
            // Define revisions settings
            $configuration = new RevisionsConfiguration();

            $companiesConfiguration = new RevisionsCollectionConfiguration();
            $companiesConfiguration->setPurgeOnDelete(true);
            $companiesConfiguration->setMinimumRevisionsToKeep(5);

            $configuration->setCollections(["Companies" => $companiesConfiguration ]);

            $result = $store->maintenance()->send(new ConfigureRevisionsOperation($configuration));
            $companyId = "";

            $session = $store->openSession();
            try {
                $company = new Company();
                $company->setName("HR");
                $session->store($company);
                $companyId = $company->getId();
                $session->saveChanges();

                $revisionsCount = count($session->advanced()->revisions()->getFor(Company::class, $company->getId()));
                $this->assertEquals(1, $revisionsCount); // one revision because configuration is set

                $session->advanced()->revisions()->forceRevisionCreationFor($company);
                $session->saveChanges();

                $revisionsCount = count($session->advanced()->revisions()->getFor(Company::class, $company->getId()));
                $this->assertEquals(1, $revisionsCount); // no new revision created - already exists due to configuration settings

                $session->advanced()->revisions()->forceRevisionCreationFor($company);
                $session->saveChanges();

                $company->setName("HR V2");
                $session->saveChanges();

                $revisions = $session->advanced()->revisions()->getFor(Company::class, $companyId);
                $revisionsCount = count($revisions);

                $this->assertEquals(2, $revisionsCount);
                $this->assertEquals("HR V2", $revisions[0]->getName());
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $session->advanced()->revisions()->forceRevisionCreationFor($companyId);
                $session->saveChanges();

                $revisions = $session->advanced()->revisions()->getFor(Company::class, $companyId);
                $revisionsCount = count($revisions);

                $this->assertEquals(2, $revisionsCount);
                $this->assertEquals("HR V2", $revisions[0]->getName());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testHasRevisionsFlagIsCreatedWhenForcingRevisionForDocumentThatHasNoRevisionsYet(): void
    {
        $store = $this->getDocumentStore();
        try {
            $company1Id = "";
            $company2Id = "";

            $session = $store->openSession();
            try {
                $company1 = new Company();
                $company1->setName("HR1");

                $company2 = new Company();
                $company2->setName("HR2");

                $session->store($company1);
                $session->store($company2);

                $session->saveChanges();

                $company1Id = $company1->getId();
                $company2Id = $company2->getId();

                $revisionsCount = count($session->advanced()->revisions()->getFor(Company::class, $company1->getId()));
                $this->assertEquals(0, $revisionsCount);

                $revisionsCount = count($session->advanced()->revisions()->getFor(Company::class, $company2->getId()));
                $this->assertEquals(0, $revisionsCount);
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                // Force revision with no changes on document
                $session->advanced()->revisions()->forceRevisionCreationFor($company1Id);

                // Force revision with changes on document
                $session->advanced()->revisions()->forceRevisionCreationFor($company2Id);
                $company2 = $session->load(Company::class, $company2Id);
                $company2->setName("HR2 New Name");

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $revisions = $session->advanced()->revisions()->getFor(Company::class, $company1Id);
                $revisionsCount = count($revisions);
                $this->assertEquals(1, $revisionsCount);
                $this->assertEquals("HR1", $revisions[0]->getName());

                $revisions = $session->advanced()->revisions()->getFor(Company::class, $company2Id);
                $revisionsCount = count($revisions);
                $this->assertEquals(1, $revisionsCount);
                $this->assertEquals("HR2", $revisions[0]->getName());

                // Assert that HasRevisions flag was created on both documents
                $company = $session->load(Company::class, $company1Id);
                $metadata = $session->advanced()->getMetadataFor($company);
                $this->assertEquals("HasRevisions", $metadata->get("@flags"));

                $company = $session->load(Company::class, $company2Id);
                $metadata = $session->advanced()->getMetadataFor($company);
                $this->assertEquals("HasRevisions", $metadata->get("@flags"));
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
