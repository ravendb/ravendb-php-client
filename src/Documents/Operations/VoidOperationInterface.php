<?php

namespace RavenDB\Documents\Operations;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\DocumentStoreInterface;
use RavenDB\Http\HttpCache;
use RavenDB\Http\VoidRavenCommand;

interface VoidOperationInterface extends OperationInterface
{
    function getCommand(?DocumentStoreInterface $store, ?DocumentConventions $conventions, ?HttpCache $cache, bool $returnDebugInformation = false, bool $test = false): VoidRavenCommand;
}
