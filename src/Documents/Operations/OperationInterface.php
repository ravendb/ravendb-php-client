<?php

namespace RavenDB\Documents\Operations;

use RavenDB\Http\HttpCache;
use RavenDB\Http\RavenCommand;
use RavenDB\Documents\DocumentStoreInterface;
use RavenDB\Documents\Conventions\DocumentConventions;

interface OperationInterface
{
    public function getCommand(
        ?DocumentStoreInterface $store,
        ?DocumentConventions $conventions,
        ?HttpCache $cache,
        bool $returnDebugInformation = false,
        bool $test = false
    ): RavenCommand;
}
