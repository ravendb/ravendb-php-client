<?php

namespace RavenDB\Http;

class ClusterTopology
{
    private string $lastNodeId;
    private string $topologyId;
    private int $etag;

    private array $members = [];
    private array $promotables = [];
    private array $watchers = [];

    public function contains(string $node): bool
    {
        if (in_array($node, $this->members)) {
            return true;
        }
        if (in_array($node, $this->promotables)) {
            return true;
        }
        return in_array($node, $this->watchers);
    }

    public function getUrlFromTag(string $tag): ?string
    {
        if ($tag == null) {
            return null;
        }
        if (array_key_exists($tag, $this->members)) {
            return $this->members[$tag];
        }
        if (array_key_exists($tag, $this->promotables)) {
            return $this->promotables[$tag];
        }
        if (array_key_exists($tag, $this->watchers)) {
            return $this->watchers[$tag];
        }
        return null;
    }

    public function getAllNodes(): array
    {
        return array_merge($this->members, $this->promotables, $this->watchers);
    }

    public function getMembers(): array
    {
        return $this->members;
    }

    public function setMembers(array $members): void
    {
        $this->members = $members;
    }

    public function getPromotables(): array
    {
        return $this->promotables;
    }

    public function setPromotables(array $promotables): void
    {
        $this->promotables = $promotables;
    }

    public function getWatchers(): array
    {
        return $this->watchers;
    }

    public function setWatchers(array $watchers): void
    {
        $this->watchers = $watchers;
    }

    public function getLastNodeId(): string
    {
        return $this->lastNodeId;
    }

    public function setLastNodeId(string $lastNodeId): void
    {
        $this->lastNodeId = $lastNodeId;
    }

    public function getTopologyId(): string
    {
        return $this->topologyId;
    }

    public function setTopologyId(string $topologyId): void
    {
        $this->topologyId = $topologyId;
    }

    public function getEtag(): int
    {
        return $this->etag;
    }

    public function setEtag(int $etag): void
    {
        $this->etag = $etag;
    }
}
