<?php

namespace RavenDB\Documents\Session\Loaders;

use DateTimeInterface;
use RavenDB\Type\StringArray;

interface QueryIncludeBuilderInterface
{
    public function includeCounter(?string $path, ?string $name): QueryIncludeBuilderInterface;

    /**
     * @param string|null $pathOrNames
     * @param null|string|StringArray|array $names
     * @return QueryIncludeBuilder
     */
    public function includeCounters(?string $pathOrNames, $names): QueryIncludeBuilderInterface;

    public function includeAllCounters(?string $path): QueryIncludeBuilderInterface;

//    public function includeTimeSeries(?string $path, ?string name): QueryIncludeBuilderInterface;
//
//    public function includeTimeSeries(?string $path, ?string name, DateTimeInterface $from, DateTimeInterface $to): QueryIncludeBuilderInterface;
}
