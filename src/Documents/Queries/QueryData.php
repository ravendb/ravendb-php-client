<?php

namespace RavenDB\Documents\Queries;

use RavenDB\Documents\Session\Tokens\DeclareTokenArray;
use RavenDB\Documents\Session\Tokens\LoadTokenList;
use RavenDB\Type\StringArray;

class QueryData
{
    private ?StringArray $fields = null;
    private ?StringArray $projections = null;
    private ?string $fromAlias = null;
    private ?DeclareTokenArray $declareTokens = null;
    private ?LoadTokenlist $loadTokens = null;
    private bool $isCustomFunction = false;
    private bool $mapReduce = false;
    private bool $isProjectInto = false;
    private ?ProjectionBehavior $projectionBehavior = null;

    public function isMapReduce(): bool
    {
        return $this->mapReduce;
    }

    public function setMapReduce(bool $mapReduce): void
    {
        $this->mapReduce = $mapReduce;
    }

    public function getFields(): ?StringArray
    {
        return $this->fields;
    }

    public function setFields(?StringArray $fields): void
    {
        $this->fields = $fields;
    }

    public function getProjections(): StringArray
    {
        return $this->projections;
    }

    public function setProjections(?StringArray $projections): void
    {
        $this->projections = $projections;
    }

    public function getFromAlias(): ?string
    {
        return $this->fromAlias;
    }

    public function setFromAlias(?string $fromAlias): void
    {
        $this->fromAlias = $fromAlias;
    }

    public function getDeclareTokens(): ?DeclareTokenArray
    {
        return $this->declareTokens;
    }

    public function setDeclareTokens(?DeclareTokenArray $declareTokens): void
    {
        $this->declareTokens = $declareTokens;
    }

    public function getLoadTokens(): ?LoadTokenlist
    {
        return $this->loadTokens;
    }

    public function setLoadTokens(?LoadTokenlist $loadTokens): void
    {
        $this->loadTokens = $loadTokens;
    }

    public function isCustomFunction(): bool
    {
        return $this->isCustomFunction;
    }

    public function setCustomFunction(bool $customFunction): void
    {
        $this->isCustomFunction = $customFunction;
    }

    /**
     * @param StringArray|array $fields
     * @param StringArray|array $projections
     * @param string|null $fromAlias
     * @param DeclareTokenArray|null $declareTokens
     * @param LoadTokenList|null $loadTokens
     * @param bool $isCustomFunction
     */
    public function __construct(
        $fields,
        $projections,
        ?string            $fromAlias = null,
        ?DeclareTokenArray $declareTokens = null,
        ?LoadTokenList     $loadTokens = null,
        bool               $isCustomFunction = false
    )
    {
        $this->fields = is_array($fields) ? StringArray::fromArray($fields) : $fields;
        $this->projections = is_array($projections) ? StringArray::fromArray($projections) : $projections;
        $this->fromAlias = $fromAlias;
        $this->declareTokens = $declareTokens;
        $this->loadTokens = $loadTokens;
        $this->isCustomFunction = $isCustomFunction;
    }

    public static function customFunction(string $alias, string $func): QueryData
    {
        return new QueryData(StringArray::fromArray([$func]),
            new StringArray(),
            $alias,
            null,
            null,
            true);
    }

    public function isProjectInto(): bool
    {
        return $this->isProjectInto;
    }

    public function setProjectInto(bool $projectInto)
    {
        $this->isProjectInto = $projectInto;
    }

    public function getProjectionBehavior(): ?ProjectionBehavior
    {
        return $this->projectionBehavior;
    }

    public function setProjectionBehavior(?ProjectionBehavior $projectionBehavior): void
    {
        $this->projectionBehavior = $projectionBehavior;
    }
}
