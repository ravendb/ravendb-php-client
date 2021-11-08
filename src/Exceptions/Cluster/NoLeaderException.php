<?php

namespace RavenDB\Exceptions\Cluster;

use RavenDB\Exceptions\RavenException;

class NoLeaderException extends RavenException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
