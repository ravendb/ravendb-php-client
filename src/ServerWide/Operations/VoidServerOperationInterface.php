<?php

namespace RavenDB\ServerWide\Operations;

use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Http\VoidRavenCommand;

interface VoidServerOperationInterface extends ServerOperationInterface
{
    public function getCommand(DocumentConventions $conventions): VoidRavenCommand;
}
