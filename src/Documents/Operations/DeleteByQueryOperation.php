<?php

namespace RavenDB\Documents\Operations;

use RavenDB\Http\HttpCache;
use RavenDB\Http\RavenCommand;
use RavenDB\Documents\Queries\IndexQuery;
use RavenDB\Documents\DocumentStoreInterface;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Documents\Queries\QueryOperationOptions;
use RavenDB\Documents\Conventions\DocumentConventions;

class DeleteByQueryOperation implements OperationInterface
{
    protected ?IndexQuery $queryToDelete = null;
    private ?QueryOperationOptions $options = null;

    /**
     * @param null|string|IndexQuery $queryToDelete
     * @param QueryOperationOptions|null $options
     */
    public function __construct($queryToDelete, ?QueryOperationOptions $options = null)
    {
        if ($queryToDelete == null) {
            throw new IllegalArgumentException('QueryToDelete cannot be null');
        }

        if (is_string($queryToDelete)) {
            $queryToDelete = new IndexQuery($queryToDelete);
        }

        $this->queryToDelete = $queryToDelete;
        $this->options = $options;
    }

    public function getCommand(?DocumentStoreInterface $store, ?DocumentConventions $conventions, ?HttpCache $cache, bool $returnDebugInformation = false, bool $test = false): RavenCommand
    {
        return new DeleteByIndexCommand($conventions, $this->queryToDelete, $this->options);
    }
}
