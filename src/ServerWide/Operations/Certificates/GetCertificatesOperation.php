<?php

namespace RavenDB\ServerWide\Operations\Certificates;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Http\RavenCommand;
use RavenDB\ServerWide\Operations\ServerOperationInterface;

class GetCertificatesOperation implements ServerOperationInterface
{
    private ?int $start = null;
    private ?int $pageSize = null;

    public function __construct(?int $start, ?int $pageSize)
    {
        $this->start = $start;
        $this->pageSize = $pageSize;
    }

    public function getCommand(DocumentConventions $conventions): RavenCommand
    {
        return new GetCertificatesCommand($this->start, $this->pageSize);
    }
}
