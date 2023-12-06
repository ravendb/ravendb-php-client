<?php

namespace RavenDB\Documents\Commands;

use DateTime;
use RavenDB\Exceptions\IllegalArgumentException;
use RavenDB\Http\HttpRequest;
use RavenDB\Http\HttpRequestInterface;
use RavenDB\Http\RavenCommand;
use RavenDB\Http\ServerNode;
use RavenDB\Primitives\NetISO8601Utils;
use RavenDB\Type\StringArray;
use RavenDB\Utils\StringBuilder;
use RavenDB\Utils\UrlUtils;

class GetRevisionsCommand extends RavenCommand
{
    private ?string $id = null;
    private ?int $start = null;
    private ?int $pageSize = null;
    private bool $metadataOnly = false;
    private ?DateTime $before = null;
    private ?string $changeVector = null;
    private ?StringArray $changeVectors = null;

    protected function __construct()
    {
        parent::__construct(null);
    }

    public static function forChangeVector(?string $changeVector, bool $metadataOnly = false): self
    {
        $command = new self();
        $command->changeVector = $changeVector;
        $command->metadataOnly = $metadataOnly;

        return $command;
    }

    public static function forChangeVectors(StringArray|array $changeVectors, bool $metadataOnly = false): self
    {
        $command = new self();
        $command->changeVectors = is_array($changeVectors) ? StringArray::fromArray($changeVectors) : $changeVectors;
        $command->metadataOnly = $metadataOnly;

        return $command;
    }

    public static function beforeDate(?string $id, DateTime $before): self
    {
        if ($id == null) {
            throw new IllegalArgumentException("Id cannot be null");
        }
        $command = new self();
        $command->id = $id;
        $command->before = $before;

        return $command;
    }

    public static function withPagination(string $id, ?int $start, ?int $pageSize, bool $metadataOnly = false): self
    {
        if ($id == null) {
            throw new IllegalArgumentException("Id cannot be null");
        }

        $command = new self();

        $command->id = $id;
        $command->start = $start;
        $command->pageSize = $pageSize;
        $command->metadataOnly = $metadataOnly;

        return $command;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getBefore(): ?DateTime
    {
        return $this->before;
    }

    public function getChangeVector(): ?string
    {
        return $this->changeVector;
    }

    public function getChangeVectors(): ?StringArray
    {
        return $this->changeVectors;
    }

    public function createUrl(ServerNode $serverNode): string
    {
        $pathBuilder = (new StringBuilder($serverNode->getUrl()))
                ->append("/databases/")
                ->append($serverNode->getDatabase())
                ->append("/revisions?");

        $this->getRequestQueryString($pathBuilder);

        return $pathBuilder->__toString();

    }

    public function createRequest(ServerNode $serverNode): HttpRequestInterface
    {
        return new HttpRequest($this->createUrl($serverNode), HttpRequest::GET);
    }

    public function getRequestQueryString(StringBuilder & $pathBuilder): void
    {
        if ($this->id != null) {
            $pathBuilder->append("&id=")->append(UrlUtils::escapeDataString($this->id));
        } else if ($this->changeVector != null) {
            $pathBuilder->append("&changeVector=")->append(UrlUtils::escapeDataString($this->changeVector));
        } else if ($this->changeVectors != null) {
            foreach ($this->changeVectors as $changeVector) {
                $pathBuilder->append("&changeVector=")->append(UrlUtils::escapeDataString($changeVector));
            }
        }

        if ($this->before != null) {
            $pathBuilder->append("&before=")->append(NetISO8601Utils::format($this->before, true));
        }

        if ($this->start != null) {
            $pathBuilder->append("&start=")->append($this->start);
        }

        if ($this->pageSize != null) {
            $pathBuilder->append("&pageSize=")->append($this->pageSize);
        }

        if ($this->metadataOnly) {
            $pathBuilder->append("&metadataOnly=true");
        }
    }

    public function setResponse(?string $response, bool $fromCache): void
    {
        if ($response == null) {
            $this->result = null;
            return;
        }

        $this->result = json_decode($response, true);
    }

    public function isReadRequest(): bool
    {
        return true;
    }
}
