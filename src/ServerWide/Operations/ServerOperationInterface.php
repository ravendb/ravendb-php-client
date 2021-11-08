<?php

namespace RavenDB\ServerWide\Operations;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Http\RavenCommand;

interface ServerOperationInterface
{
    public function getCommand(DocumentConventions $conventions): RavenCommand;
}
