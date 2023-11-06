<?php

namespace RavenDB\Documents\Queries\Facets;

use RavenDB\Exceptions\IllegalStateException;



/**
 * @template T
 */
class RangeBuilder
{
    private string $path;

    /** @var T|null  */
    private $lessBound = null;

    /** @var T|null  */
    private $greaterBound = null;
    private bool $lessInclusive = false;
    private bool $greaterInclusive = false;

    private bool $lessSet = false;
    private bool $greaterSet = false;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public static function forPath(string $path): RangeBuilder
    {
        return new RangeBuilder($path);
    }

    private function createClone(): RangeBuilder
    {
        $builder = new RangeBuilder($this->path);
        $builder->lessBound = $this->lessBound;
        $builder->greaterBound = $this->greaterBound;
        $builder->lessInclusive = $this->lessInclusive;
        $builder->greaterInclusive = $this->greaterInclusive;
        $builder->lessSet = $this->lessSet;
        $builder->greaterSet = $this->greaterSet;
        return $builder;
    }

    /**
     * @param T $value
     * @return RangeBuilder
     */
    public function isLessThan($value): RangeBuilder {
        if ($this->lessSet) {
            throw new IllegalStateException("Less bound was already set");
        }

        $clone = $this->createClone();
        $clone->lessBound = $value;
        $clone->lessInclusive = false;
        $clone->lessSet = true;
        return $clone;
    }

    /**
     * @param T $value
     * @return RangeBuilder
     */
    public function isLessThanOrEqualTo($value): RangeBuilder
    {
        if ($this->lessSet) {
            throw new IllegalStateException("Less bound was already set");
        }

        $clone = $this->createClone();
        $clone->lessBound = $value;
        $clone->lessInclusive = true;
        $clone->lessSet = true;
        return $clone;
    }

    /**
     * @param T $value
     * @return RangeBuilder
     */
    public function isGreaterThan($value): RangeBuilder
    {
        if ($this->greaterSet) {
            throw new IllegalStateException("Greater bound was already set");
        }

        $clone = $this->createClone();
        $clone->greaterBound = $value;
        $clone->greaterInclusive = false;
        $clone->greaterSet = true;
        return $clone;
    }

    /**
     * @param T $value
     * @return RangeBuilder
     */
    public function isGreaterThanOrEqualTo($value): RangeBuilder
    {
        if ($this->greaterSet) {
            throw new IllegalStateException("Greater bound was already set");
        }

        $clone = $this->createClone();
        $clone->greaterBound = $value;
        $clone->greaterInclusive = true;
        $clone->greaterSet = true;
        return $clone;
    }

    public function getStringRepresentation($addQueryParameter): ?string
    {
        $less = null;
        $greater = null;

        if (!$this->lessSet && !$this->greaterSet) {
            throw new IllegalStateException("Bounds were not set");
        }

        if ($this->lessSet) {
            $lessParamName = $addQueryParameter($this->lessBound);
            $less = $this->path . ($this->lessInclusive ? " <= " : " < ") . "$" . $lessParamName;
        }

        if ($this->greaterSet) {
            $greaterParamName = $addQueryParameter($this->greaterBound);
            $greater = $this->path . ($this->greaterInclusive ? " >= " : " > ") . "$" . $greaterParamName;
        }

        if ($less != null && $greater != null) {
            return $greater . " and " . $less;
        }

        return $less ?? $greater;
    }
}
