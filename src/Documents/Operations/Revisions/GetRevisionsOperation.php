<?php

namespace RavenDB\Documents\Operations\Revisions;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\DocumentStoreInterface;
use RavenDB\Documents\Operations\OperationInterface;
use RavenDB\Http\HttpCache;
use RavenDB\Http\RavenCommand;

class GetRevisionsOperation implements OperationInterface
{
    private ?string $className = null;
    private ?GetRevisionsOperationParameters $parameters = null;

    public function __construct(?string $className, null|string|GetRevisionsOperationParameters $idOrParameters, ?int $start = null, ?int $pageSize = null)
    {
        if (!is_object($idOrParameters)) {
            $parameters = new GetRevisionsOperationParameters();
            $parameters->setId($idOrParameters);
            $parameters->setStart($start);
            $parameters->setPageSize($pageSize);
        } else {
            $parameters = $idOrParameters;
            $parameters->validate();
        }

        $this->className = $className;
        $this->parameters = $parameters;
    }

    public function getCommand(?DocumentStoreInterface $store, ?DocumentConventions $conventions, ?HttpCache $cache, bool $returnDebugInformation = false, bool $test = false): RavenCommand
    {
        return new GetRevisionsResultCommand($this->className, $this->parameters->getId(), $this->parameters->getStart(), $this->parameters->getPageSize(), $store->getConventions()->getEntityMapper());
    }
}
