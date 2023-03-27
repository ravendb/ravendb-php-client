<?php

namespace tests\RavenDB\Test\Client\Spatial\_SpatialTest;

use RavenDB\Documents\Indexes\AbstractIndexCreationTask;
use RavenDB\Documents\Indexes\FieldStorage;

class MyIndex extends AbstractIndexCreationTask
{
    public function __construct()
    {
        parent::__construct();

        $this->map = "docs.MyDocuments.SelectMany(doc => doc.items, (doc, item) => new {\n" .
            "    doc = doc,\n" .
            "    item = item\n" .
            "}).Select(this0 => new {\n" .
            "    this0 = this0,\n" .
            "    lat = ((double)(this0.item.latitude ?? 0))\n" .
            "}).Select(this1 => new {\n" .
            "    this1 = this1,\n" .
            "    lng = ((double)(this1.this0.item.longitude ?? 0))\n" .
            "}).Select(this2 => new {\n" .
            "    id = Id(this2.this1.this0.doc),\n" .
            "    date = this2.this1.this0.item.date,\n" .
            "    latitude = this2.this1.lat,\n" .
            "    longitude = this2.lng,\n" .
            "    coordinates = this.CreateSpatialField(((double ? ) this2.this1.lat), ((double ? ) this2.lng))\n" .
            "})";

        $this->store("id", FieldStorage::yes());
        $this->store("date", FieldStorage::yes());

        $this->store("latitude", FieldStorage::yes());
        $this->store("longitude", FieldStorage::yes());
    }
}
