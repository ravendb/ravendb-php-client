<?php

namespace tests\RavenDB\Test\_FacetTestBase;

use DateTime;
use RavenDB\Documents\DocumentStoreInterface;
use RavenDB\Documents\Operations\Indexes\PutIndexesOperation;
use RavenDB\Documents\Queries\Facets\Facet;
use RavenDB\Documents\Queries\Facets\FacetBase;
use RavenDB\Documents\Queries\Facets\RangeFacet;
use RavenDB\Utils\DateUtils;
use tests\RavenDB\RemoteTestBase;

class FacetTestBase extends RemoteTestBase
{
    public function __construct()
    {
        parent::__construct();

    }

    public static function createCameraCostIndex(DocumentStoreInterface $store): void
    {
        $index = new CameraCostIndex();
        $store->maintenance()->send(new PutIndexesOperation($index->createIndexDefinition()));
    }

    protected function insertCameraData(DocumentStoreInterface $store, CameraList $cameras, bool $waitForIndexing): void
    {
        $session = $store->openSession();
        try {
            foreach ($cameras as $camera) {
                $session->store($camera);
            }

            $session->saveChanges();
        } finally {
            $session->close();
        }

        if ($waitForIndexing) {
            FacetTestBase::waitForIndexing($store);
        }
    }

    /**
     * @return array<FacetBase>
     */
    public static function getFacets(): array
    {
        $facet1 = new Facet();
        $facet1->setFieldName("manufacturer");

        $costRangeFacet = new RangeFacet();
        $costRangeFacet->setRanges([
                "cost <= 200",
                "cost >= 200 and cost <= 400",
                "cost >= 400 and cost <= 600",
                "cost >= 600 and cost <= 800",
                "cost >= 800"
        ]);
        $megaPixelsRangeFacet = new RangeFacet();
        $megaPixelsRangeFacet->setRanges([
                "megapixels <= 3",
                "megapixels >= 3 and megapixels <= 7",
                "megapixels >= 7 and megapixels <= 10",
                "megapixels >= 10"
        ]);

        return [$facet1, $costRangeFacet, $megaPixelsRangeFacet];
    }

    private static array $FEATURES = ["Image Stabilizer", "Tripod", "Low Light Compatible", "Fixed Lens", "LCD"];

    private static array $MANUFACTURERS = ["Sony", "Nikon", "Phillips", "Canon", "Jessops"];

    private static array $MODELS = ["Model1", "Model2", "Model3", "Model4", "Model5"];

    protected static function getCameras(int $numCameras): CameraList
    {
        $cameraList = new CameraList();
        for ($i = 1; $i <= $numCameras; $i++) {
            $camera = new Camera();
            $camera->setDateOfListing(DateUtils::now()->setDate(80 + rand(1, 30), rand(1, 12), rand(1, 27)));
            $camera->setManufacturer(self::$MANUFACTURERS[rand(0, count(self::$MANUFACTURERS)-1)]);
            $camera->setModel(self::$MODELS[rand(1, count(self::$MODELS)-1)]);
            $camera->setCost(self::randFloat() * 900 + 100);
            $camera->setZoom((int)(self::randFloat() * 10 + 1.0));
            $camera->setMegapixels(self::randFloat() * 10 + 1.0);
            $camera->setImageStabilizer(self::randFloat() > 0.6);
            $camera->setAdvancedFeatures(["??"]);

            $cameraList[] = $camera;
        }

        return $cameraList;
    }

    private static function randFloat(): float
    {
        return (float)rand() / (float)getrandmax();
    }
}
