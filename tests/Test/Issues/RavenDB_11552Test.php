<?php

namespace tests\RavenDB\Test\Issues;

use RavenDB\Documents\Commands\Batches\CopyAttachmentCommandData;
use RavenDB\Documents\Commands\Batches\DeleteAttachmentCommandData;
use RavenDB\Documents\Commands\Batches\DeleteCommandData;
use RavenDB\Documents\Commands\Batches\MoveAttachmentCommandData;
use RavenDB\Documents\Commands\Batches\PatchCommandData;
use RavenDB\Documents\Commands\Batches\PutAttachmentCommandData;
use RavenDB\Documents\Operations\PatchRequest;
use tests\RavenDB\Infrastructure\Entity\Company;
use tests\RavenDB\RemoteTestBase;

class RavenDB_11552Test extends RemoteTestBase
{
    public function testPatchWillUpdateTrackedDocumentAfterSaveChanges(): void
    {
        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                $company = new Company();
                $company->setName("HR");
                $session->store($company, "companies/1");

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                /** @var Company $company */
                $company = $session->load(Company::class, "companies/1");
                $session->advanced()->patch($company, "name", "CF");

                $cv = $session->advanced()->getChangeVectorFor($company);
                $lastModified = $session->advanced()->getLastModifiedFor($company);

                $session->saveChanges();

                $this->assertEquals("CF", $company->getName());

                $this->assertNotEquals($cv, $session->advanced()->getChangeVectorFor($company));
                $this->assertNotEquals($lastModified, $session->advanced()->getLastModifiedFor($company));

                $company->setPhone(123);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                /** @var Company $company */
                $company = $session->load(Company::class, "companies/1");

                $this->assertEquals("CF", $company->getName());
                $this->assertEquals(123, $company->getPhone());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testDeleteWillWork(): void
    {
        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                $company = new Company();
                $company->setName("HR");
                $session->store($company, "companies/1");

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $company = $session->load(Company::class, "companies/1");

                $this->assertNotNull($company);

                $this->assertTrue($session->advanced()->isLoaded("companies/1"));
                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                $session->advanced()->defer(new DeleteCommandData("companies/1", null));
                $session->saveChanges();

                $this->assertFalse($session->advanced()->isLoaded("companies/1"));
                $this->assertEquals(2, $session->advanced()->getNumberOfRequests());

                $company = $session->load(Company::class, "companies/1");
                $this->assertNull($company);

                $this->assertTrue($session->advanced()->isLoaded("companies/1"));

                $this->assertEquals(3, $session->advanced()->getNumberOfRequests());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testPatchWillWork(): void
    {
        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                $company = new Company();
                $company->setName("HR");
                $session->store($company, "companies/1");

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $company = $session->load(Company::class, "companies/1");

                $this->assertNotNull($company);
                $this->assertTrue($session->advanced()->isLoaded("companies/1"));
                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());

                $patchRequest = new PatchRequest();
                $patchRequest->setScript("this.name = 'HR2';");

                $session->advanced()->defer(new PatchCommandData("companies/1", null, $patchRequest, null));
                $session->saveChanges();

                $this->assertTrue($session->advanced()->isLoaded("companies/1"));
                $this->assertEquals(2, $session->advanced()->getNumberOfRequests());

                $company2 = $session->load(Company::class, "companies/1");

                $this->assertNotNull($company2);
                $this->assertTrue($session->advanced()->isLoaded("companies/1"));
                $this->assertEquals(2, $session->advanced()->getNumberOfRequests());

                $this->assertEquals($company, $company2);

                $this->assertEquals("HR2", $company2->getName());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testAttachmentPutAndDeleteWillWork(): void
    {
        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                $company = new Company();
                $company->setName("HR");

                $file0Stream = implode(array_map("chr", [1, 2, 3]));
                $session->store($company, "companies/1");
                $session->advanced()->attachments()->store($company, "file0", $file0Stream);
                $session->saveChanges();

                $this->assertCount(1, $session->advanced()->attachments()->getNames($company));

            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $company = $session->load(Company::class, "companies/1");

                $this->assertNotNull($company);
                $this->assertTrue($session->advanced()->isLoaded("companies/1"));
                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
                $this->assertCount(1, $session->advanced()->attachments()->getNames($company));

                $file1Stream = implode(array_map("chr", [1, 2, 3]));
                $session->advanced()->defer(new PutAttachmentCommandData("companies/1", "file1", $file1Stream, null, null));
                $session->saveChanges();

                $this->assertTrue($session->advanced()->isLoaded("companies/1"));
                $this->assertEquals(2, $session->advanced()->getNumberOfRequests());
                $this->assertCount(2, $session->advanced()->attachments()->getNames($company));

                $session->advanced()->defer(new DeleteAttachmentCommandData("companies/1", "file1", null));
                $session->saveChanges();

                $this->assertTrue($session->advanced()->isLoaded("companies/1"));
                $this->assertEquals(3, $session->advanced()->getNumberOfRequests());
                $this->assertCount(1, $session->advanced()->attachments()->getNames($company));
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testAttachmentCopyAndMoveWillWork(): void
    {
        $store = $this->getDocumentStore();
        try {

            $session = $store->openSession();
            try {
                $company1 = new Company();
                $company1->setName("HR");

                $company2 = new Company();
                $company2->setName("HR");

                $session->store($company1, "companies/1");
                $session->store($company2, "companies/2");

                $file1Stream = implode(array_map("chr", [1, 2, 3]));
                $session->advanced()->attachments()->store($company1, "file1", $file1Stream);
                $session->saveChanges();

                $this->assertCount(1, $session->advanced()->attachments()->getNames($company1));
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $company1 = $session->load(Company::class, "companies/1");
                $company2 = $session->load(Company::class, "companies/2");

                $this->assertNotNull($company1);

                $this->assertTrue($session->advanced()->isLoaded("companies/1"));
                $this->assertEquals(2, $session->advanced()->getNumberOfRequests());
                $this->assertCount(1, $session->advanced()->attachments()->getNames($company1));

                $this->assertNotNull($company2);

                $this->assertTrue($session->advanced()->isLoaded("companies/2"));
                $this->assertEquals(2, $session->advanced()->getNumberOfRequests());
                $this->assertCount(0, $session->advanced()->attachments()->getNames($company2));

                $session->advanced()->defer(new CopyAttachmentCommandData("companies/1", "file1", "companies/2", "file1", null));
                $session->saveChanges();

                $this->assertTrue($session->advanced()->isLoaded("companies/1"));
                $this->assertEquals(3, $session->advanced()->getNumberOfRequests());
                $this->assertCount(1, $session->advanced()->attachments()->getNames($company1));

                $this->assertTrue($session->advanced()->isLoaded("companies/2"));
                $this->assertEquals(3, $session->advanced()->getNumberOfRequests());
                $this->assertCount(1, $session->advanced()->attachments()->getNames($company2));

                $session->advanced()->defer(new MoveAttachmentCommandData("companies/1", "file1", "companies/2", "file2", null));
                $session->saveChanges();

                $this->assertTrue($session->advanced()->isLoaded("companies/1"));
                $this->assertEquals(4, $session->advanced()->getNumberOfRequests());
                $this->assertCount(0, $session->advanced()->attachments()->getNames($company1));

                $this->assertTrue($session->advanced()->isLoaded("companies/2"));
                $this->assertEquals(4, $session->advanced()->getNumberOfRequests());
                $this->assertCount(2, $session->advanced()->attachments()->getNames($company2));
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
