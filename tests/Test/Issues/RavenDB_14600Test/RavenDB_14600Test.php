<?php

namespace tests\RavenDB\Test\Issues\RavenDB_14600Test;

use RavenDB\Documents\DocumentStore;
use RavenDB\Documents\Queries\Facets\FacetResult;
use tests\RavenDB\Infrastructure\Orders\Employee;
use tests\RavenDB\Infrastructure\Orders\Order;
use tests\RavenDB\RemoteTestBase;

class RavenDB_14600Test extends RemoteTestBase
{
    protected function customizeStore(DocumentStore &$store): void
    {
        $store->getConventions()->setDisableTopologyUpdates(true);
    }

    public function testCanIncludeFacetResult(): void
    {
        $store = $this->getDocumentStore();
        try {
            $session = $store->openSession();
            try {
                $employee = new Employee();
                $employee->setFirstName("Test");
                $session->store($employee, "employees/1");

                $order = new Order();
                $order->setEmployee("employees/1");
                $order->setCompany("companies/1-A");

                $session->store($order, "orders/1");
                $session->saveChanges();
            } finally {
                $session->close();
            }

            $store->executeIndex(new MyIndex());

            $this->waitForIndexing($store);

            $session = $store->openSession();
            try {
                /** @var array<FacetResult> $facets */
                $facets = $session->query(Order::class, MyIndex::class)
                    ->include("employee")
                    ->whereEquals("company", "companies/1-A")
                    ->aggregateBy(function ($x) {
                        return $x->byField("employee");
                    })
                    ->execute();

                $this->assertNotNull($facets['employee']->getValues());

                foreach ($facets['employee']->getValues() as $f) {
                    $session->load(null, $f->getRange());
                }

                $this->assertEquals(1, $session->advanced()->getNumberOfRequests());
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }
}
