<?php

namespace tests\RavenDB\Test\Issues;

use RavenDB\Documents\Commands\Batches\DeleteCommandData;
use RavenDB\Documents\Commands\Batches\PatchCommandData;
use RavenDB\Documents\Operations\PatchRequest;
use tests\RavenDB\Infrastructure\Entity\Company;
use tests\RavenDB\RemoteTestBase;

class RavenDB_11552Test extends RemoteTestBase
{
//    @Test
//    public void patchWillUpdateTrackedDocumentAfterSaveChanges() throws Exception {
//        try (IDocumentStore store = getDocumentStore()) {
//            try (IDocumentSession session = store.openSession()) {
//                Company company = new Company();
//                company.setName("HR");
//                session.store(company, "companies/1");
//
//                session.saveChanges();
//            }
//
//            try (IDocumentSession session = store.openSession()) {
//                Company company = session.load(Company.class, "companies/1");
//                session.advanced().patch(company, "name", "CF");
//
//                String cv = session.advanced().getChangeVectorFor(company);
//                Date lastModified = session.advanced().getLastModifiedFor(company);
//
//                session.saveChanges();
//
//                assertThat(company.getName())
//                        .isEqualTo("CF");
//
//                assertThat(session.advanced().getChangeVectorFor(company))
//                        .isNotEqualTo(cv);
//                assertThat(session.advanced().getLastModifiedFor(company))
//                        .isNotEqualTo(lastModified);
//
//                company.setPhone(123);
//                session.saveChanges();
//            }
//
//            try (IDocumentSession session = store.openSession()) {
//                Company company = session.load(Company.class, "companies/1");
//
//                assertThat(company.getName())
//                        .isEqualTo("CF");
//                assertThat(company.getPhone())
//                        .isEqualTo(123);
//            }
//        }
//    }

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

//                echo 'FIRST: ' . PHP_EOL;
//                print_r($company);
//                echo 'SECOND: ' . PHP_EOL;
//                print_r($company2);
//
                // @todo: Check with Marcing what to do with this behavior
                // !!!! we do not returning object references !!!!
//                 $this->assertEquals($company, $company2);

                $this->assertEquals("HR2", $company2->getName());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
//
//    @Test
//    public void attachmentPutAndDeleteWillWork() throws Exception {
//        try (IDocumentStore store = getDocumentStore()) {
//            try (IDocumentSession session = store.openSession()) {
//                Company company = new Company();
//                company.setName("HR");
//
//                ByteArrayInputStream file0Stream = new ByteArrayInputStream(new byte[]{1, 2, 3});
//                session.store(company, "companies/1");
//                session.advanced().attachments().store(company, "file0", file0Stream);
//                session.saveChanges();
//
//                assertThat(session.advanced().attachments().getNames(company))
//                        .hasSize(1);
//            }
//
//            try (IDocumentSession session = store.openSession()) {
//                Company company = session.load(Company.class, "companies/1");
//
//                assertThat(company)
//                        .isNotNull();
//                assertThat(session.advanced().isLoaded("companies/1"))
//                        .isTrue();
//                assertThat(session.advanced().getNumberOfRequests())
//                        .isEqualTo(1);
//                assertThat(session.advanced().attachments().getNames(company))
//                        .hasSize(1);
//
//
//                ByteArrayInputStream file1Stream = new ByteArrayInputStream(new byte[]{1, 2, 3});
//                session.advanced().defer(new PutAttachmentCommandData("companies/1", "file1", file1Stream, null, null));
//                session.saveChanges();
//
//                assertThat(session.advanced().isLoaded("companies/1"))
//                        .isTrue();
//                assertThat(session.advanced().getNumberOfRequests())
//                        .isEqualTo(2);
//                assertThat(session.advanced().attachments().getNames(company))
//                        .hasSize(2);
//
//                session.advanced().defer(new DeleteAttachmentCommandData("companies/1", "file1", null));
//                session.saveChanges();
//
//                assertThat(session.advanced().isLoaded("companies/1"))
//                        .isTrue();
//                assertThat(session.advanced().getNumberOfRequests())
//                        .isEqualTo(3);
//                assertThat(session.advanced().attachments().getNames(company))
//                        .hasSize(1);
//            }
//        }
//    }
//
//    @Test
//    public void attachmentCopyAndMoveWillWork() throws Exception {
//        try (IDocumentStore store = getDocumentStore()) {
//            try (IDocumentSession session = store.openSession()) {
//                Company company1 = new Company();
//                company1.setName("HR");
//
//                Company company2 = new Company();
//                company2.setName("HR");
//
//                session.store(company1, "companies/1");
//                session.store(company2, "companies/2");
//
//                ByteArrayInputStream file1Stream = new ByteArrayInputStream(new byte[]{1, 2, 3});
//                session.advanced().attachments().store(company1, "file1", file1Stream);
//                session.saveChanges();
//
//                assertThat(session.advanced().attachments().getNames(company1))
//                        .hasSize(1);
//            }
//
//            try (IDocumentSession session = store.openSession()) {
//                Company company1 = session.load(Company.class, "companies/1");
//                Company company2 = session.load(Company.class, "companies/2");
//
//                assertThat(company1)
//                        .isNotNull();
//
//                assertThat(session.advanced().isLoaded("companies/1"))
//                        .isTrue();
//                assertThat(session.advanced().getNumberOfRequests())
//                        .isEqualTo(2);
//                assertThat(session.advanced().attachments().getNames(company1))
//                        .hasSize(1);
//
//                assertThat(company2)
//                        .isNotNull();
//
//                assertThat(session.advanced().isLoaded("companies/2"))
//                        .isTrue();
//                assertThat(session.advanced().getNumberOfRequests())
//                        .isEqualTo(2);
//                assertThat(session.advanced().attachments().getNames(company2))
//                        .hasSize(0);
//
//                session.advanced().defer(new CopyAttachmentCommandData("companies/1", "file1", "companies/2", "file1", null));
//                session.saveChanges();
//
//                assertThat(session.advanced().isLoaded("companies/1"))
//                        .isTrue();
//                assertThat(session.advanced().getNumberOfRequests())
//                        .isEqualTo(3);
//                assertThat(session.advanced().attachments().getNames(company1))
//                        .hasSize(1);
//
//                assertThat(session.advanced().isLoaded("companies/2"))
//                        .isTrue();
//                assertThat(session.advanced().getNumberOfRequests())
//                        .isEqualTo(3);
//                assertThat(session.advanced().attachments().getNames(company2))
//                        .hasSize(1);
//
//                session.advanced().defer(new MoveAttachmentCommandData("companies/1", "file1", "companies/2", "file2", null));
//                session.saveChanges();
//
//                assertThat(session.advanced().isLoaded("companies/1"))
//                        .isTrue();
//                assertThat(session.advanced().getNumberOfRequests())
//                        .isEqualTo(4);
//                assertThat(session.advanced().attachments().getNames(company1))
//                        .hasSize(0);
//
//                assertThat(session.advanced().isLoaded("companies/2"))
//                        .isTrue();
//                assertThat(session.advanced().getNumberOfRequests())
//                        .isEqualTo(4);
//                assertThat(session.advanced().attachments().getNames(company2))
//                        .hasSize(2);
//            }
//        }
//    }
}
