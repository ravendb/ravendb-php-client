<?php

namespace RavenDB\Documents\Session\Tokens;

use RavenDB\Exceptions\IllegalStateException;
use RavenDB\Extensions\StringExtensions;
use RavenDB\Utils\StringBuilder;

class FromToken extends QueryToken
{
    private ?string $collectionName;
    private ?string $indexName;
    private bool $dynamic = false;
    private ?string $alias;

    public function getCollectionName(): ?string
    {
        return $this->collectionName;
    }

    public function getIndexName(): ?string
    {
        return $this->indexName;
    }

    public function isDynamic(): bool
    {
        return $this->dynamic;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    private function __construct(?string $indexName, ?string $collectionName, ?string $alias = null) {
        $this->collectionName = $collectionName;
        $this->indexName = $indexName;
        $this->dynamic = $collectionName != null;
        $this->alias = $alias;
    }

    public static function create(?string $indexName, ?string $collectionName, ?string $alias): FromToken
    {
        return new FromToken($indexName, $collectionName, $alias);
    }

    private array $WHITE_SPACE_CHARS = [' ', '\t', '\r', '\n'];

    public function writeTo(StringBuilder &$writer): void
    {
        if ($this->indexName == null && $this->collectionName == null) {
            throw new IllegalStateException("Either indexName or collectionName must be specified");
        }

        if ($this->dynamic) {
            $writer->append("from '");
            StringExtensions::escapeString($writer, $this->collectionName);
            $writer->append("'");
        } else {
            $writer
                    ->append("from index '")
                    ->append($this->indexName)
                    ->append("'");
        }

        if ($this->alias != null) {
            $writer
                ->append(" as ")
                ->append($this->alias);
        }
    }
}
