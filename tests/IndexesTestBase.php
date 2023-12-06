<?php

namespace tests\RavenDB;

use RavenDB\Documents\DocumentStoreInterface;
use RavenDB\Documents\Indexes\IndexErrorsArray;
use RavenDB\Type\Duration;

class IndexesTestBase
{
    private ?RemoteTestBase $parent = null;

    public function __construct(?RemoteTestBase $parent = null) {
        $this->parent = $parent;
    }

    public function waitForIndexing(DocumentStoreInterface $store, ?string $database = null, ?Duration $timeout = null, ?string $nodeTag = null) {
        RemoteTestBase::waitForIndexing($store, $database, $timeout, $nodeTag);
    }

    public function waitForIndexingErrors(?DocumentStoreInterface $store, ?Duration $timeout, string ...$indexNames): IndexErrorsArray
    {
        return RemoteTestBase::waitForIndexingErrors($store, $timeout, ...$indexNames);
    }
}
