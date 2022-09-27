<?php

namespace RavenDB\Documents\Queries\Highlighting;

use RavenDB\Type\TypedList;

class HighlightingsList extends TypedList
{
    public function __construct()
    {
        parent::__construct(Highlightings::class);
    }
}
