<?php

namespace RavenDB\Documents\Operations;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\DocumentStoreInterface;
use RavenDB\Documents\Queries\IndexQuery;
use RavenDB\Documents\Queries\QueryOperationOptions;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\HttpCache;
use RavenDB\Http\RavenCommand;

class PatchByQueryOperation implements OperationInterface
{
    private static ?IndexQuery $DUMMY_QUERY = null;

    protected static function getDummyQuery(): IndexQuery
    {
        if (self::$DUMMY_QUERY == null) {
            self::$DUMMY_QUERY = new IndexQuery();
        }
        return self::$DUMMY_QUERY;
    }
    private ?IndexQuery $queryToUpdate = null;
    private ?QueryOperationOptions $options = null;

    /**
     * @param null|IndexQuery|string $queryToUpdate
     * @param QueryOperationOptions|null $options
     */
    public function __construct(null|IndexQuery|string $queryToUpdate, ?QueryOperationOptions $options = null)
    {
        if ($queryToUpdate == null) {
            throw new IllegalArgumentException("QueryToUpdate cannot be null");
        }

        if (is_string($queryToUpdate)) {
            $queryToUpdate = new IndexQuery($queryToUpdate);
        }

        $this->queryToUpdate = $queryToUpdate;
        $this->options = $options;
    }

    public function getCommand(?DocumentStoreInterface $store, ?DocumentConventions $conventions, ?HttpCache $cache, bool $returnDebugInformation = false, bool $test = false): RavenCommand
    {
        return new PatchByQueryCommand($conventions, $this->queryToUpdate, $this->options);
    }
}
