<?php

namespace RavenDB\Documents\Indexes\Analysis;

use RavenDB\Type\TypedArray;

class AnalyzerDefinitionArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(AnalyzerDefinition::class);
    }
}
