<?php

namespace RavenDB\Documents\Session\Loaders;

use DateTimeInterface;
use RavenDB\Type\StringArray;

interface QueryIncludeBuilderInterface
{
    public function includeCounter(?string $path, ?string $name): QueryIncludeBuilderInterface;

    /**
     * @param string|array|StringArray|null $pathOrNames
     * @param array|StringArray $names
     * @return QueryIncludeBuilderInterface
     */
    public function includeCounters(?string $pathOrNames, $names): QueryIncludeBuilderInterface;

    public function includeAllCounters(?string $path): QueryIncludeBuilderInterface;

//    public function includeTimeSeries(?string $path, ?string name): QueryIncludeBuilderInterface;
//
//    public function includeTimeSeries(?string $path, ?string name, DateTimeInterface $from, DateTimeInterface $to): QueryIncludeBuilderInterface;
}
