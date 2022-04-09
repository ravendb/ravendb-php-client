<?php

namespace RavenDB\Documents\Queries;

// !status: DONE
class Query
{
    private string $collection;
    private string $indexName;

    private function __construct()
    {
    }

    public function getCollection(): string
    {
        return $this->collection;
    }

    public function getIndexName(): string
    {
        return $this->indexName;
    }

    public static function index(string $indexName): Query
    {
        $query = new Query();
        $query->indexName = $indexName;
        return $query;
    }

    public static function collection(string $collectionName): Query
    {
        $query = new Query();
        $query->collection = $collectionName;
        return $query;
    }
}
