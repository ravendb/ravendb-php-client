<?php

namespace RavenDB\Http;

use RavenDB\Documents\Conventions\DocumentConventions;

interface BroadcastInterface
{
    function prepareToBroadcast(DocumentConventions $conventions): BroadcastInterface;
}
