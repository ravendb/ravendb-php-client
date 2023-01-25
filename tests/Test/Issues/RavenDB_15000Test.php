<?php

namespace tests\RavenDB\Test\Issues;

use DateTime;
use tests\RavenDB\Infrastructure\Entity\Company;
use tests\RavenDB\Infrastructure\Entity\Order;
use tests\RavenDB\RemoteTestBase;

class RavenDB_15000Test extends RemoteTestBase
{
    public function testCanIncludeTimeSeriesWithoutProvidingFromAndToDates_ViaLoad(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $order = new Order();
                $order->setCompany("companies/1-A");
                $session->store($order, "orders/1-A");

                $company = new Company();
                $company->setName("HR");
                $session->store($company, "companies/1-A");

                $session->timeSeriesFor("orders/1-A", "Heartrate")
                    ->append(new DateTime(), 1);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $order = $session->load(Order::class, "orders/1-A",
                    function ($i) {
                        $i->includeDocuments("company")->includeTimeSeries("Heartrate");
                    });

                // should not go to server
                $company = $session->load(Company::class, $order->getCompany());

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
                $this->assertEquals("HR", $company->getName());

                // should not go to server
                $vals = $session->timeSeriesFor($order, "Heartrate")
                    ->get();

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
                $this->assertCount(1, $vals);
            } finally {
                $session->close();
            }

        } finally {
            $store->close();
        }
    }

    public function testCanIncludeTimeSeriesWithoutProvidingFromAndToDates_ViaQuery(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $order = new Order();
                $order->setCompany("companies/1-A");
                $session->store($order, "orders/1-A");

                $company = new Company();
                $company->setName("HR");
                $session->store($company, "companies/1-A");

                $session->timeSeriesFor("orders/1-A", "Heartrate")
                        ->append(new DateTime(), 1);

                $session->saveChanges();
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $order = $session->query(Order::class)
                        ->include(function($i) { $i->includeDocuments("company")->includeTimeSeries("Heartrate");})
                        ->first();

                // should not go to server
                $company = $session->load(Company::class, $order->getCompany());

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
                $this->assertEquals("HR", $company->getName());

                // should not go to server
                $vals = $session->timeSeriesFor($order, "Heartrate")
                        ->get();

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
                $this->assertCount(1, $vals);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
