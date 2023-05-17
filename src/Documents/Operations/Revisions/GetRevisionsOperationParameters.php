<?php

namespace RavenDB\Documents\Operations\Revisions;

use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Utils\StringUtils;

class GetRevisionsOperationParameters
{
    private ?string $id = null;
    private ?int $start = null;
    private ?int $pageSize = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getStart(): ?int
    {
        return $this->start;
    }

    public function setStart(?int $start): void
    {
        $this->start = $start;
    }

    public function getPageSize(): ?int
    {
        return $this->pageSize;
    }

    public function setPageSize(?int $pageSize): void
    {
        $this->pageSize = $pageSize;
    }

    function validate(): void
    {
        if (StringUtils::isEmpty($this->id)) {
            throw new IllegalArgumentException("Id cannot be null");
        }
    }
}
