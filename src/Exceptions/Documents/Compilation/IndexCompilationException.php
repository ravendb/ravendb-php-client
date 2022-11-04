<?php

namespace RavenDB\Exceptions\Documents\Compilation;

use RavenDB\Exceptions\Compilation\CompilationException;
use Throwable;

class IndexCompilationException extends CompilationException
{
    private string $indexDefinitionProperty;
    private string $problematicText;

    public function __construct(string $message = "", ?Throwable $cause = null)
    {
        parent::__construct($message, $cause);
    }

    public function getIndexDefinitionProperty(): string
    {
        return $this->indexDefinitionProperty;
    }

    public function setIndexDefinitionProperty(string $indexDefinitionProperty): void
    {
        $this->indexDefinitionProperty = $indexDefinitionProperty;
    }

    public function getProblematicText(): string
    {
        return $this->problematicText;
    }

    public function setProblematicText(string $problematicText): void
    {
        $this->problematicText = $problematicText;
    }
}
