<?php

namespace RavenDB\Documents\Session;

use RavenDB\Documents\Indexes\AbstractCommonApiForIndexes;


interface DocumentQueryBuilderInterface
{
    /**
     * Query the specified index using Lucene syntax
     * @param ?string $className The result of the query
     * @param string|null|AbstractCommonApiForIndexes $indexNameOrClass Name of the index (mutually exclusive with collectionName) or AbstractCommonApiForIndexes class name
     * @param string|null $collectionName Name of the collection (mutually exclusive with indexName)
     * @param bool $isMapReduce Whether we are querying a map/reduce index (modify how we treat identifier properties)
     */
    public function documentQuery(?string $className, $indexNameOrClass = null, ?string $collectionName = null, bool $isMapReduce = false): DocumentQueryInterface;
}
