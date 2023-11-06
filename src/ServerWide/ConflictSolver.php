<?php

namespace RavenDB\ServerWide;

class ConflictSolver
{
    private ?ScriptResolverArray $resolveByCollection = null;
    private bool $resolveToLatest = false;

    public function getResolveByCollection(): ?ScriptResolverArray
    {
        return $this->resolveByCollection;
    }

    public function setResolveByCollection(?ScriptResolverArray $resolveByCollection): void
    {
        $this->resolveByCollection = $resolveByCollection;
    }

    public function isResolveToLatest(): bool
    {
        return $this->resolveToLatest;
    }

    public function setResolveToLatest(bool $resolveToLatest): void
    {
        $this->resolveToLatest = $resolveToLatest;
    }
}
