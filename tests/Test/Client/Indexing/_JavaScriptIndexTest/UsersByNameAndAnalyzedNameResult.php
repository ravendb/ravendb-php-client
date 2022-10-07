<?php

namespace tests\RavenDB\Test\Client\Indexing\_JavaScriptIndexTest;

class UsersByNameAndAnalyzedNameResult
{
    private ?string $analyzedName = null;

    public function getAnalyzedName(): ?string
    {
        return $this->analyzedName;
    }

    public function setAnalyzedName(?string $analyzedName): void
    {
        $this->analyzedName = $analyzedName;
    }
}
