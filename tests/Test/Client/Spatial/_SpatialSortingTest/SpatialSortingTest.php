<?php

namespace tests\RavenDB\Test\Client\Spatial\_SpatialSortingTest;

use RavenDB\Documents\DocumentStoreInterface;
use RavenDB\Documents\Indexes\FieldIndexing;
use RavenDB\Documents\Indexes\IndexDefinition;
use RavenDB\Documents\Indexes\IndexFieldOptions;
use RavenDB\Documents\Operations\Indexes\PutIndexesOperation;
use RavenDB\Documents\Queries\Query;
use tests\RavenDB\RemoteTestBase;

class SpatialSortingTest extends RemoteTestBase
{
    private const FILTERED_LAT = 44.419575;
    private const FILTERED_LNG = 34.042618;
    private const SORTED_LAT = 44.417398;
    private const SORTED_LNG = 34.042575;
    private const FILTERED_RADIUS = 100;

    private array $shops = [];

    public function setUp(): void
    {
        parent::setUp();
        $this->shops = [
            new Shop(44.420678, 34.042490),
            new Shop(44.419712, 34.042232),
            new Shop(44.418686, 34.043219)
        ];
    }

    //shop/1:0.36KM, shop/2:0.26KM, shop/3 0.15KM from (34.042575,  44.417398)
    private static array $sortedExpectedOrder = [ "shops/3-A", "shops/2-A", "shops/1-A" ];

    //shop/1:0.12KM, shop/2:0.03KM, shop/3 0.11KM from (34.042618,  44.419575)
    private static array $filteredExpectedOrder = [ "shops/2-A", "shops/3-A", "shops/1-A" ];

    public function createData(DocumentStoreInterface $store): void
    {
        $indexDefinition = new IndexDefinition();
        $indexDefinition->setName("eventsByLatLng");
        $indexDefinition->setMaps(["from e in docs.Shops select new { e.venue, coordinates = CreateSpatialField(e.latitude, e.longitude) }"]);

        $fields = [];
        $options = new IndexFieldOptions();
        $options->setIndexing(FieldIndexing::exact());
        $fields["tag"] = $options;
        $indexDefinition->setFields($fields);

        $store->maintenance()->send(new PutIndexesOperation($indexDefinition));

        $indexDefinition2 = new IndexDefinition();
        $indexDefinition2->setName("eventsByLatLngWSpecialField");
        $indexDefinition2->setMaps(["from e in docs.Shops select new { e.venue, mySpacialField = CreateSpatialField(e.latitude, e.longitude) }"]);

        $indexFieldOptions = new IndexFieldOptions();
        $indexFieldOptions->setIndexing(FieldIndexing::exact());
        $indexDefinition2->setFields(["tag" => $indexFieldOptions]);

        $store->maintenance()->send(new PutIndexesOperation($indexDefinition2));

        $session = $store->openSession();
        try {
            foreach ($this->shops as $shop) {
                $session->store($shop);
            }
            $session->saveChanges();
        } finally {
            $session->close();
        }

        $this->waitForIndexing($store);
    }

    private static function assertResultsOrder(array $resultIDs, array $expectedOrder): void
    {
        self::assertEquals(count($expectedOrder), count($resultIDs));
    }


    public function testCanFilterByLocationAndSortByDistanceFromDifferentPointWDocQuery(): void
    {
        $store = $this->getDocumentStore();
        try {
            $this->createData($store);

            $session = $store->openSession();
            try {
                $test = $this;

                $shops = $session->query(Shop::class, Query::index("eventsByLatLng"))
                        ->spatial("coordinates", function($f) use ($test) { return $f->within($test->getQueryShapeFromLatLon($test::FILTERED_LAT, $test::FILTERED_LNG, $test::FILTERED_RADIUS)); })
                        ->orderByDistance("coordinates", self::SORTED_LAT, self::SORTED_LNG)
                        ->toList();


                $this->assertResultsOrder(array_map(function($x) { return $x->getId();}, $shops), self::$sortedExpectedOrder);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanSortByDistanceWOFilteringWDocQuery(): void
    {
        $store = $this->getDocumentStore();
        try {
            $this->createData($store);

            $session = $store->openSession();
            try {
                $shops = $session->query(Shop::class, Query::index("eventsByLatLng"))
                        ->orderByDistance("coordinates", self::SORTED_LAT, self::SORTED_LNG)
                        ->toList();

                $this->assertResultsOrder(array_map(function($x) { return $x->getId();}, $shops), self::$sortedExpectedOrder);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanSortByDistanceWOFilteringWDocQueryBySpecifiedField(): void
    {
        $store = $this->getDocumentStore();
        try {
            $this->createData($store);

            $session = $store->openSession();
            try {
                $shops = $session->query(Shop::class, Query::index("eventsByLatLngWSpecialField"))
                        ->orderByDistance("mySpacialField", self::SORTED_LAT, self::SORTED_LNG)
                        ->toList();

                $this->assertResultsOrder(array_map(function($x) { return $x->getId();}, $shops), self::$sortedExpectedOrder);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanSortByDistanceWOFiltering(): void
    {
        $store = $this->getDocumentStore();
        try {
            $this->createData($store);

            $session = $store->openSession();
            try {
                $shops = $session->query(Shop::class, Query::index("eventsByLatLng"))
                        ->orderByDistance("coordinates", self::FILTERED_LAT, self::FILTERED_LNG)
                        ->toList();

                $this->assertResultsOrder(array_map(function($x) { return $x->getId();}, $shops), self::$filteredExpectedOrder);
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $shops = $session->query(Shop::class, Query::index("eventsByLatLng"))
                        ->orderByDistanceDescending("coordinates", self::FILTERED_LAT, self::FILTERED_LNG)
                        ->toList();

                $strings = array_reverse(array_map(function($x) { return $x->getId();}, $shops));
                $this->assertResultsOrder($strings, self::$filteredExpectedOrder);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    public function testCanSortByDistanceWOFilteringBySpecifiedField(): void
    {
        $store = $this->getDocumentStore();
        try {
            $this->createData($store);

            $session = $store->openSession();
            try {
                $shops = $session->query(Shop::class, Query::index("eventsByLatLngWSpecialField"))
                        ->orderByDistance("mySpacialField", self::FILTERED_LAT, self::FILTERED_LNG)
                        ->toList();

                $this->assertResultsOrder(array_map(function($x) { return $x->getId();}, $shops), self::$filteredExpectedOrder);
            } finally {
                $session->close();
            }

            $session = $store->openSession();
            try {
                $shops = $session->query(Shop::class, Query::index("eventsByLatLngWSpecialField"))
                        ->orderByDistanceDescending("mySpacialField", self::FILTERED_LAT, self::FILTERED_LNG)
                        ->toList();

                $strings = array_reverse(array_map(function($x) { return $x->getId();}, $shops));
                $this->assertResultsOrder($strings, self::$filteredExpectedOrder);
            } finally {
                $session->close();
            }
        } finally {
            $store->close();
        }
    }

    private static function getQueryShapeFromLatLon(float $lat, float $lng, float $radius): string
    {
        return "Circle(" . $lng . " " . $lat . " d=" . $radius . ")";
    }
}
