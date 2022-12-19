<?php

namespace RavenDB\Documents\Session\Loaders;

interface TimeSeriesIncludeBuilderInterface
{
    function includeTags(): TimeSeriesIncludeBuilderInterface;

    function includeDocument(): TimeSeriesIncludeBuilderInterface;
}
