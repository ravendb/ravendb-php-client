<?php

namespace RavenDB\ServerWide\Operations;

use Closure;
use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Operations\Operation;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\RequestExecutor;

class ServerWideOperation extends Operation
{
    public function __construct(RequestExecutor $requestExecutor, DocumentConventions $conventions, int $id, ?string $nodeTag = null)
    {
        parent::__construct($requestExecutor, $conventions, $id, $nodeTag);
        $this->setNodeTag($nodeTag);
    }

    public function getOperationStateCommand(DocumentConventions $conventions, int $id, ?string $nodeTag = null): RavenCommand
    {
        return new GetServerWideOperationStateCommand($id, $nodeTag);
    }
}
