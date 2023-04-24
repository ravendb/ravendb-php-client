<?php

namespace RavenDB\Documents\Session\Operations\Lazy;

use RavenDB\Documents\Commands\MultiGet\ContentInterface;
use RavenDB\Documents\Conventions\DocumentConventions;
use RavenDB\Documents\Queries\IndexQuery;
use RavenDB\Extensions\JsonExtensions;

class IndexQueryContent implements ContentInterface
{
    private ?DocumentConventions $conventions = null;
    private ?IndexQuery $query = null;

    public function __construct(?DocumentConventions $conventions, ?IndexQuery $query) {
        $this->conventions = $conventions;
        $this->query = $query;
    }

    public function writeContent(): array
    {
        return JsonExtensions::writeIndexQuery($this->conventions, $this->query);
    }
}
