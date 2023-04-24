<?php

namespace RavenDB\Documents\Session\Operations\Lazy;

use RavenDB\Documents\Commands\MultiGet\GetRequest;
use RavenDB\Documents\Commands\MultiGet\GetResponse;
use RavenDB\Documents\Queries\QueryResult;

interface LazyOperationInterface
{
    function createRequest(): GetRequest;

    function getResult(): ?object;

    function getQueryResult(): ?QueryResult;

    function isRequiresRetry(): bool;

    function handleResponse(GetResponse $response): void;
}
