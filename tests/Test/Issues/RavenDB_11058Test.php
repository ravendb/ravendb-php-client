<?php

namespace tests\RavenDB\Test\Issues;

use RavenDB\Documents\Operations\DatabaseStatistics;
use RavenDB\Documents\Operations\GetStatisticsOperation;
use RavenDB\Exceptions\ConcurrencyException;
use tests\RavenDB\Infrastructure\Entity\Company;
use tests\RavenDB\RemoteTestBase;

class RavenDB_11058Test extends RemoteTestBase
{
    public function testCanCopyAttachment(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $company = new Company();
                $company->setName("HR");

                $session->store($company, "companies/1");

                $bais1 = implode(array_map("chr", [1, 2, 3]));
                $session->advanced()->attachments()->store($company, "file1", $bais1);

                $bais2 = implode(array_map("chr", [3, 2, 1]));
                $session->advanced()->attachments()->store($company, "file10", $bais2);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $stats = $store->maintenance()->send(new GetStatisticsOperation());
            $this->assertEquals(2, $stats->getCountOfAttachments());
            $this->assertEquals(2, $stats->getCountOfUniqueAttachments());

            $session = $store->openSession();
            try {
                $newCompany = new Company();
                $newCompany->setName("CF");

                $session->store($newCompany, "companies/2");

                $oldCompany = $session->load(Company::class, "companies/1");
                $session->advanced()->attachments()->copy($oldCompany, "file1", $newCompany, "file2");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            /** @var DatabaseStatistics $stats */
            $stats = $store->maintenance()->send(new GetStatisticsOperation());
            $this->assertEquals(3, $stats->getCountOfAttachments());
            $this->assertEquals(2, $stats->getCountOfUniqueAttachments());

            $session = $store->openSession();
            try {
                $this->assertTrue($session->advanced()->attachments()->exists("companies/1", "file1"));
                $this->assertFalse($session->advanced()->attachments()->exists("companies/1", "file2"));
                $this->assertTrue($session->advanced()->attachments()->exists("companies/1", "file10"));

                $this->assertFalse($session->advanced()->attachments()->exists("companies/2", "file1"));
                $this->assertTrue($session->advanced()->attachments()->exists("companies/2", "file2"));
                $this->assertFalse($session->advanced()->attachments()->exists("companies/2", "file10"));
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $session->advanced()->attachments()->copy("companies/1", "file1", "companies/2", "file3");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            /** @var DatabaseStatistics $stats */
            $stats = $store->maintenance()->send(new GetStatisticsOperation());
            $this->assertEquals(4, $stats->getCountOfAttachments());
            $this->assertEquals(2, $stats->getCountOfUniqueAttachments());

            $session = $store->openSession();
            try {
                $this->assertTrue($session->advanced()->attachments()->exists("companies/1", "file1"));
                $this->assertFalse($session->advanced()->attachments()->exists("companies/1", "file2"));
                $this->assertFalse($session->advanced()->attachments()->exists("companies/1", "file3"));
                $this->assertTrue($session->advanced()->attachments()->exists("companies/1", "file10"));

                $this->assertFalse($session->advanced()->attachments()->exists("companies/2", "file1"));
                $this->assertTrue($session->advanced()->attachments()->exists("companies/2", "file2"));
                $this->assertTrue($session->advanced()->attachments()->exists("companies/2", "file3"));
                $this->assertFalse($session->advanced()->attachments()->exists("companies/2", "file10"));
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $session->advanced()->attachments()->copy("companies/1", "file1", "companies/2", "file3"); //should throw

                $this->expectException(ConcurrencyException::class);
                $session->saveChanges();
            } finally {
                $session->close();
            }

        } finally {
            $store->close();
        }
    }

    public function testCanMoveAttachment(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $company = new Company();
                $company->setName("HR");

                $session->store($company, "companies/1");

                $bais1 = implode(array_map("chr", [1, 2, 3]));
                $session->advanced()->attachments()->store($company, "file1", $bais1);

                $bais2 = implode(array_map("chr", [3, 2, 1]));
                $session->advanced()->attachments()->store($company, "file10", $bais2);

                $bais3 = implode(array_map("chr", [4, 5, 6]));
                $session->advanced()->attachments()->store($company, "file20", $bais3);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            /** @var DatabaseStatistics $stats */
            $stats = $store->maintenance()->send(new GetStatisticsOperation());
            $this->assertEquals(3, $stats->getCountOfAttachments());
            $this->assertEquals(3, $stats->getCountOfUniqueAttachments());

            $session = $store->openSession();
            try {
                $newCompany = new Company();
                $newCompany->setName("CF");

                $session->store($newCompany, "companies/2");

                $oldCompany = $session->load(Company::class, "companies/1");

                $session->advanced()->attachments()->move($oldCompany, "file1", $newCompany, "file2");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            /** @var DatabaseStatistics $stats */
            $stats = $store->maintenance()->send(new GetStatisticsOperation());
            $this->assertEquals(3, $stats->getCountOfAttachments());
            $this->assertEquals(3, $stats->getCountOfUniqueAttachments());

            $session = $store->openSession();
            try {
                $this->assertFalse($session->advanced()->attachments()->exists("companies/1", "file1"));
                $this->assertFalse($session->advanced()->attachments()->exists("companies/1", "file2"));
                $this->assertTrue($session->advanced()->attachments()->exists("companies/1", "file10"));
                $this->assertTrue($session->advanced()->attachments()->exists("companies/1", "file20"));

                $this->assertFalse($session->advanced()->attachments()->exists("companies/2", "file1"));
                $this->assertTrue($session->advanced()->attachments()->exists("companies/2", "file2"));
                $this->assertFalse($session->advanced()->attachments()->exists("companies/2", "file10"));
                $this->assertFalse($session->advanced()->attachments()->exists("companies/2", "file20"));
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $session->advanced()->attachments()->move("companies/1", "file10", "companies/2", "file3");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            /** @var DatabaseStatistics $stats */
            $stats = $store->maintenance()->send(new GetStatisticsOperation());
            $this->assertEquals(3, $stats->getCountOfAttachments());
            $this->assertEquals(3, $stats->getCountOfUniqueAttachments());

            $session = $store->openSession();
            try {
                $this->assertFalse($session->advanced()->attachments()->exists("companies/1", "file1"));
                $this->assertFalse($session->advanced()->attachments()->exists("companies/1", "file2"));
                $this->assertFalse($session->advanced()->attachments()->exists("companies/1", "file3"));
                $this->assertFalse($session->advanced()->attachments()->exists("companies/1", "file10"));
                $this->assertTrue($session->advanced()->attachments()->exists("companies/1", "file20"));


                $this->assertFalse($session->advanced()->attachments()->exists("companies/2", "file1"));
                $this->assertTrue($session->advanced()->attachments()->exists("companies/2", "file2"));
                $this->assertTrue($session->advanced()->attachments()->exists("companies/2", "file3"));
                $this->assertFalse($session->advanced()->attachments()->exists("companies/2", "file10"));
                $this->assertFalse($session->advanced()->attachments()->exists("companies/2", "file20"));
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $session->advanced()->attachments()->move("companies/1", "file20", "companies/2", "file3"); //should throw

                $this->expectException(ConcurrencyException::class);
                $session->saveChanges();
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanRenameAttachment(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $company = new Company();
                $company->setName("HR");

                $session->store($company, "companies/1-A");

                $bais1 = implode(array_map("chr", [1, 2, 3]));
                $session->advanced()->attachments()->store($company, "file1", $bais1);

                $bais2 = implode(array_map("chr", [3, 2, 1]));
                $session->advanced()->attachments()->store($company, "file10", $bais2);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            /** @var DatabaseStatistics $stats */
            $stats = $store->maintenance()->send(new GetStatisticsOperation());
            $this->assertEquals(2, $stats->getCountOfAttachments());
            $this->assertEquals(2, $stats->getCountOfUniqueAttachments());

            $session = $store->openSession();
            try {
                $company = $session->load(Company::class, "companies/1-A");
                $session->advanced()->attachments()->rename($company, "file1", "file2");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            /** @var DatabaseStatistics $stats */
            $stats = $store->maintenance()->send(new GetStatisticsOperation());
            $this->assertEquals(2, $stats->getCountOfAttachments());
            $this->assertEquals(2, $stats->getCountOfUniqueAttachments());

            $session = $store->openSession();
            try {
                $this->assertFalse($session->advanced()->attachments()->exists("companies/1-A", "file1"));
                $this->assertTrue($session->advanced()->attachments()->exists("companies/1-A", "file2"));
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $company = $session->load(Company::class, "companies/1-A");
                $session->advanced()->attachments()->rename($company, "file2", "file3");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            /** @var DatabaseStatistics $stats */
            $stats = $store->maintenance()->send(new GetStatisticsOperation());
            $this->assertEquals(2, $stats->getCountOfAttachments());
            $this->assertEquals(2, $stats->getCountOfUniqueAttachments());

            $session = $store->openSession();
            try {
                $this->assertFalse($session->advanced()->attachments()->exists("companies/1-A", "file2"));
                $this->assertTrue($session->advanced()->attachments()->exists("companies/1-A", "file3"));
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $company = $session->load(Company::class, "companies/1-A");
                $session->advanced()->attachments()->rename($company, "file3", "file10"); // should throw
                $this->expectException(ConcurrencyException::class);
                $session->saveChanges();
            } finally {
                $session->close();
            }

            /** @var DatabaseStatistics $stats */
            $stats = $store->maintenance()->send(new GetStatisticsOperation());
            $this->assertEquals(2, $stats->getCountOfAttachments());
            $this->assertEquals(2, $stats->getCountOfUniqueAttachments());
        } finally {
            $store->close();
        }
    }
}
