<?php

namespace RavenDB\Documents\Session\Loaders;

use RavenDB\Documents\Conventions\DocumentConventions;

class TimeSeriesIncludeBuilder extends IncludeBuilderBase implements TimeSeriesIncludeBuilderInterface
{
    public function __construct(?DocumentConventions $conventions) {
        parent::__construct($conventions);
    }

    public function includeTags(): TimeSeriesIncludeBuilderInterface
    {
        $this->includeTimeSeriesTags = true;
        return $this;
    }

    public function includeDocument(): TimeSeriesIncludeBuilderInterface
    {
        $this->includeTimeSeriesDocument = true;
        return $this;
    }
}
