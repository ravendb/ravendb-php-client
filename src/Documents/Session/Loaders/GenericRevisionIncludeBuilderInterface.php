<?php

namespace RavenDB\Documents\Session\Loaders;

use DateTime;

/**
 * @template TBuilder
 */
interface GenericRevisionIncludeBuilderInterface
{
    public function includeRevisions(string $changeVectorPaths): IncludeBuilderInterface;
    public function includeRevisionsBefore(DateTime $before): IncludeBuilderInterface;

}
