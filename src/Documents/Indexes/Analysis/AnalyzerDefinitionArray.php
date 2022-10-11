<?php

namespace RavenDB\Documents\Indexes\Analysis;

use RavenDB\Type\TypedArray;

class AnalyzerDefinitionArray extends TypedArray
{
    public function __construct()
    {
        parent::__construct(AnalyzerDefinition::class);
    }

    public static function fromArray(array $data, $nullAllowed = false): AnalyzerDefinitionArray
    {
        $sa = new AnalyzerDefinitionArray();
        $sa->setNullAllowed($nullAllowed);

        foreach ($data as $key => $value) {
            $sa->offsetSet($key, $value);
        }

        return $sa;
    }
}
